<?php
function k_scss_compile($src, $dest) {
    k_log("Compiling SCSS from $src to $dest");
    k_log_indent();
    k_shell_cmd("sass --update " . escapeshellarg(k_absolute_path($src) . ':' . k_absolute_path($dest)));
    k_log_unindent();
}