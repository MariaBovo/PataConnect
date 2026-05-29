import sys
import json
from system.ipc import IPC



if __name__ == '__main__':
    raw_input = sys.stdin.read()
    try:
        if not raw_input.strip():
            raise ValueError("Empty input received.")
            
        input_data = json.loads(raw_input)

        if isinstance(input_data, dict) and "call" in input_data and "parameters" in input_data:
            call_name = input_data["call"]
            parameters = input_data["parameters"]
            
            result = IPC.execute_published_function(call_name, parameters)
            
            sys.stdout.write(json.dumps(result))
        else:
            raise ValueError("Invalid IPC payload structure. Expected 'call' and 'parameters'.")

    except json.JSONDecodeError as e:
        sys.stderr.write(f"py JSON Error: {str(e)}")
    except Exception as e:
        sys.stderr.write(f"py Execution Error: {str(e)}")