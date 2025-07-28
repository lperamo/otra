<?php
declare(strict_types=1);
namespace otra\tools\secrets;

use JsonException;
use Redis;
use otra\cache\php\Logger;
use otra\OtraException;
use const otra\cache\php\{BASE_PATH, CACHE_PATH, CLASSMAP, CORE_PATH};

/**
 * Returns the encryption key.
 * 1) Try Redis cache.
 * 2) If missing, contact TPM daemon and store key 5 min in Redis.
 *
 * @param string $tpmDaemonSocketPath We need this variable to prevent security holes when using Docker containers
 *
 * @throws JsonException|OtraException
 */
function getEncryptionKeyFromTPMDaemon(string $tpmDaemonSocketPath = '/run/otra/tpm_daemon.sock'): string
{
  static $redis;

  if ($redis === null)
  {
    $redis = new \Redis();
    $redis->pconnect('127.0.0.1');
  }

  if ($redis->exists('tpm'))
    return $redis->get('tpm');

  // Connect to the Unix socket of the daemon (timeout after 5 seconds)
  $client = stream_socket_client(
    'unix://' . $tpmDaemonSocketPath,
    $errno,
    $errorString,
    5
  );

  if (!$client)
  {
    define (__NAMESPACE__ . '\\ERROR', 'Could not connect to TPM daemon: ' . $errorString . ' (' . $errno . ')');
    Logger::logTo(ERROR, 'tpm');
    throw new OtraException(ERROR);
  }

  // Prepare the JSON request: command "decrypt"
  fwrite($client, json_encode(
    [
      'basePath' => BASE_PATH,
      'command' => 'decrypt',
      'scriptPath' => CORE_PATH . 'tools/secrets/tpm.sh'
    ],
    JSON_THROW_ON_ERROR
  ));

  fflush($client);
  stream_socket_shutdown($client, STREAM_SHUT_WR);

  // Retrieve the response from the daemon
  $response = stream_get_contents($client);
  fclose($client);

  try
  {
    $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
  } catch (JsonException $exception)
  {
    define(__NAMESPACE__ . '\\ERROR', 'JSON decoding error: ' . $exception->getMessage() . $response);
    Logger::logTo(ERROR, 'tpm');
    throw new OtraException(ERROR);
  }

  if (isset($result['result']))
  {
    $redis->set('tpm', $result);
    $redis->expire('tpm', 600); // 10 min
    return trim($result['result']);
  }

  require_once CORE_PATH . 'OtraException.php';
  throw new OtraException('TPM daemon error: ' . ($result['error'] ?? 'Unknown error'));
}
