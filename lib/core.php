<?php
function konstrukt() {
    _k_init();
    
    // read options
    $args = $_SERVER['argv'];
    array_shift($args);
    $options = _k_read_options($args);
    $options += array (
        'base_dir' => isset ($args[0]) ? array_shift($args) : '.',
        'watch' => false,
    );
    
    // store the base dir
    _k_base_dir($options['base_dir']);
    
    // build resources
    _k_build($options);
}

/**
 * Register a resource type.
 * 
 * The build function for the resource must be at konstrukt_{$r}.
 * 
 * The build function receives as its only param the options passed to the build script.
 * Its return value is discarded, but it may log messages using k_log() and errors using
 * k_error().
 * 
 * @param string $r
 */
function k_register_resource($r) {
    _k_resources($r);
}

/**
 * Log a message to STDOUT.
 * @param string $msg
 */
function k_log($msg) {
    fwrite(STDOUT, _k_log_format('INFO', $msg));
}

/**
 * Log an error to STDOUT.
 * @param string $err
 */
function k_error($err) {
    fwrite(STDERR, _k_log_format('ERROR', $err, 'red'));
}

/**
 * Indent the log output.
 */
function k_log_indent($msg = null) {
    if ($msg) {
        fwrite(STDOUT, _k_log_format('', $msg, 'dark_gray'));
    }
    _k_log_prefix("> ");
}

/**
 * Unindent the log output.
 */
function k_log_unindent() {
    _k_log_prefix('', "> ");
}

/**
 * Watch a file or directory for changes, and run the current build script again if something changes.
 * @param string $path
 */
function k_watch($path) {
    if ($path) {
        _k_watch_pp($path);
    }
}

/**
 * Make an absolute path, based on the current base_dir.
 * @param string $path
 * @return string
 */
function k_absolute_path($path) {
    return _k_path_merge(_k_base_dir(), $path);
}

/**
 * Create a dir if it does not already exist.
 * @param string $dir
 */
function k_setup_dir($dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

/**
 * Run a shell command.
 * @param string $cmd
 * @param bool $pointer Return a process pointer? Defaults to false. If you do this, be sure to close it using proc_close().
 */
function k_shell_cmd($cmd, $pointer = false) {
    $descriptorspec = array (
        0 => array ('pipe', 'r'),
        1 => array ('pipe', 'w'),
        2 => array ('pipe', 'w'),
    );
    $pp = proc_open($cmd, $descriptorspec, $pipes);
    while ($err = fgets($pipes[2], 2048)) {
        k_error(rtrim($err, "\n"));
    }
    if ($pointer) {
        return array ($pp, $pipes[0], $pipes[1], $pipes[2]);
    }
    proc_close($pp);
}

function _k_build(array $options) {
    $resources = _k_resources();
    $builds = array ();
    foreach ($resources as $r) {
        if (!isset ($options["no-{$r}"])) {
            $builds[$r] = "konstrukt_{$r}";
        }
    }
    
    $watches = array ();
    
    // run all builds
    foreach ($builds as $r => $f) {
        _k_build_one($r, $f, $options);
        foreach (_k_watch_pp() as $path) {
            $absolute_path = _k_path_merge($options['base_dir'], $path);
            if (!$absolute_path || !file_exists($absolute_path)) {
                k_error("Can't watch path {$path}: file not found");
                continue;
            }
            if (is_dir($absolute_path)) {
                $absolute_path = rtrim($absolute_path, '/') . '/';
            }
            $watches[$absolute_path][] = $r;
        }
    }
    
    if ($options['watch'] && $watches) {
        // start the watch loop and run it until we die
        $paths = array_keys($watches);
        while (true) {
            if ($path = _k_watch_wait($paths)) {
                foreach ($watches as $watch_path => $watch_resources) {
                    if (substr($path, 0, strlen($watch_path)) == $watch_path) {
                        foreach ($watch_resources as $r) {
                            _k_build_one($r, $builds[$r], $options);
                        }
                    }
                }
            }
        }
    }
    
}

function _k_build_one($r, $f, $options) {
    k_log_indent("Building {$r}");
    try {
        $f($options);
    } catch (Exception $e) {
        k_error("Unhandled exception: " . $e->getMessage());
    }
    k_log_unindent();
}

function _k_init() {
    k_register_resource('js');
    k_register_resource('css');
    _k_extension_load('coffee');
    _k_extension_load('js');
    _k_extension_load('scss');
}

/**
 * Read options from the args array.
 * @param array &$args  argv array. Options will be stripped from this array.
 * @return array $options
 */
function _k_read_options(array &$args) {
    $options = array ();
    foreach ($args as $k => $arg) {
        if (substr($arg, 0, 2) == '--') {
            $arg = substr($arg, 2);
            if (strpos($arg, '=')) {
                list ($param, $value) = explode('=', $arg);
                $options[$param] = $value;
            } else {
                $options[$arg] = true;
            }
            unset ($args[$k]);
        } else if (substr($arg, 0, 1) == '-') {
            $chars = str_split(substr($arg, 1));
            $options += array_combine($chars, array_fill(0, sizeof($chars), true));
        }
    }
    return $options;
}

function _k_resources($r = null) {
    static $resources = array ();
    if ($r && !in_array($r, $resources)) {
        if (!is_callable("konstrukt_{$r}")) {
            throw new LogicException("Invalid resource: {$r}. Build function not found.");
        }
        $resources[] = $r;
    }
    return $resources;
}

function _k_watch_pp($path = null) {
    static $watch_list = array ();
    if ($path) {
        $watch_list[] = $path;
    } else {
        $output = $watch_list;
        $watch_list = array ();
        return $output;
    }
}

function _k_watch_wait(array $paths) {
    k_log_indent('Watching for changes...');
    $args = array_merge(array ('-r', '-q', '-c', '-e', 'modify', '-e', 'create', '-e', 'delete'), array_map('escapeshellarg', $paths));
    list ($proc, $in, $out, $err) = k_shell_cmd('inotifywait ' . implode(' ', $args), true);
    k_log_unindent();
    $line = fgetcsv($out, 2048, ',', '"');
    proc_close($proc);
    if ($line) {
        return $line[0];
    } else {
        return null;
    }
}

function _k_path_merge($base, $path) {
    if (substr($path, 0, 1) == '/') {
        return $path;
    } else {
        $path = rtrim($base, '/') . '/' . $path;
        if ($realpath = realpath($path)) {
            return $realpath;
        }
        return $path;
    }
}

function _k_base_dir($dir = null) {
    static $base_dir = null;
    if ($dir) {
        $base_dir = $dir;
    }
    return $base_dir;
}

function _k_extension_load($extension) {
    $dir = dirname(__FILE__);
    require_once ("{$dir}/{$extension}.php");    
}

function _k_escapeshellarg($arg) {
    if (strpos($arg, '=')) {
        list ($start, $end) = explode('=', $arg);
        var_dump($start);
        var_dump($end);
    }
    return escapeshellarg($arg);
}

function _k_log_color($str, $color) {
    static $colors = array (
        'red' => '0;31',
        'dark_gray' => '1;30',
    );
    if (isset ($colors[$color])) {
        $str = "\033[{$colors[$color]}m{$str}\033[0m";
    }
    return $str;
}

function _k_log_format($label, $msg, $color = null) {
    $str = str_pad("[{$label}]", 8, " ", STR_PAD_RIGHT);
    $str .= '[' . date('Y-m-d H:i:s') . '] ';
    $str .= _k_log_prefix();
    $str .= $msg;
    if ($color) {
        $str = _k_log_color($str, $color);
    }
    $str .= "\n";
    return $str;
}

function _k_log_prefix($push = '', $pop = '') {
    static $prefix = '';
    if ($pop) {
        if (substr($prefix, -1 * strlen($pop)) == $pop) {
            $prefix = substr($prefix, 0, -1 * strlen($pop));
        }
    }
    if ($push) {
        $prefix .= $push;
    }
    return $prefix;
}
