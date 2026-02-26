<?php

use HTMLPurifier_Config;

return function(HTMLPurifier_Config $config) {
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('Attr.EnableID', true);
    $config->set('HTML.AllowedComments', ['pagebreak']);
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/|player.vimeo.com/video/)%');
};
