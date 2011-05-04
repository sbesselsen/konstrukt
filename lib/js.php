<?php
/**
 * Build JS packages from the specified source directory into the destination directory.
 * 
 * Options:
 * - asset_root: asset root (see Sprockets documentation). Note that this has nothing to do
 *      with the 'assets' feature of Konstrukt.
 * 
 * @param string $src   Source directory.
 * @param string $dest  Destination directory.
 * @param array $includes   Directories that contain include files.
 * @param array $options
 */
function k_js_package($src, $dest, array $includes = array (), array $options = array ()) {
    k_log_indent("Packaging JS from $src to $dest");
    
    $src = k_absolute_path($src);
    $abs_dest = k_absolute_path($dest);
    
    // create the target path
    k_setup_dir($dest);
    
    $args = array ();
    foreach ($includes as $path) {
        $args[] = '-I';
        $args[] = escapeshellarg(k_absolute_path($path));
    }
    
    if (!empty ($options['asset_root'])) {
        $args[] = '--asset-root=' . escapeshellarg(k_absolute_path($options['asset_root']));
    }
    
    foreach (glob("{$src}/*.js") as $package) {
        // package the file
        $package_name = basename($package);
        k_log_indent("Packaging {$package_name}");
        $combined_path = "{$abs_dest}/{$package_name}";
        $temp_path = $combined_path . '.TEMP';
        $package_args = array_merge($args, array ($package, '>', $temp_path));
        k_shell_cmd('sprocketize ' . implode(' ', $package_args));
        
        // update it only if necessary
        if (!file_exists($combined_path) || md5_file($temp_path) != md5_file($combined_path)) {
            rename($temp_path, $combined_path);
            k_log("Package {$package_name} updated");
        } else {
            unlink($temp_path);
        }
        
        // store metadata
        k_metadata_add('js', $package_name, array (
            'path' => "{$dest}/{$package_name}",
            'timestamp' => filemtime($combined_path),
        ));
        
        k_log_unindent();
    }
    k_log_unindent();
}

/**
 * Minify the JS files in the source directory into the destination directory.
 * @param string $src   Source directory.
 * @param string $dest  Destination directory.
 */
function k_js_minify($src, $dest) {
    k_log_indent("Minifying JS from $src to $dest");
    
    $src = k_absolute_path($src);
    $abs_dest = k_absolute_path($dest);
    
    // create the target path
    k_setup_dir($dest);
    foreach (glob("{$src}/*.js") as $file) {
        $file_name = basename($file);
        $compressed_path = "{$abs_dest}/{$file_name}";
        if (!file_exists($compressed_path) || filemtime($file) > filemtime($compressed_path)) {
            k_log("Compressing {$file_name}");
            k_shell_cmd('yuicompressor --nomunge ' . escapeshellarg($file) . ' > ' . escapeshellarg($compressed_path));
        }
        
        // store metadata
        k_metadata_add('js', $file_name, array (
            'minified_path' => "{$dest}/{$file_name}",
            'minified_timestamp' => filemtime($compressed_path),
        ));
    }
    k_log_unindent();
}
