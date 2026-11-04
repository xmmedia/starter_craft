<?php

// documentation: vendor/craftcms/cms/docs/oauth.md
return [
    // OAuth clients (e.g. MCP connectors) may only register redirects to these origins/path prefixes.
    // Empty rejects every registration; Craft's default of ['*'] allows any.
    // Scheme & host must match exactly; http loopback hosts accept any port.
    'redirectDomains' => [
        // Claude web, Desktop & mobile
        // 'https://claude.ai/api/mcp/auth_callback',
        // 'https://claude.com/api/mcp/auth_callback',

        // Claude Code & other local clients
        // 'http://localhost',
        // 'http://127.0.0.1',

        // ChatGPT – confirm the URL shown in the connector's advanced settings
        // 'https://chatgpt.com/connector_platform_oauth_redirect',
        // 'https://chatgpt.com/connector/oauth',
    ],
];
