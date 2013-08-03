<?php
namespace php_require\php_package;

/*
    Open the given Package.json file.
*/

function readPackageJson($filename) {

    if (!is_file($filename)) {
        return array();
    }

    $json = file_get_contents($filename);

    $data = json_decode($json, true);

    return $data;
}

/*
    This is a hack at the moment.
*/

function runPostInstall($filename) {

    // read the filename as a package.json file.
    $data = readPackageJson($filename);

    // if there are no items in the array return false.
    if (count($data) === 0) {
        return false;
    }

    // If there is no post install script return true.
    if (!isset($data["scripts"]["postinstall"])) {
        return true;
    }

    $script = $data["scripts"]["postinstall"];

    // if the script does not start with "php" return false.
    if (strpos($script, "php") != 0) {
        return false;
    }

    // require the script removing "php" from the start.
    require(dirname($filename) . DIRECTORY_SEPARATOR . substr($script, 4));

    return true;
}

/*
    Get the name from package.json.
*/

function readNameFromPackage($filename) {

    // read the filename as a package.json file.
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
    curl_setopt($client, CURLOPT_CONNECTTIMEOUT_MS, 5000);

    $fileData = curl_exec($client);

    file_put_contents($tmpfile, $fileData);

    $zip = new \ZipArchive();  
    $x = $zip->open($tmpfile);

    if($x !== true) {  
        if($debug !== true) {
            unlink($tmpfile);
        }
        return array("error" => "There was a problem. Please try again!");
    }

    $return = array();

    // extract to tmp destination.
    $zip->extractTo($tmpdir);
    $zip->close();

    // read package.json and get $name.
    $packdir = findPackageDir($tmpdir);
    $name = readNameFromPackage($packdir . DIRECTORY_SEPARATOR . "package.json");

    // if we got a $name move everything.
    if ($name) {
        // make sure we have the $destination.
        if (!is_dir($destination)) {
            // TODO: Check this is safe.
            mkdir($destination, 0755, true);
        }
        if (is_dir($destination . DIRECTORY_SEPARATOR . $name)) {
            $return["error"] = "Cannot install package as it's already installed!";
        } else {
            // move all items at package.json level to $destination.$name
            rename($packdir, $destination . DIRECTORY_SEPARATOR . $name);
            // run the postinstall script
            runPostInstall($destination . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . "package.json");
            // return the output directory.
            $return["output"] = $destination . DIRECTORY_SEPARATOR . $name;
        }
    } else {
        // return an error if the package was not found.
        $return["error"] = "No pacakage.json found. Please try again!";
    }

    deleteDir($tmpdir);
    unlink($tmpfile);

    return $return;
}

$module->exports = function ($source, $destination, $debug=false) {
    return extractRemoteZip($source, $destination, $debug);
}
?>