<?php
namespace php_require\php_package;

function readPackageJson($filename) {

    $json = file_get_contents($filename);

    $data = json_decode($json, true);

    return $data;
}

function runPostInstall($filename) {

    $data = readPackageJson($filename);
print_r($data);
    if (!isset($data["scripts"]["postinstall"])) {
        return true;
    }

    $script = $data["scripts"]["postinstall"];

    echo $script;

    if (strpos($script, "php") != 0) {
        return false;
    }

    require(dirname($filename) . substr($script, 5));

    return true;
}

/*
    Get the name from package.json.
*/

function readNameFromPackage($filename) {

    $data = readPackageJson($filename);

    if (!isset($data["name"])) {
        return "";
    }

    return $data["name"];
}

/*
    Recursively find a package.json file in the given directory.
*/

function findPackageDir($dirpath) {

    $cdir = scandir($dirpath);

    foreach ($cdir as $key => $value) {

        if (!in_array($value, array(".",".."))) {

            if ($value == "package.json") {
                return $dirpath;
            }

            $fullpath = $dirpath . DIRECTORY_SEPARATOR . $value;

            if (is_dir($fullpath) && findPackageDir($fullpath)) { 
                return $fullpath; 
            }
            // echo $dirpath . "<br/>";
        }
    }

    return "";
}

/*
    Deletes the given directory and all files in it.
*/

function deleteDir($dirpath) {

    if (!is_dir($dirpath)) {
        throw new InvalidArgumentException("$dirpath must be a directory");
    }

    if (substr($dirpath, strlen($dirpath) - 1, 1) != DIRECTORY_SEPARATOR) {
        $dirpath .= DIRECTORY_SEPARATOR;
    }

    $cdir = scandir($dirpath);

    foreach ($cdir as $key => $value) {

        if (!in_array($value, array(".",".."))) {

            $fullpath = $dirpath . DIRECTORY_SEPARATOR . $value;

            if (is_dir($fullpath)) {
                deleteDir($fullpath);
            } else {
                unlink($fullpath);
            }
        }
    }

    rmdir($dirpath);
}

/*
    Get the zip file found at $source and unpack it into $destination.
*/

function extractRemoteZip($source, $destination, $debug=false) { 

    $tmpdir = "/tmp/" . md5($source);
    $tmpfile = $tmpdir . '.zip';
    $client = curl_init($source);

    curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($client, CURLOPT_FOLLOWLOCATION, true);

    $fileData = curl_exec($client);

    file_put_contents($tmpfile, $fileData);

    $zip = new ZipArchive();  
    $x = $zip->open($tmpfile);  
    if($x === true) {  

        // extract to tmp destination
        $zip->extractTo($tmpdir);
        $zip->close();

        // read package.json and get $name
        $packdir = findPackageDir($tmpdir);
        $name = readNameFromPackage($packdir . DIRECTORY_SEPARATOR . "package.json");

        // if we got a $name move everything
        if ($name) {
            // make sure we have the $destination
            if (!is_dir($destination)) {
                // TODO: Check this is safe.
                mkdir($destination, 0755, true);
            }
            // move all items at package.json level to $destination.$name
            rename($packdir, $destination . DIRECTORY_SEPARATOR . $name);

            // run the postinstall script
            runPostInstall($destination . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "package.json");

        } else {
            echo("No pacakage.json found. Please try again!");
        }

        deleteDir($tmpdir);
        unlink($tmpfile);

    } else {
        if($debug !== true) {
            unlink($tmpfile);
        }
        echo("There was a problem. Please try again!");
    }
}

$module->exports = function ($source, $destination, $debug=false) {
    return extractRemoteZip($source, $destination, $debug);
}
?>