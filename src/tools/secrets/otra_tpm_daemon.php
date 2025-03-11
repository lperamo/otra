#!/usr/bin/env php
<?php
declare(strict_types=1);
const SOCKET_PATH = '/run/otra/tpm_daemon.sock';

// Remove stale socket left after a crash
if (file_exists(SOCKET_PATH) && !is_link(SOCKET_PATH))
  unlink(SOCKET_PATH);

// Create the Unix socket server (STREAM_SERVER_LISTEN = default)
$server = stream_socket_server('unix://' . SOCKET_PATH, $errno, $errorString);

if (!$server)
  die("Cannot bind socket : $errorString ($errno)" . PHP_EOL);

// Set permissions on the socket (e.g., 0770 so that the authorized group can connect)
chmod(SOCKET_PATH, 0770);

echo 'TPM daemon is listening on ', SOCKET_PATH, PHP_EOL;

while ($connection = @stream_socket_accept($server, -1))
{
  // Read data from the connection (limited to 1024 bytes in this example)
  $dataFromConnection = fread($connection, 1024);

  if (!$dataFromConnection)
  {
    fclose($connection);
    continue;
  }

  // Expect a JSON request, for example {"command": "decrypt", "basePath": "/path", "scriptPath": "/scriptPath"}
  $request = json_decode($dataFromConnection, true, 512, JSON_THROW_ON_ERROR);

  if (!isset($request['command'], $request['basePath']))
  {
    fwrite($connection, json_encode(['error' => 'Command or basePath not specified'], JSON_THROW_ON_ERROR));
    fclose($connection);
    continue;
  }

  $basePath = $request['basePath'];

  if ($request['command'] === 'decrypt')
  {
    // Always use non-interactive mode since the TPM script is designed for that.
    $result = shell_exec('sudo ' . $request['scriptPath'] . ' -n -b ' . $basePath);
    $response = ['result' => $result === null ? null : trim($result)];
  } else
    $response = ['error' => 'Unrecognized command'];

  fwrite($connection, json_encode($response, JSON_THROW_ON_ERROR));
  fclose($connection);
}

fclose($server);
unlink(SOCKET_PATH);
