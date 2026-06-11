<?php
require_once __DIR__ . '/test_ipc.php';
require_once __DIR__ . '/test_stock.php';

$testClasses = [
    new IpcTest(),
    new StockTest(),
];

$passed = 0;
$failed = 0;
$failures = [];

echo "Running PHP Unit Tests...\n";
echo "-------------------------------\n";

foreach ($testClasses as $testClass) {
    $className = get_class($testClass);
    echo "Running tests in {$className}...\n";
    $methods = get_class_methods($testClass);
    foreach ($methods as $method) {
        if (str_starts_with($method, 'test')) {
            echo "  Running {$method}... ";
            try {
                $testClass->$method();
                echo "\033[32mOK\033[0m\n";
                $passed++;
            } catch (Exception $e) {
                echo "\033[31mFAILED\033[0m\n";
                $failed++;
                $failures["{$className}::{$method}"] = $e->getMessage();
            }
        }
    }
}

echo "-------------------------------\n";
echo "Tests summary: {$passed} passed, {$failed} failed.\n";

if ($failed > 0) {
    echo "\nFailures:\n";
    foreach ($failures as $method => $message) {
        echo "- {$method}: {$message}\n";
    }
    exit(1);
} else {
    echo "\033[32mAll tests passed successfully!\033[0m\n";
    exit(0);
}
