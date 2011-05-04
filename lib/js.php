<?php
function k_js_package($src, $dest, array $includes = array (), array $options = array ()) {
    k_log_indent("Packaging JS from $src to $dest");
    
    $src = k_absolute_path($src);
    $dest = k_absolute_path($dest);
    
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
        $package_name = basename($package);
        k_log_indent("Packaging {$package_name}");
        $combined_path = "{$dest}/{$package_name}";
        $temp_path = $combined_path . '.TEMP';
        $package_args = array_merge($args, array ($package, '>', $temp_path));
        k_shell_cmd('sprocketize ' . implode(' ', $package_args));
        if (!file_exists($combined_path) || md5_file($temp_path) != md5_file($combined_path)) {
            rename($temp_path, $combined_path);
            k_log("Package {$package_name} updated");
        } else {
            unlink($temp_path);
        }
        k_log_unindent();
    }
    k_log_unindent();
}

function k_js_minify($src, $dest) {
    k_log_indent("Minifying JS from $src to $dest");
    
    $src = k_absolute_path($src);
    $dest = k_absolute_path($dest);
    
    // create the target path
    k_setup_dir($dest);
    foreach (glob("{$src}/*.js") as $file) {
        $file_name = basename($file);
        $compressed_path = "{$dest}/{$file_name}";
        if (!file_exists($compressed_path) || filemtime($file) > filemtime($compressed_path)) {
            k_log("Compressing {$file_name}");
            k_shell_cmd('yuicompressor --nomunge ' . escapeshellarg($file) . ' > ' . escapeshellarg($compressed_path));
        }
    }
    k_log_unindent();
}