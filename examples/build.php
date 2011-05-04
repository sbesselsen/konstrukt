#!/usr/bin/env php
<?php
include dirname(__FILE__) . '/../lib/konstrukt/lib/core.php';

function konstrukt_js() {
    k_watch('js/src');
    k_watch('js/packages');
    
    k_coffee_compile('js/src');
    k_js_package('js/packages', 'public/js/packages', array ('js/src', 'js/lib'), array ('asset_root' => 'public'));
    k_js_minify('public/js/packages', 'public/js/min');
}

function konstrukt_css() {
    k_watch('scss');
    
    k_scss_compile('scss', 'public/css');
}

function konstrukt_metadata() {
    k_metadata_write('resources/metadata.json');
}

konstrukt();
