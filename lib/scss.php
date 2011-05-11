<?php
function k_scss_compile($src, $dest) {
    return _k_scss_compile($src, $dest, false);
}

function _k_scss_compile($src, $dest, $retrying) {
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
    $retry_next = false;
    foreach (glob("{$abs_dest}/*.css") as $file) {
        $file_name = basename($file);
        if (!isset ($last_updated[$file]) || filemtime($file) > $last_updated[$file]) {
            if (!$retrying && filesize($file) == 0) {
                // try building this file again
                k_log("Empty file: {$file_name}; trying again in 1 second");
                unlink($file);
                $retry_next = true;
                continue;
            }
            k_log("Updated {$file_name}");
        }
        
        // store metadata
        k_metadata_add('css', $file_name, array (
            'path' => "{$dest}/{$file_name}",
            'timestamp' => filemtime($file),
        ));
    }
    
    k_log_unindent();
    
    if ($retry_next) {
        sleep(1);
        return _k_scss_compile($src, $dest, true);
    }
}