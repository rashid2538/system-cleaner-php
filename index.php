<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once(__DIR__ . '/functions.php');

$directory = isset($argv[1])
    ? $argv[1]
    : getcwd();

// dumpdd($directory, get_files_and_folders($directory));
cleanup($directory);
