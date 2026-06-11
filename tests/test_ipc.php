<?php
require_once __DIR__ . '/../utils/ipc.php';

class IpcTest {
    private function assertEqual($actual, $expected, $message = "") {
        if ($actual !== $expected) {
            $actualStr = json_encode($actual);
            $expectedStr = json_encode($expected);
            throw new Exception("Assertion failed: Expected {$expectedStr}, got {$actualStr}. {$message}");
        }
    }

    private function assertArrayHasKey($key, $array, $message = "") {
        if (!is_array($array) || !array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: Array does not have key '{$key}'. {$message}");
        }
    }

    public function testCallPySuccess() {
        $result = callpy('success', ['name' => 'John'], 'tests/mock_ipc.py');
        $this->assertEqual(is_array($result), true, "Result should be an array");
        $this->assertEqual($result['status'], 'success');
        $this->assertEqual($result['result']['name'], 'John');
    }

    public function testCallPyError() {
        // The mock script will write to STDERR and exit with code 1.
        $result = callpy('error', [], 'tests/mock_ipc.py');
        $this->assertEqual(is_array($result), true, "Result should be an array on error");
        $this->assertArrayHasKey('error', $result);
        $this->assertEqual($result['error'], 'Python execution failed.');
        $this->assertArrayHasKey('details', $result);
        $this->assertEqual($result['details'], 'Mock python execution failed. Intentional test error.');
    }

    public function testCallPyMalformedJson() {
        // The mock script will write invalid JSON to STDOUT.
        // PHP's json_decode on malformed JSON returns null.
        $result = callpy('malformed', [], 'tests/mock_ipc.py');
        $this->assertEqual($result, null);
    }

    public function testCallPyMissingScript() {
        // Run with a non-existent Python script path.
        // It should write an error to STDERR or fail to run.
        $result = callpy('any', [], 'tests/non_existent_mock.py');
        $this->assertEqual(is_array($result), true, "Result should be an array");
        $this->assertArrayHasKey('error', $result);
        $this->assertEqual($result['error'], 'Python execution failed.');
        $this->assertArrayHasKey('details', $result);
    }
}
