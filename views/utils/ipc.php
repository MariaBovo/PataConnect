<?php
function callpy(string $call, mixed $parameters, string $ipc_path = '../../system/ipc.py') {
    $command = escapeshellcmd("python " . $ipc_path);

    $descriptors = [
        0 => ["pipe", "r"], // STDIN
        1 => ["pipe", "w"], // STDOUT
        2 => ["pipe", "w"]  // STDERR
    ];
    
    $process = proc_open($command, $descriptors, $pipes);
    
    if (is_resource($process)) {
        $payload = [
            "call" => $call,
            "parameters" => $parameters
        ];

        fwrite($pipes[0], json_encode($payload));
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        if (!empty($errors)) {
            error_log("IPC Error: " . $errors);
            return ["error" => "Python execution failed.", "details" => trim($errors)];
        }
        
        return json_decode($output, true);
    }
    
    return ["error" => "Failed to open Python process."];
}
?>