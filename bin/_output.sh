# shellcheck shell=bash
# Shared output helpers for bin/check & bin/check_full.
# Source this file, then wrap each command in: step "Label" cmd...

if [ -t 1 ]; then
    C_STEP=$'\033[1;36m'
    C_OK=$'\033[1;32m'
    C_FAIL=$'\033[1;31m'
    C_WARN=$'\033[1;33m'
    C_OFF=$'\033[0m'
else
    C_STEP='' C_OK='' C_FAIL='' C_WARN='' C_OFF=''
fi

CURRENT_STEP=''

step() {
    CURRENT_STEP="$1"
    shift
    printf '\n%s▶ %s%s\n' "$C_STEP" "$CURRENT_STEP" "$C_OFF"
    "$@"
}

FAILED_STEPS=()

# for steps that should all run even when one fails (e.g. the security checks, so one advisory
# doesn't hide the rest): step_continue "Label" cmd..., then fail_if_steps_failed after the last
step_continue() {
    step "$@" || {
        FAILED_STEPS+=("$CURRENT_STEP")
        report_failure
    }
}

fail_if_steps_failed() {
    if [ ${#FAILED_STEPS[@]} -gt 0 ]; then
        local list
        printf -v list '%s, ' "${FAILED_STEPS[@]}"
        printf '\n%s✖ Failed: %s%s\n' "$C_FAIL" "${list%, }" "$C_OFF"
        exit 1
    fi
}

# shellcheck disable=SC2317
report_failure() {
    printf '\n%s✖ %s failed%s\n' "$C_FAIL" "${CURRENT_STEP:-command}" "$C_OFF"
}

# for steps that shouldn't fail the check: step "Label" cmd... || report_warning
report_warning() {
    printf '\n%s⚠ %s reported issues (not failing the check)%s\n' "$C_WARN" "${CURRENT_STEP:-command}" "$C_OFF"
}

report_success() {
    printf '\n%s✔ %s%s\n' "$C_OK" "$1" "$C_OFF"
}
