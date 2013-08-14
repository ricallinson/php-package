<?php

$module = new stdClass();

/*
    Now we "require()" the file to test.
*/

require(__DIR__ . "/../index.php");

/*
    Now we test it.
*/

describe("php-package", function () {

    describe("readPackageJson()", function () {
            
        it("should return [0]", function () {
            $filepath = __DIR__ . "/fixtures/false/package.json";
            $json = php_require\php_package\readPackageJson($filepath);
            assert(count($json) === 0);
        });

        it("should return [foo]", function () {
            $filepath = __DIR__ . "/fixtures/foo/package.json";
            $json = php_require\php_package\readPackageJson($filepath);
            assert($json["name"] === "foo");
        });
    });

    describe("runPostInstall()", function () {

        it("should return [false]", function () {
            $filepath = __DIR__ . "/fixtures/false/package.json";
            $result = php_require\php_package\runPostInstall($filepath);
            assert($result === false);
        });

        it("should return [true] from no post install", function () {
            $filepath = __DIR__ . "/fixtures/foo/package.json";
            $result = php_require\php_package\runPostInstall($filepath);
            assert($result === true);
        });

        it("should return [false] from a post install that is not php", function () {
            $filepath = __DIR__ . "/fixtures/baz/package.json";
            $result = php_require\php_package\runPostInstall($filepath);
            assert($result === false);
        });

        it("should return [true] from a post install", function () {
            $filepath = __DIR__ . "/fixtures/bar/package.json";
            ob_start();
            php_require\php_package\runPostInstall($filepath);
            $result = ob_get_clean();
            assert($result === "POST INSTALL");
        });
    });

    describe("readNameFromPackage()", function () {

        it("should return an [empty string]", function () {
            $filepath = __DIR__ . "/fixtures/false/package.json";
            $name = php_require\php_package\readNameFromPackage($filepath);
            assert($name === "");
        });

        it("should return [foo]", function () {
            $filepath = __DIR__ . "/fixtures/foo/package.json";
            $name = php_require\php_package\readNameFromPackage($filepath);
            assert($name === "foo");
        });
    });

    describe("findPackageDir()", function () {

        it("should return an [empty string]", function () {
            $dir = __DIR__ . "/fixtures/false";
            $filepath = php_require\php_package\findPackageDir($dir);
            assert($filepath === "");
        });

        it("should return [/fixtures/bar]", function () {
            $dir = __DIR__ . "/fixtures";
            $filepath = php_require\php_package\findPackageDir($dir);
            assert(strpos($filepath, "/fixtures/bar") !== false);
        });

        it("should return [/fixtures/bar]", function () {
            $dir = __DIR__ . "/fixtures/foo";
            $filepath = php_require\php_package\findPackageDir($dir);
            assert(strpos($filepath, "/fixtures/foo") !== false);
        });

        it("should return an [empty string] from /fixtures/site", function () {
            $dir = __DIR__ . "/fixtures/site";
            $filepath = php_require\php_package\findPackageDir($dir);
            assert($filepath === "");
        });
    });

    describe("deleteDir()", function () {

        it("should return [true]", function () {
            $deldir = __DIR__ . "/fixtures/no-dir";
            $status = false;
            try {
                php_require\php_package\deleteDir($deldir);
            } catch (Exception $err) {
                $status = true;
            }
            assert($status);
        });

        it("should return [false]", function () {
            $deldir = __DIR__ . "/fixtures/delete";
            $mkdir = $deldir . "/folder/deep";
            mkdir($mkdir, 0755, true);
            file_put_contents($mkdir . "/test.text", "test file");
            file_put_contents(dirname($mkdir) . "/test.text", "test file");
            php_require\php_package\deleteDir($deldir);
            assert(is_dir($deldir) === false);
        });
    });

    describe("extractRemoteZipFromUrl()", function () {

        it("should return [foo]", function () {
            $source = "file://" . __DIR__ . "/fixtures/foo.zip";
            $destination = __DIR__ . "/fixtures/site";
            $status = php_require\php_package\extractRemoteZipFromUrl($source, $destination, true);
            assert(isset($status["package"]) && $status["package"] === "foo");
            // clean up
            php_require\php_package\deleteDir($destination);
        });
    });

    describe("extractRemoteZipFromFile()", function () {

        it("should return [foo]", function () {
            $source = __DIR__ . "/fixtures/foo.zip";
            $destination = __DIR__ . "/fixtures/site";
            $status = php_require\php_package\extractRemoteZipFromFile($source, $destination, true);
            assert(isset($status["package"]) && $status["package"] === "foo");
            // clean up
            php_require\php_package\deleteDir($destination);
        });
    });

    describe("extractRemoteZip()", function () {

        it("should return [foo]", function () {
            $source = __DIR__ . "/fixtures/foo.zip";
            $destination = __DIR__ . "/fixtures/site";
            $status = php_require\php_package\extractZip($source, $destination, true);
            assert(isset($status["package"]) && $status["package"] === "foo");
            // clean up
            php_require\php_package\deleteDir($destination);
        });

        it("should return [404]", function () {
            $source = __DIR__ . "/fixtures/bar.zip";
            $destination = __DIR__ . "/fixtures/site";
            $status = php_require\php_package\extractZip($source, $destination);
            assert(isset($status["code"]) && $status["code"] === 404);
        });

        it("should return [400]", function () {
            $source = __DIR__ . "/fixtures/bad.zip";
            $destination = __DIR__ . "/fixtures/site";
            $status = php_require\php_package\extractZip($source, $destination, true);
            assert(isset($status["code"]) && $status["code"] === 400);
        });

        it("should return [409]", function () {
            $source = __DIR__ . "/fixtures/foo.zip";
            $destination = __DIR__ . "/fixtures/site";
            $status = php_require\php_package\extractZip($source, $destination, true);
            $status = php_require\php_package\extractZip($source, $destination, true);
            assert(isset($status["code"]) && $status["code"] === 409);
            // clean up
            php_require\php_package\deleteDir($destination);
        });

        it("should return [422]", function () {
            $source = __DIR__ . "/fixtures/empty.zip";
            $destination = __DIR__ . "/fixtures/site";
            $status = php_require\php_package\extractZip($source, $destination, true);
            assert(isset($status["code"]) && $status["code"] === 422);
        });
    });
});
