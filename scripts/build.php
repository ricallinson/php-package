<?php
chdir(__DIR__ . DIRECTORY_SEPARATOR . "..");
$tmpl = file_get_contents("assets" . DIRECTORY_SEPARATOR . "bootstrap.html");
$code = file_get_contents("index.php");
$file = str_replace("{{php-package}}", $code, $tmpl);
$file = str_replace("namespace php_require\php_package;", "", $file);
file_put_contents("build" . DIRECTORY_SEPARATOR . "bootstrap.php", $file);
