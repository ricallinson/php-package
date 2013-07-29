<?php
class MockModule {
    public $exports = array();
}
$module = new MockModule();
?>
<?php


/*
    Get the name from package.json.
*/

function readNameFromPackage($filename) {

    // echo $filename . "<br/>";

    $json = file_get_contents($filename);

    $data = json_decode($json, true);

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
<?php
if (isset($_POST["source"])) {
    $source = $_POST["source"];
    $destination = __DIR__ . DIRECTORY_SEPARATOR . "node_modules";
    $lockdown = $destination . DIRECTORY_SEPARATOR . ".htaccess";
    extractRemoteZip($source, $destination);
    if (!is_file($lockdown)) {
        file_put_contents($lockdown, "Options -Indexes\ndeny from all\n");
    }
}?>
<html>
    <head>
        <title>Package.php</title>
    </head>
    <body>
        <form method="post">
            <input type="text" name="source" value="http://localhost:8888/derp/php-require-master.zip" size="100">
            <input type="submit" value="Use">
        </form>
    </body>
</html>