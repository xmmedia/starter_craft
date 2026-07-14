<?php

use HTMLPurifier_Config;
use HTMLPurifier_Filter;

// Preserve soft hyphens (&shy;) which HTMLPurifier strips by default
$softHyphenFilter = new class () extends HTMLPurifier_Filter {
    public $name = 'SoftHyphen';

    public function preFilter($html, $config, $context): string
    {
        return str_replace("\xc2\xad", '[[SHY]]', $html);
    }

    public function postFilter($html, $config, $context): string
    {
        return str_replace('[[SHY]]', "\xc2\xad", $html);
    }
};

return static function (HTMLPurifier_Config $config) use ($softHyphenFilter) {
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('Attr.EnableID', true);
    $config->set('HTML.AllowedComments', ['pagebreak']);
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/|player.vimeo.com/video/)%');
    $config->set('Filter.Custom', [$softHyphenFilter]);
};
