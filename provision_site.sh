#!/usr/bin/env bash
# Provisions a new dev/staging site end to end.
#
# Orchestrates the steps from the "New Dev/Staging Site" Notion doc:
#   1. Create the 1Password server + database items (you fill in the passwords)
#   2. Create the SiteWorx account (InterWorx nodeworx CLI, over ssh)
#   3. Create the database + database user
#   4. Upload and run setup_server.sh as the site user
#   5. Add the PHP restart sudoers entry for the site user
#   6. (optional) Add the Craft queue cron job
#   7. Run setup_gitlab_ci_vars.sh locally
#
# The SSL certificate is not generated here — the InterWorx CLI always includes
# the www subdomain and gives no way to exclude it, so it fails when www has no
# DNS record. Generate it in SiteWorx instead (see the manual steps at the end).
#
# The DNS record must already exist.
#
# Prerequisites:
#   - ssh access to the server via a configured ssh Host, with passwordless sudo
#   - op CLI signed in (op signin), glab authenticated, jq installed
#
# Usage: ./provision_site.sh
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
NODEWORX='nodeworx'
SITEWORX='siteworx'
# Database name/user suffix — always craft for this starter.
SITE_TYPE='craft'

die() { echo -e "${RED}Error: $*${NC}" >&2; exit 1; }
heading() { echo ""; echo -e "${BOLD}${CYAN}$*${NC}"; }
ok() { echo -e "  ${GREEN}✓${NC} $*"; }
warn() { echo -e "  ${YELLOW}!${NC} $*"; }

# ask <label> [default]
ask() {
    local label="$1" default="${2:-}" result
    if [[ -n "$default" ]]; then
        read -r -p "$label [$default]: " result
        echo "${result:-$default}"
    else
        read -r -p "$label: " result
        echo "$result"
    fi
}

# ask_required <label> [default] — re-prompts until non-empty
ask_required() {
    local label="$1" default="${2:-}" result
    while true; do
        result=$(ask "$label" "$default")
        [[ -n "$result" ]] && break
        echo -e "  ${RED}Required.${NC}" >&2
    done
    echo "$result"
}

# confirm <question> [default y|n] — returns 0 for yes
confirm() {
    local question="$1" default="${2:-n}" prompt reply
    [[ "$default" == "y" ]] && prompt="[Y/n]" || prompt="[y/N]"
    read -r -p "$question $prompt: " reply
    reply="$(echo "${reply:-$default}" | tr '[:upper:]' '[:lower:]')"
    [[ "$reply" == "y" ]]
}

# shell-quote args for the remote shell
rq() {
    local out="" arg
    for arg in "$@"; do
        out+=" $(printf '%q' "$arg")"
    done
    printf '%s' "${out# }"
}

# remote <command> [args…] — runs a single command on the server
remote() { ssh "$SSH_HOST" "$(rq "$@")"; }

# --- Preflight ---------------------------------------------------------------

command -v op   >/dev/null 2>&1 || die "op CLI not installed. Run: brew install 1password-cli"
command -v glab >/dev/null 2>&1 || die "glab CLI not installed. See: https://gitlab.com/gitlab-org/cli"
command -v jq   >/dev/null 2>&1 || die "jq not installed. Run: brew install jq"
op account list >/dev/null 2>&1 || die "Not signed in to 1Password. Run: op signin"
[[ -f "$SCRIPT_DIR/setup_server.sh" ]] || die "setup_server.sh not found in $SCRIPT_DIR"
[[ -f "$SCRIPT_DIR/setup_gitlab_ci_vars.sh" ]] || die "setup_gitlab_ci_vars.sh not found in $SCRIPT_DIR"

echo ""
echo -e "${BOLD}${CYAN}New Site Setup${NC}"
echo "The DNS record should already be added before running this."

# --- Gather input ------------------------------------------------------------

heading "Environment"
echo "  1) staging"
echo "  2) production"
read -r -p "Which are you provisioning? [1]: " env_choice
case "${env_choice:-1}" in
    2|production) SCOPE="production"; CRON_MINUTE='*/5' ;;
    *)            SCOPE="staging";    CRON_MINUTE='*/15' ;;
esac

heading "Server"
SSH_HOST=$(ask_required "ssh Host name (from ~/.ssh/config, e.g. dev3)")
echo -n "  Connecting… "
remote true >/dev/null 2>&1 || die "cannot ssh to '$SSH_HOST'"
remote sudo -n true >/dev/null 2>&1 || die "no passwordless sudo on '$SSH_HOST'"
remote sudo -n sh -c "command -v $NODEWORX" >/dev/null 2>&1 \
    || die "'$NODEWORX' not on root's PATH on '$SSH_HOST' — is this an InterWorx server?"
echo -e "${GREEN}ok${NC}"

SERVER_HOST=$(ask_required "Server hostname (for 1Password + GitLab, e.g. dev3.xmmedia.com)" "${SSH_HOST}.xmmedia.com")

heading "Site"
DOMAIN=$(ask_required "Domain (e.g. example.com without the www prefix)")
DOMAIN="${DOMAIN#https://}"; DOMAIN="${DOMAIN#http://}"; DOMAIN="${DOMAIN%/}"
SITE_USER=$(ask_required "SiteWorx username (e.g. exampledev — avoid using 'dev' as the prefix)")
ADMIN_EMAIL=$(ask_required "InterWorx account email" "admin@xmmedia.com")
DB_NAME="${SITE_USER}_${SITE_TYPE}"
SITE_BASE="/home/$SITE_USER/$DOMAIN"

# Verify the account doesn't already exist
if remote sudo -n "$NODEWORX" -u -n -c Siteworx -a listAccounts \
        | awk -F'\t' -v d="$DOMAIN" -v u="$SITE_USER" '$2==u || $10==d {found=1} END {exit !found}'; then
    die "a SiteWorx account for '$DOMAIN' or user '$SITE_USER' already exists on $SSH_HOST"
fi

heading "PHP"
PHP_AVAILABLE=$(remote sudo -n "$NODEWORX" -u -n -c Siteworx -a add --help \
    | awk '/^--php_version/ {print $NF}')
echo "  Available: $PHP_AVAILABLE"
PHP_PATH=$(ask "PHP version path" "$(echo "$PHP_AVAILABLE" | tr '|' '\n' | grep -E '^/opt/remi/php[0-9]+$' | tail -1)")
[[ "$PHP_PATH" =~ php([0-9]+)$ ]] || die "cannot derive the sudoers macro from '$PHP_PATH'"
PHP_RESTART="PHP${BASH_REMATCH[1]}_RESTART"

heading "1Password"
OP_VAULT=$(ask_required "Vault")
OP_TAG=$(ask "Tag (client name, optional)")

heading "Optional steps"
DO_GITLAB=n; confirm "Set the GitLab CI/CD variables?" y && DO_GITLAB=y || true
GITLAB_PROJECT=""
if [[ "$DO_GITLAB" == "y" ]]; then
    GITLAB_PROJECT=$(ask_required "GitLab project (e.g. xmmedia/my-client-site)")
fi
DO_CRON=n;   confirm "Add the Craft queue cron job ($CRON_MINUTE)?" y && DO_CRON=y || true

# --- Summary -----------------------------------------------------------------

heading "Summary"
printf "  %-22s %s\n" "environment"   "$SCOPE"
printf "  %-22s %s\n" "ssh host"      "$SSH_HOST"
printf "  %-22s %s\n" "server"        "$SERVER_HOST"
printf "  %-22s %s\n" "domain"        "$DOMAIN"
printf "  %-22s %s\n" "site user"     "$SITE_USER"
printf "  %-22s %s\n" "account email" "$ADMIN_EMAIL"
printf "  %-22s %s\n" "site base"     "$SITE_BASE"
printf "  %-22s %s\n" "database/user" "$DB_NAME"
printf "  %-22s %s\n" "PHP"           "$PHP_PATH ($PHP_RESTART)"
printf "  %-22s %s\n" "1P vault"      "$OP_VAULT${OP_TAG:+ (tag: $OP_TAG)}"
printf "  %-22s %s\n" "GitLab vars"   "$DO_GITLAB${GITLAB_PROJECT:+ ($GITLAB_PROJECT)}"
if [[ "$DO_CRON" == "y" ]]; then
    printf "  %-22s %s\n" "queue cron"    "$DO_CRON ($CRON_MINUTE)"
else
    printf "  %-22s %s\n" "queue cron"    "$DO_CRON"
fi
echo ""
confirm "Proceed? Everything after this point makes changes." n || { echo "Aborted."; exit 0; }

# --- 1. 1Password items ------------------------------------------------------

heading "1/7 Creating 1Password items"
OP_ARGS=(--category server --title "$DOMAIN" --vault "$OP_VAULT"
    "username=$SITE_USER"
    'port[text]=10022'
    "path[text]=$SITE_BASE/current"
    "domain[text]=$DOMAIN"
    "url[text]=$SERVER_HOST")
if [[ -n "$OP_TAG" ]]; then OP_ARGS+=(--tags "$OP_TAG"); fi
OP_SERVER_ID=$(op item create "${OP_ARGS[@]}" --format json | jq -r '.id')
ok "server item: $OP_SERVER_ID"

OP_ARGS=(--category database --title "$DOMAIN" --vault "$OP_VAULT"
    "username=$DB_NAME"
    "database[text]=$DB_NAME"
    'hostname[text]=localhost'
    'port[text]=3306'
    'database_type[text]=MySQL')
if [[ -n "$OP_TAG" ]]; then OP_ARGS+=(--tags "$OP_TAG"); fi
OP_DB_ID=$(op item create "${OP_ARGS[@]}" --format json | jq -r '.id')
ok "database item: $OP_DB_ID"

echo ""
echo -e "${BOLD}${YELLOW}Set the passwords on both items in 1Password now.${NC}"
echo "  Use ~30 characters. Symbols are fine for the SiteWorx account; for the"
echo "  database, be careful — they can be awkward in the .env file."
echo ""
read -r -p "  Press Enter once both passwords are saved… "

read_op_password() {
    local id="$1" label="$2" value
    while true; do
        value=$(op item get "$id" --vault "$OP_VAULT" --fields password --reveal 2>/dev/null || true)
        [[ -n "$value" ]] && break
        warn "no password set on the $label item yet."
        read -r -p "  Press Enter to check again… "
    done
    printf '%s' "$value"
}

SITE_PASSWORD=$(read_op_password "$OP_SERVER_ID" "server")
DB_PASSWORD=$(read_op_password "$OP_DB_ID" "database")
ok "passwords read from 1Password"

# --- 2. SiteWorx account -----------------------------------------------------

heading "2/7 Creating the SiteWorx account"
warn "passwords are passed as CLI arguments and are briefly visible in the server's process list."

# Reproduce a package template by expanding its options into --OPT_* flags;
# nodeworx's Siteworx add has no --package option.
PACKAGE=$(ask "Package template" "Default")
PACKAGE_OPTS=$(remote sudo -n "$NODEWORX" -u -n -c Packages -a listPackages \
    | awk -F'\t' -v p="$PACKAGE" '$2==p {print $3}')
[[ -n "$PACKAGE_OPTS" ]] || die "package '$PACKAGE' not found on $SSH_HOST"

ALLOWED_FLAGS=$(remote sudo -n "$NODEWORX" -u -n -c Siteworx -a add --help \
    | grep -oE '^--[a-zA-Z_]+' | sed 's/^--//')
SW_ARGS=()
while IFS='=' read -r key value; do
    [[ -z "$key" || -z "$value" ]] && continue
    grep -qx "$key" <<<"$ALLOWED_FLAGS" || continue
    SW_ARGS+=("--$key" "$value")
done < <(tr ',' '\n' <<<"$PACKAGE_OPTS")

IPV4=$(remote sudo -n "$NODEWORX" -u -n -c Siteworx -a add --help \
    | awk '/^--master_domain_ipv4/ {print $NF}' | cut -d'|' -f1)

remote sudo -n "$NODEWORX" -u -n -c Siteworx -a add \
    --master_domain "$DOMAIN" \
    --master_domain_ipv4 "$IPV4" \
    --uniqname "$SITE_USER" \
    --email "$ADMIN_EMAIL" \
    --encrypted n \
    --password "$SITE_PASSWORD" \
    --confirm_password "$SITE_PASSWORD" \
    --requires_password_change 0 \
    --language en-us \
    --php_version "$PHP_PATH" \
    --php_available "$PHP_PATH" \
    --create_package 0 \
    --restart_httpd 1 \
    "${SW_ARGS[@]}" \
    || die "failed to create the SiteWorx account"
ok "account created ($SITE_USER / $DOMAIN)"

# --- 3. Database -------------------------------------------------------------

heading "3/7 Creating the database and user"
# InterWorx prefixes both with the account username, so pass the suffix only.
remote sudo -n "$SITEWORX" -u -n --login_domain "$DOMAIN" -c MysqlDb -a add \
    --name "$SITE_TYPE" \
    --create_user 1 \
    --user "$SITE_TYPE" \
    --password "$DB_PASSWORD" \
    --confirm_password "$DB_PASSWORD" \
    --host localhost \
    || die "failed to create the database"
ok "database and user created ($DB_NAME)"

# --- 4. setup_server.sh ------------------------------------------------------

heading "4/7 Running setup_server.sh"
# The account's real path — /chroot/home/… on chrooted servers, /home/… otherwise.
REAL_BASE=$(remote sudo -n "$NODEWORX" -u -n -c Siteworx -a listAccounts \
    | awk -F'\t' -v u="$SITE_USER" '$2==u {print $21}')
[[ -n "$REAL_BASE" ]] || die "could not determine the site path for '$SITE_USER' on $SSH_HOST"
echo "  Site path on server: $REAL_BASE"

REMOTE_TMP="/tmp/setup_server.$$.sh"
scp -q "$SCRIPT_DIR/setup_server.sh" "$SSH_HOST:$REMOTE_TMP" || die "failed to copy setup_server.sh"
remote sudo -n install -o "$SITE_USER" -g "$SITE_USER" -m 755 \
    "$REMOTE_TMP" "$REAL_BASE/setup_server.sh" \
    || die "failed to place setup_server.sh in the site directory"
remote rm -f "$REMOTE_TMP"

# Run as the site user, in the domain dir; "Y" answers the script's confirm prompt.
remote sudo -n su - "$SITE_USER" -c "cd $(printf '%q' "$DOMAIN") && echo Y | sh ./setup_server.sh" \
    || die "setup_server.sh failed — fix and re-run it manually on the server"
ok "site directory structure created"

# --- 5. PHP restart sudoers --------------------------------------------------

heading "5/7 Adding the PHP restart sudoers entry"
SUDOERS_LINE=$(printf '\n# %s\n%s ALL=NOPASSWD: %s' "$DOMAIN" "$SITE_USER" "$PHP_RESTART")
if remote sudo -n grep -qE "^$SITE_USER ALL=NOPASSWD: $PHP_RESTART\$" /etc/sudoers.d/iworx-php-restart; then
    ok "entry already present"
else
    remote sudo -n sh -c "printf '%s' $(printf '%q' "$SUDOERS_LINE") >> /etc/sudoers.d/iworx-php-restart" \
        || die "failed to update /etc/sudoers.d/iworx-php-restart"
    ok "added: $SITE_USER ALL=NOPASSWD: $PHP_RESTART"
fi

# --- 6. Craft queue cron -----------------------------------------------------

heading "6/7 Craft queue cron job ($CRON_MINUTE)"
if [[ "$DO_CRON" == "y" ]]; then
    remote sudo -n "$SITEWORX" -u -n --login_domain "$DOMAIN" -c Cron -a add \
        --interface simple \
        --minute "$CRON_MINUTE" --hour '*' --day '*' --month '*' --dayofweek '*' \
        --script "cd $SITE_BASE/current && MAGICK_DISK_LIMIT=0 flock -n storage/queue.lock ./craft queue/run 2>&1" \
        || warn "failed to add the cron job — add it in SiteWorx instead"
    ok "cron job added"
else
    echo "  Skipped."
fi

# --- 7. GitLab CI variables --------------------------------------------------

heading "7/7 GitLab CI/CD variables"
if [[ "$DO_GITLAB" == "y" ]]; then
    "$SCRIPT_DIR/setup_gitlab_ci_vars.sh" "$GITLAB_PROJECT" \
        --scope "$SCOPE" \
        --remote-base "$SITE_BASE" \
        --remote-server "$SERVER_HOST" \
        --remote-user "$SITE_USER" \
        --context-host "$DOMAIN" \
        || warn "setup_gitlab_ci_vars.sh did not complete"
else
    echo "  Skipped."
fi

# --- Done --------------------------------------------------------------------

echo ""
echo -e "${GREEN}Done!${NC}"
echo ""
echo ""
echo -e "${BOLD}${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BOLD}  Remaining manual steps${NC}"
echo -e "${BOLD}${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "  ${BOLD}1.${NC} Generate the SSL certificate in SiteWorx (not scriptable — the CLI"
echo -e "     always includes www and cannot exclude it)"
echo -e "     Log in as ${YELLOW}$SITE_USER${NC} at ${YELLOW}https://$SERVER_HOST:2443/${NC} → SSL/TLS → Let's Encrypt"
echo -e "     Untick ${YELLOW}www.$DOMAIN${NC} unless it has its own DNS record"
echo -e "  ${BOLD}2.${NC} Add the CI public key to ~/.ssh/authorized_keys for ${YELLOW}$SITE_USER${NC} on ${YELLOW}$SSH_HOST${NC}"
echo -e "  ${BOLD}3.${NC} Complete ${YELLOW}$SITE_BASE/shared/.env${NC}"
echo "     Copy .env.example, then set CRAFT_ENVIRONMENT=production and ENVIRONMENT=development"
echo -e "  ${BOLD}4.${NC} Deploy from GitLab (the first attempt often fails — fix and retry)"
echo -e "  ${BOLD}5.${NC} Install or import the Craft database"
echo ""
echo -e "${BOLD}${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
