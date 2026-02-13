<?php

header('Content-Type: text/css');

// Note: This file requires built assets. Run 'yarn build' to generate them.
$manifestPath = __DIR__ . '/build/.vite/manifest.json';

if (!file_exists($manifestPath)) {
    echo "/* Editor CSS Error: Vite manifest not found. Run 'yarn build' to generate assets. */\n";
    return;
}

$manifest = json_decode(file_get_contents($manifestPath), true);

if ($manifest === null) {
    echo "/* Editor CSS Error: Failed to parse Vite manifest. */\n";
    return;
}

$entry = $manifest['public/js/src/editor.js'] ?? null;

if (!$entry) {
    echo "/* Editor CSS Error: Entry 'public/js/src/editor.js' not found in manifest. */\n";
    return;
}

if (empty($entry['css'])) {
    echo "/* Editor CSS Error: No CSS files associated with editor entry. */\n";
    return;
}

$cssPath = __DIR__ . '/build/' . $entry['css'][0];

if (!file_exists($cssPath)) {
    echo "/* Editor CSS Error: CSS file not found at {$entry['css'][0]}. */\n";
    return;
}

echo file_get_contents($cssPath);
