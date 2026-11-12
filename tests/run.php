<?php

/**
 * Runs every test_* function in the given test files (default: DataObjectPageControllerTest.php) on plain PHP.
 *
 *   php tests/run.php
 *   php tests/run.php tests/LookupCountTest.php
 *   DOM_CONTROLLER=/path/to/DataObjectPageController.php php tests/run.php
 */
require __DIR__ . '/bootstrap.php';

// A warning or notice from the code under test fails the test that caused it
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class AssertionFailed extends Exception {

}

function assertSame($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new AssertionFailed(trim($message . "\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true)));
    }
}

function assertTrue($condition, $message = 'expected true') {
    if ($condition !== true) {
        throw new AssertionFailed($message);
    }
}

function assertNull($actual, $message = '') {
    assertSame(null, $actual, $message);
}

function assertInstanceOf($class, $actual) {
    if (!$actual instanceof $class) {
        throw new AssertionFailed("expected an instance of $class, got " . (is_object($actual) ? get_class($actual) : gettype($actual)));
    }
}

/**
 * Runs $callback and returns what it threw, failing when it throws nothing or something other than $class
 */
function assertThrows($class, callable $callback) {
    try {
        $callback();
    } catch (AssertionFailed $failure) {
        throw $failure;
    } catch (Throwable $thrown) {
        if (!$thrown instanceof $class) {
            throw new AssertionFailed("expected $class, got " . get_class($thrown) . ': ' . $thrown->getMessage());
        }

        return $thrown;
    }

    throw new AssertionFailed("expected $class, nothing was thrown");
}

/**
 * Calls a method whatever its visibility
 */
function invoke($object, $method, ...$args) {
    return (new ReflectionMethod($object, $method))->invokeArgs($object, $args);
}

/**
 * The items of any list as a plain array
 */
function items($list) {
    $items = [];
    foreach ($list as $item) {
        $items[] = $item;
    }

    return $items;
}

function ids($list) {
    return array_map(function ($item) {
        return $item->ID;
    }, items($list));
}

/**
 * Global state the stubs keep, cleared before each test
 */
function resetState() {
    SilverStripe\ORM\DataObject::$lists = [];
    SilverStripe\i18n\i18n::$locale = 'en_US';
    TractorCow\Fluent\State\FluentState::reset();
    TractorCow\Fluent\Model\Locale::$cached = [];
    Page::$byLocale = [];
    Page::$loads = 0;
    SelfFilteringObject::$filtered = 0;
}

$files = array_slice($argv, 1) ?: [__DIR__ . '/DataObjectPageControllerTest.php'];
$before = get_defined_functions()['user'];
foreach ($files as $file) {
    require $file;
}
$tests = array_filter(array_diff(get_defined_functions()['user'], $before), function ($name) {
    return strpos($name, 'test_') === 0;
});

$failed = 0;
foreach ($tests as $test) {
    resetState();
    try {
        $test();
        echo "  ok    $test\n";
    } catch (Throwable $error) {
        $failed++;
        $where = $error instanceof AssertionFailed ? '' : ' (' . get_class($error) . ' at ' . basename($error->getFile()) . ':' . $error->getLine() . ')';
        echo "  FAIL  $test$where\n        " . str_replace("\n", "\n        ", $error->getMessage()) . "\n";
    }
}

echo "\n" . count($tests) . ' tests, ' . (count($tests) - $failed) . " passed, $failed failed\n";
echo 'controller: ' . (getenv('DOM_CONTROLLER') ?: 'src/View/DataObjectPageController.php') . "\n";
exit($failed ? 1 : 0);
