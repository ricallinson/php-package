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

    describe("todo", function () {
        assert(false);
    });
});
