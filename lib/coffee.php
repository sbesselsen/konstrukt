<?php
function k_coffee_compile($dir) {
    k_log_indent("Compiling Coffeescript in $dir");
    k_shell_cmd("coffee --compile " . escapeshellarg(k_absolute_path($dir)));
    k_log_unindent();
}