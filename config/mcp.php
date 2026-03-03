<?php

use craft\helpers\App;

// documentation: https://github.com/stimmtdigital/craft-mcp
return [
    'enabled' => App::env('MCP_ENABLED') ?? false,
    'enableDangerousTools' => App::env('MCP_DANGEROUS_TOOLS') ?? false,

    // Restrict MCP connections to specific IP addresses.
    // When empty, connections from any IP are allowed.
    // Default: [] (all IPs allowed)
    'allowedIps' => [
        '127.0.0.1',
        '::1',
    ],
];
