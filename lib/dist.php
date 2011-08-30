<?php
/**
 * Sync files from the specified source directory to the specified target directory.
 * 
 * Options:
 * - delete: delete files that are in the target directory but not in the source? Defaults to true.
 * - exclude: path, or array of paths, to exclude
 * - cvs_exclude: exclude .svn, .git etc.? Defaults to true
 * 
 * @param string $src
 * @param string $dest
 * @param array $options
 */
function k_dist_sync($src, $dest, array $options = array ()) {
    $options += array (
        'delete' => true,
        'exclude' => array (),
        'cvs_exclude' => true,
    );
    if ($options['exclude'] && !is_array($options['exclude'])) {
        $options['exclude'] = array ($options['exclude']);
    }
    k_log_indent("Syncing files from $src to $dest");
    
    $src_abs  = k_absolute_path($src);
    $dest_abs = k_absolute_path($dest);
    
    $args = array ('-av');
    if ($options['delete']) {
        $args[] = '--delete';
    }
    if ($options['cvs_exclude']) {
        $args[] = '--cvs-exclude';
    }
    if ($options['exclude']) {
        foreach (array_filter($options['exclude']) as $exclude) {
            $args[] = '--exclude=' . $exclude;
        }
    }
    
    $args[] = $src_abs . '/';
    $args[] = $dest_abs;
    
    list ($pp, $stdin, $stdout, $stderr) = k_shell_cmd('rsync ' . implode(' ', $args), true);
    while ($line = fgets($stdout, 1024)) {
        if (!$line = trim($line)) {
            continue;
        }
        if (preg_match('(^(sending incremental|sent [0-9]|total size is))', $line)) {
            continue;
        }
        k_log('Sync: ' . $line);
    }
    proc_close($pp);
    
    k_log_unindent();
}

/**
 * Create a dir if it does not already exist.
 * @param string $dir
 */
function k_dist_mkdir($dir) {
    $path = k_absolute_path($dir);
    if (!is_dir($path)) {
        k_log("Creating directory $dir");
        k_setup_dir($dir);
    }
}