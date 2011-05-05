<?php
function k_scss_compile($src, $dest) {
    k_log_indent("Compiling SCSS from $src to $dest");
    
    $src = k_absolute_path($src);
    $abs_dest = k_absolute_path($dest);
    
    $last_updated = array ();
    foreach (glob("{$abs_dest}/*.css") as $file) {
        $last_updated[$file] = filemtime($file);
    }
    
    k_shell_cmd("sass --update " . escapeshellarg($src . ':' . $abs_dest));
    
    clearstatcache();
    
    // create stylesheet metadata and log messages
    foreach (glob("{$abs_dest}/*.css") as $file) {
        $file_name = basename($file);
        if (!isset ($last_updated[$file]) || filemtime($file) > $last_updated[$file]) {
            k_log("Updated {$file_name}");
        }
        
        // store metadata
        k_metadata_add('css', $file_name, array (
            'path' => "{$dest}/{$file_name}",
            'timestamp' => filemtime($file),
        ));
    }
    
    k_log_unindent();
}
