import sys
import json

def main():
    try:
        raw_input = sys.stdin.read()
        if not raw_input.strip():
            sys.stderr.write("Mock Error: Empty input")
            sys.exit(1)
            
        data = json.loads(raw_input)
        call = data.get("call")
        params = data.get("parameters", {})
        
        if call == "success":
            sys.stdout.write(json.dumps({"status": "success", "result": params}))
        elif call == "error":
            sys.stderr.write("Mock python execution failed. Intentional test error.")
            sys.exit(1)
        elif call == "malformed":
            sys.stdout.write("{malformed json response")
        else:
            sys.stderr.write(f"Unknown mock call: {call}")
            sys.exit(1)
            
    except Exception as e:
        sys.stderr.write(f"Mock Error: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    main()
