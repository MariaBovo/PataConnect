import unittest
import sys
import json
import subprocess
from pathlib import Path

# Add project root to sys.path
PROJECT_ROOT = Path(__file__).resolve().parent.parent
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from system.ipc import IPC

class TestIPC(unittest.TestCase):
    
    def setUp(self):
        # Backup published_functions to avoid test side-effects
        self._orig_published = IPC.published_functions.copy()
        
    def tearDown(self):
        # Restore published_functions
        IPC.published_functions = self._orig_published

    def test_publish_decorator(self):
        @IPC.publish
        def test_dummy_func(x, y):
            return x + y
            
        self.assertIn("test_dummy_func", IPC.published_functions)
        self.assertEqual(IPC.published_functions["test_dummy_func"], test_dummy_func)

    def test_execute_dict_parameters(self):
        @IPC.publish
        def test_kw_func(a, b):
            return {"a": a, "b": b}
            
        res = IPC.execute_published_function("test_kw_func", {"a": 1, "b": 2})
        self.assertEqual(res, {"a": 1, "b": 2})

    def test_execute_list_parameters(self):
        @IPC.publish
        def test_pos_func(a, b):
            return a * b
            
        res = IPC.execute_published_function("test_pos_func", [3, 4])
        self.assertEqual(res, 12)

    def test_execute_single_parameter(self):
        @IPC.publish
        def test_single_func(val):
            return f"val:{val}"
            
        res = IPC.execute_published_function("test_single_func", "hello")
        self.assertEqual(res, "val:hello")

    def test_execute_unregistered_function(self):
        with self.assertRaises(ValueError) as ctx:
            IPC.execute_published_function("non_existent_func", {})
        self.assertIn("is not registered", str(ctx.exception))

    def test_main_script_success(self):
        payload = {
            "call": "non_existent_function",
            "parameters": {}
        }
        
        proc = subprocess.run(
            [sys.executable, str(PROJECT_ROOT / "main.py")],
            input=json.dumps(payload),
            text=True,
            capture_output=True
        )
        
        self.assertEqual(proc.stdout, "")
        self.assertIn("py Execution Error", proc.stderr)
        self.assertIn("Function 'non_existent_function' is not registered", proc.stderr)

    def test_main_script_invalid_json(self):
        proc = subprocess.run(
            [sys.executable, str(PROJECT_ROOT / "main.py")],
            input="{invalid json",
            text=True,
            capture_output=True
        )
        
        self.assertEqual(proc.stdout, "")
        self.assertIn("py JSON Error", proc.stderr)

    def test_main_script_invalid_payload_structure(self):
        payload = {"only_call": "foo"}
        proc = subprocess.run(
            [sys.executable, str(PROJECT_ROOT / "main.py")],
            input=json.dumps(payload),
            text=True,
            capture_output=True
        )
        
        self.assertEqual(proc.stdout, "")
        self.assertIn("py Execution Error", proc.stderr)
        self.assertIn("Invalid IPC payload structure", proc.stderr)

    def test_main_script_empty_input(self):
        proc = subprocess.run(
            [sys.executable, str(PROJECT_ROOT / "main.py")],
            input="",
            text=True,
            capture_output=True
        )
        
        self.assertEqual(proc.stdout, "")
        self.assertIn("py Execution Error", proc.stderr)
        self.assertIn("Empty input received", proc.stderr)

if __name__ == "__main__":
    unittest.main()
