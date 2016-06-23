<?

function writeConfigFile (string &$configFile, string &$content)
{
  if (true === empty($content))
  {
    echo brown(), 'Nothing to put into ', lightBlue(), $configFile, brown(), ' so we\'ll delete the main file if it exists.', endColor(), PHP_EOL;

    if (true === file_exists($configFile))
      unlink($configFile);

    return;
  }

  file_put_contents($configFile, $content);

  // Compresses the file
  file_put_contents($configFile, rtrim(preg_replace('@\s+@', ' ', php_strip_whitespace($configFile))) . PHP_EOL);
  echo greenText($configFile . ' updated.'), PHP_EOL;
}

/**
 * We return a string that contains an array with a PHP7 array like notation.
 *
 * @param string $content
 * @param array  $array
 */
function loopForEach(string &$content, array &$array)
{
  foreach ($array as $key => &$arrayChunk)
  {
    $key = is_numeric($key) ? '' : '\'' . $key . '\'' . '=>';

    if (false === is_array($arrayChunk))
    {
      $content .= $key . (is_numeric($arrayChunk) ? $arrayChunk : '\'' . $arrayChunk . '\'') . ',';
      continue;
    }

    $content .= $key . '[';

    loopForEach($content, $arrayChunk);
    $content = substr($content, 0, -1);
    $content .= '],';
  }
//  var_dump($content);die;
}

/** BEGINNING OF THE TASK */
$dir = BASE_PATH . 'bundles/';
$folderHandler = opendir($dir);
$configs = $routes = $schemas = [];

// we scan the bundles directory to retrieve all the bundles name ...
while (false !== ($file = readdir($folderHandler)))
{
  // config is not a bundle ... just a configuration folder
  if (true === in_array($file, ['.', '..', 'config']))
    continue;

  $bundleDir = $dir . $file;

  // We don't need the files either
  if (true !== is_dir($bundleDir))
    continue;

  // ... and we scan all those bundles to retrieve the config file names.
  $bundleConfigDir = $bundleDir . '/config/';
  $bundleConfigs = glob($bundleConfigDir . '*Config.php');
  $bundleRoutes = glob($bundleConfigDir . '*Routes.php');
  $bundleSchemas = glob($bundleConfigDir . 'data/yml/*Schema.yml');

  if (false === empty($bundleConfigs))
    $configs = array_merge($configs, $bundleConfigs);

  if (false === empty($bundleRoutes))
    $routes = array_merge($routes, $bundleRoutes);
}
closedir($folderHandler);

// now we have all the informations, we can create the files in 'bundles/config';
$configDir = $dir . 'config/';
$configFile = $configDir . 'Config.php';
$routesFile = $configDir . 'Routes.php';
//$schemasFile = $configDir . 'Schemas.php';

if (false === file_exists($configDir))
  mkdir($configDir, 0755);

$configsContent = '';

/** CONFIGS MANAGEMENT */
foreach ($configs as &$config)
  $configsContent .= file_get_contents($config);

writeConfigFile($configFile, $configsContent);

/** ROUTES MANAGEMENT */
$routesArray = [];

foreach($routes as &$route)
  $routesArray = array_merge($routesArray, require $route);

// Transforms the array in code that returns the array.

$routesContent = '<? return [';
loopForEach($routesContent, $routesArray);
$routesContent = substr($routesContent, 0, -1) . ']; ?>';

writeConfigFile($routesFile, $routesContent);
?>
