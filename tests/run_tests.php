<?php
require_once __DIR__ . '/test_ipc.php';

$testClass = new IpcTest();
$methods = get_class_methods($testClass);

$passed = 0;
$failed = 0;
$failures = [];

echo "Running PHP IPC Unit Tests...\n";
echo "-------------------------------\n";

foreach ($methods as $method) {
    if (str_starts_with($method, 'test')) {
        echo "Running {$method}... ";
        try {
            $testClass->$method();
            echo "\033[32mOK\033[0m\n";
            $passed++;
        } catch (Exception $e) {
            echo "\033[31mFAILED\033[0m\n";
            $failed++;
            $failures[$method] = $e->getMessage();
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
