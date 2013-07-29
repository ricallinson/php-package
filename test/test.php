<?php

/*
    Create a MockModule to load our module into for testing.
*/

class MockModule {
    public $exports = array();
}
$module = new MockModule();

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
