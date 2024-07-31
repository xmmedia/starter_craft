<?php

header('Content-Type: text/css');

$manifest = json_decode(file_get_contents('build/manifest.json'), true);

echo file_get_contents(ltrim($manifest['build/editor.css'], '/'));
