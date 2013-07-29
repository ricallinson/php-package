<?php

/*
    Report everything.
*/

error_reporting(E_ALL);
ini_set('display_errors', 'on');

/*
    From here we call the build script and then eval what was built.
*/

chdir(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
require("scripts" . DIRECTORY_SEPARATOR . "build.php");
$file = file_get_contents("build" . DIRECTORY_SEPARATOR . "bootstrap.php");
chdir(__DIR__);
eval(substr($file, 5));
?>