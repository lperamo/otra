<?php
declare(strict_types=1);

use config\AllConfig;

if (!defined('OTRA_DUMP_TEXT_BLOCK'))
{
  // How much
  define('OTRA_DUMP_ARRAY', [128, 512, 3]);
  define('OTRA_DUMP_ARRAY_KEY', ['maxChildren', 'maxData', 'maxDepth']);
  define('OTRA_DUMP_KEY_MAX_CHILDREN', 0);
  define('OTRA_DUMP_KEY_MAX_DATA', 1);
  define('OTRA_DUMP_KEY_MAX_DEPTH', 2);
  define('OTRA_DUMP_MAX_CHILDREN', 128);
  define('OTRA_DUMP_MAX_DATA', 512);
  define('OTRA_DUMP_MAX_DEPTH', 3);
  define('OTRA_DUMP_CONFIGURATION', [
    OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_CHILDREN] => OTRA_DUMP_MAX_CHILDREN,
    OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DATA] => OTRA_DUMP_MAX_DATA,
    OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DEPTH] => OTRA_DUMP_MAX_DEPTH,
  ]);

  // visibilities constants
  define('OTRA_DUMP_VISIBILITY_PUBLIC', 1);
  define('OTRA_DUMP_VISIBILITY_PROTECTED', 2);
  define('OTRA_DUMP_VISIBILITY_PRIVATE', 4);
  define('OTRA_DUMP_KEY_VISIBILITY', 0);
  define('OTRA_DUMP_KEY_VISIBILITY_SYMBOL', 1);

  define('OTRA_DUMP_VISIBILITIES', [
    OTRA_DUMP_VISIBILITY_PUBLIC => ['public', '+'],
    OTRA_DUMP_VISIBILITY_PROTECTED => ['protected', '#'],
    OTRA_DUMP_VISIBILITY_PRIVATE => ['private', '-']
  ]);

  // Display
  define('OTRA_DUMP_TEXT_BLOCK', '<span class="otra-dump--value">');
  define('OTRA_DUMP_END_TEXT_BLOCK', '</span>');
  define('OTRA_DUMP_INDENT_STRING', '│ ');
  define('OTRA_DUMP_HELP_CLASS', 'otra-dump--help');
}

/**
 * Sets the dump configuration to the defaults if the dump configurarion is not set.
 * Returns the values passed in parameters if they exist otherwise
 * returns the configuration if it exists
 * otherwise returns the default configuration.
 *
 * @param array|null $options
 *
 * @return array Returns the actual dump configuration.
 */
function setDumpConfig(array $options = null) : array
{
  // We ensure us that there are values set to the dump keys
  AllConfig::$debugConfig = !isset(AllConfig::$debugConfig)
    ? OTRA_DUMP_CONFIGURATION
    : array_merge(OTRA_DUMP_CONFIGURATION, AllConfig::$debugConfig);

  // If there is no option, we returns the merged array we just done
  if ($options === null)
    return AllConfig::$debugConfig;

  // Stores the actual config
  $oldConfig = AllConfig::$debugConfig ?? OTRA_DUMP_CONFIGURATION;

  // for each OTRA dump key, we update its value according to the passed parameters
  foreach (OTRA_DUMP_ARRAY as $optionKey => $option)
  {
     // if the dump key exists in the configuration
    if (isset(AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[$optionKey]]))
    {
      AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[$optionKey]] =
        $options[$optionKey] ?? AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[$optionKey]];
    }
  }

  return $oldConfig;
}

/**
 * A nice dump function that takes as much parameters as we want to put.
 * Somewhat disables XDebug if some parameters are true.
 *
 * @param array $options    [0 => Affects the amount of array children and object's properties shown
 *                           1 => Affects the maximum string length shown
 *                           2 => Affects the array and object's depths shown]
 * @param array ...$params
 */

function dump(array $options = [], ... $params) : void
{
  $oldOtraDebugValues = setDumpConfig($options);
  call_user_func_array('cli' === PHP_SAPI ? 'dumpSmallCli' : 'dumpSmall', $params);
  AllConfig::$debugConfig = $oldOtraDebugValues;
}

/**
 * Begins a foldable div
 *
 * @param bool $margin
 */
function createFoldable(bool $margin = false) : void
{
  $uniqId = uniqid();
  ?><label for="<?= $uniqId ?>" class="otra-dump--foldable otra-dump--expand-icon<?=
    $margin ? ' otra-dump--margin-bottom': '' ?>">⇅</label><!--
   --><input type="checkbox" id="<?= $uniqId ?>" class="otra-dump--foldable-checkbox"/><!--
   --><div><?php
}

/**
 * @param int|string $paramType
 * @param            $param
 * @param bool       $notFirstDepth
 * @param int        $depth
 *
 * @throws ReflectionException
 */
function dumpArray($paramType, $param, bool $notFirstDepth, int $depth) : void
{
  $description = $paramType . ' (' . count($param) . ') ';

  // If we have reach the depth limit, we exit this function
  if ($depth + 1 > AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DEPTH]])
  {
    echo '<br>', str_repeat(OTRA_DUMP_INDENT_STRING, $depth + 1), '<b>...</b><br>';

    return;
  }

  echo $description, OTRA_DUMP_END_TEXT_BLOCK;

  if ($notFirstDepth)
    createFoldable();
  else
    echo '<br>';

  $loopIndex = 0;

  foreach ($param as $paramItemKey => $paramItem)
  {
    // We show the rest of the variables only if we have not reach the 'maxChildren' limit.
    if (AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_CHILDREN]] < $loopIndex
      && AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_CHILDREN]] !== -1)
    {
      echo OTRA_DUMP_TEXT_BLOCK, '...', OTRA_DUMP_END_TEXT_BLOCK, '<br';
      break;
    }

    analyseVar($paramItemKey, $paramItem, $depth + 1, true,);
    ++$loopIndex;
  }

  if ($notFirstDepth)
    echo '</div><br>';
}

/**
* @param $param
* @param bool $notFirstDepth
* @param int $depth
*
* @throws ReflectionException
 */
function dumpObject($param, bool $notFirstDepth, int $depth) : void
{
  $className = get_class($param);
  $reflectedClass = new ReflectionClass($className);
  $classInterfaces = $reflectedClass->getInterfaceNames();
  $parentClass = $reflectedClass->getParentClass();
  $description = 'object (' . count((array) $param) . ') ' .
    ($reflectedClass->isAbstract() ? 'abstract ': '') .
    ($reflectedClass->isFinal() ? 'final ': '') . $className;

  if ($parentClass !== false)
    $description .= ' extends ' . $parentClass->getName();

  if (!empty($classInterfaces))
    $description .= ' implements ' . implode(',', $classInterfaces);

  echo $description, OTRA_DUMP_END_TEXT_BLOCK;

  if ($notFirstDepth)
    createFoldable();
  else
    echo '<br>';

  // If we have reach the depth limit, we exit this function
  if ($depth + 1 > AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DEPTH]])
  {
    echo str_repeat(OTRA_DUMP_INDENT_STRING, $depth + 1), '<b>...</b><br>';

    return;
  }

  foreach ((new ReflectionClass($className))->getProperties() as $variable)
  {
    analyseObjectVar($className, $param, $variable, $depth + 1);
  }
}

/**
 * @param string $className
 * @param object $param
 * @param ReflectionProperty $property
 * @param int $depth
 *
 * @throws ReflectionException
*/
function analyseObjectVar(
  string $className,
  object $param,
  ReflectionProperty $property,
  int $depth
)
{
  $propertyName = $property->getName();
  $isPublicProperty = $property->isPublic();
  $visibilityMask = $property->isPublic()
    | $property->isProtected() << 1
    | $property->isPrivate() << 2;

  echo OTRA_DUMP_TEXT_BLOCK, str_repeat(OTRA_DUMP_INDENT_STRING, $depth + 1), ' ';

  echo '<b class="' . OTRA_DUMP_HELP_CLASS . '" title="' .
    OTRA_DUMP_VISIBILITIES[$visibilityMask][OTRA_DUMP_KEY_VISIBILITY] . '">' .
    OTRA_DUMP_VISIBILITIES[$visibilityMask][OTRA_DUMP_KEY_VISIBILITY_SYMBOL] . '</b>';

  echo $property->isStatic()
    ? '<u class="' . OTRA_DUMP_HELP_CLASS . '" title="static">' . $propertyName . '</u>'
    : $propertyName, ':';

  if (!$isPublicProperty)
    $property = removeFieldScopeProtection($className, $propertyName);

  $propertyValue = $property->isInitialized($param)
    ? $property->getValue($param)
    : null;

  $propertyType = gettype($propertyValue);

  switch($propertyType)
  {
    case 'integer' :
    case 'float' :
      echo $propertyType, ' => ', $propertyValue,  $property->getDocComment();
      break;
    case 'string' :
      echo $propertyType, ' => ';
      $lengthParam = strlen($propertyValue);

      if ($lengthParam > 50)
        echo '<br>';

      echo "'",
      (AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DATA]] === -1
        ? $propertyValue
        : substr(
          $propertyValue,
          0,
          AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DATA]]
        )),
      "'";

      if ($lengthParam > AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DATA]])
        echo '<b>(cut)</b>';

      echo ' (', $lengthParam, ') ', $property->getDocComment();
      break;
    case 'array' : dumpArray(
        $propertyType,
        $propertyValue,
        ($depth !== -1),
        $depth
      );
      break;
    case 'NULL' : echo '<b>null</b>'; break;
    case 'object' :
      dumpObject(
        $param,
        ($depth !== -1),
        $depth
      );
      break;
  }

  echo OTRA_DUMP_END_TEXT_BLOCK;

  if ($propertyType !== 'array')
    echo '<br>';

  if (!$isPublicProperty)
    restoreFieldScopeProtection($className, $propertyName);
}


/**
 * @param int|string $paramKey
 * @param mixed      $param
 * @param int        $depth
 * @param bool       $isArray
 *
 *@throws ReflectionException
*/
function analyseVar($paramKey, $param, int $depth, bool $isArray = false) : void
{
  $notFirstDepth = ($depth !== -1);
  $paramType = gettype($param);
  $padding = '';

  if ($notFirstDepth)
    $padding = str_repeat(OTRA_DUMP_INDENT_STRING, $depth + 1);

  echo OTRA_DUMP_TEXT_BLOCK;

  if ($isArray)
    echo $padding;

  // showing keys
  echo (gettype($paramKey) !== 'string' ? $paramKey : '\'' . $paramKey . '\''), ' => ';

  // showing values
  switch($paramType)
  {
    case 'array' :
      dumpArray($paramType, $param, $notFirstDepth, $depth);
      break;
    case 'integer' :
    case 'float' :
      echo $param, '</span>', OTRA_DUMP_END_TEXT_BLOCK, '<br>';
      break;
    case 'NULL' : echo '<b>null</b><br>'; break;
    case 'object' :
      dumpObject($param, $notFirstDepth, $depth);
      break;

    case 'string' :
      $stringToShow = (AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DATA]] === -1
        ? $param
        : substr(
          $param,
          0,
          AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DATA]]
        ));
      $lengthParam = mb_strlen($param);
      echo $paramType, ' (', $lengthParam, ')';

      // If the string is too long, we begin it at the next line
      if ($lengthParam > 50)
        echo '<br>', $padding;

      echo ' \'', htmlspecialchars($stringToShow), '\'';

      if ($lengthParam > AllConfig::$debugConfig[OTRA_DUMP_ARRAY_KEY[OTRA_DUMP_KEY_MAX_DATA]])
        echo '<b>(cut)</b>';

      echo OTRA_DUMP_END_TEXT_BLOCK, '<br>';
      break;

    default:
      echo $paramType, $param, OTRA_DUMP_END_TEXT_BLOCK;
      break;
  }

  echo OTRA_DUMP_END_TEXT_BLOCK;
}

/**
 * A nice dump function that takes as much parameters as we want to put.
 *
 * @param mixed $params
 *
 *@throws ReflectionException
*/
function dumpSmall(...$params) : void
{
  $secondTrace = debug_backtrace()[1];
  $sourceFile = $secondTrace['file'];
  $sourceLine = $secondTrace['line'];
  require_once CORE_PATH . 'tools/removeFieldProtection.php';
  require_once CORE_PATH . 'tools/getSourceFromFile.php';
  ?><link rel="stylesheet" href="<?= CORE_CSS_PATH ?>otraDump.css"/>
    <div class="otra-dump">
      <span class="otra-dump--intro">
        <?= 'OTRA DUMP - ' . $sourceFile . ':' . $sourceLine ?>
      </span><?php createFoldable(true); ?>
      <pre class="otra-dump--string"><!--
       --><b class="otra--code--container"><mark class="otra--code--container-highlight"><?= getSourceFromFile($sourceFile, $sourceLine, 5, 2) ?></mark></b></pre>
      </div>
      <pre class="otra-dump--string">
<br><?php
      foreach ($params as $paramKey => $param)
      {
        analyseVar($paramKey, $param, -1, is_array($param));
      }
      ?></pre>
    </div><?php
}

/**
 * @param mixed ...$params
 */
function dumpSmallCli(...$params)
{
  $secondTrace = debug_backtrace()[1];
  echo 'OTRA DUMP - ' . $secondTrace['file'] . ':' . $secondTrace['line'] . PHP_EOL;

  foreach ($params as $param)
  {
    var_dump($param);
  }
}
