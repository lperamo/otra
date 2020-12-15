<?php
declare(strict_types=1);

define('OTRA_KEY_XDEBUG_MAX_CHILDREN', 0);
define('OTRA_KEY_XDEBUG_MAX_DATA', 1);
define('OTRA_KEY_XDEBUG_MAX_DEPTH', 2);

define('XDEBUG_ARRAY', [
  'xdebug.var_display_max_children',
  'xdebug.var_display_max_data',
  'xdebug.var_display_max_depth'
]);

define('OTRA_TD_OPENING_TAG', '<td>');
define('OTRA_TD_ENDING_TAG', '</td>');
define('OTRA_TR_ENDING_TAG', '</tr>');

/**
 * @param string $message
 */
function lg(string $message) : void
{
  require_once CORE_PATH . 'Logger.php';
  otra\Logger::logTo($message, 'trace');
}

/**
 * Set the XDebug key to '-1' and returns the old value.
 *
 * @param string $xDebugKey
 *
 * @return string
 */
function updateXDebugValue(string $xDebugKey) : string
{
  $oldXDebugKey = ini_get($xDebugKey);
  ini_set($xDebugKey,'-1');

  return $oldXDebugKey;
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
  $oldXDebugValues = [];

  foreach ($options as $numKey => $option)
  {
    if ($option)
      $oldXDebugValues[$numKey] = updateXDebugValue(XDEBUG_ARRAY[$numKey]);
  }

  unset($numKey, $option);

  call_user_func_array('cli' === PHP_SAPI ? 'dumpSmallCli' : 'dumpSmall', $params);

  foreach ($oldXDebugValues as $numKey => $option)
  {
    ini_set(XDEBUG_ARRAY[$numKey], $option);
  }
}

/**
 * Returns file and line of the caller for debugging purposes.
 *
 * @return string
 */
function getCaller() : string
{
  $secondTrace = debug_backtrace()[1];

  return $secondTrace['file'] . ':' . $secondTrace['line'];
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

  if ($notFirstDepth)
  {
    echo $description, OTRA_DUMP_END_TEXT_BLOCK;
    createFoldable();
  } else
    echo $description, OTRA_DUMP_END_TEXT_BLOCK, '<br>';

  foreach ($param as $paramItemKey => $paramItem)
  {
    analyseVar(
      $paramItemKey,
      $paramItem,
      $depth + 1,
      true,
    );
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

  if ($notFirstDepth)
  {
    echo $description, OTRA_DUMP_END_TEXT_BLOCK, ' ';
    createFoldable();
  } else
    echo $description, OTRA_DUMP_END_TEXT_BLOCK, '<br>';


  $properties = [];
  $className = get_class($param);
  $reflectedClass = new ReflectionClass($className);

  foreach ($reflectedClass->getProperties() as $variable)
  {
    analyseObjectVar(
      $className,
      $param,
      $variable,
      $depth + 1
    );
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
  $isPublicProperty = false;

  // Determining the visibility...
  switch (true)
  {
    case $property->isPublic() :
      $visibility = '<b class="' . OTRA_DUMP_HELP_CLASS . '" title="public">+</b>';
      $isPublicProperty = true;
      break;
    case $property->isProtected() :
      $visibility = '<b class="' . OTRA_DUMP_HELP_CLASS . '" title="protected">#</b>';
      break;
    case $property->isPrivate() :
      $visibility = '<b class="' . OTRA_DUMP_HELP_CLASS . '" title="private">-</b>';
      break;
    default :
      $visibility = '';
  }

  echo OTRA_DUMP_TEXT_BLOCK, str_repeat(OTRA_DUMP_INDENT_STRING, $depth + 1), ' ', $visibility,
    $property->isStatic()
    ? '<u class="' . OTRA_DUMP_HELP_CLASS . '" title="static">' . $propertyName . '</u>'
    : $propertyName, ':';


  if (!$isPublicProperty)
  {
    $unprotectedProperty = removeFieldScopeProtection($className, $propertyName);
    $propertyValue = $unprotectedProperty->isInitialized($param)
      ? removeFieldScopeProtection($className, $propertyName)->getValue($param)
      : null;
  } else {
    $propertyValue = ($property->isInitialized($param))
      ? $property->getValue($param)
      : null;
  }

  $propertyType = gettype($propertyValue);

  switch($propertyType)
  {
    case 'integer' :
    case 'float' :
      echo $propertyType, ' => ', $propertyValue,  $property->getDocComment();
      break;
    case 'string' :
      echo $propertyType, ' => ',
       "'" . $propertyValue . "'",
        $property->getDocComment();
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
  switch(gettype($paramKey))
  {
    case 'string' :
      echo '\'', $paramKey, '\'';
      break;
    default : echo $paramKey; break;
  }

  echo ' => ';

  // showing values
  switch($paramType)
  {
    case 'array' :
      dumpArray(
        $paramType,
        $param,
        $notFirstDepth,
        $depth
      );

      break;
    case 'integer' :
    case 'float' :
      echo $param, '</span>', OTRA_DUMP_END_TEXT_BLOCK, '<br>';
      break;

    case 'NULL' : echo '<b>null</b><br>'; break;
    case 'object' :
      dumpObject(
        $param,
        $notFirstDepth,
        $depth
      );

      break;

    case 'string' :
      $lengthParam = mb_strlen($param);
      echo $paramType, ' (', $lengthParam, ')';

      // If the string is too long, we begin it at the next line
      if ($lengthParam > 50)
        echo '<br>', $padding;

      echo ' \'', htmlspecialchars($param), '\'', OTRA_DUMP_END_TEXT_BLOCK, '<br>';
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

  if (!defined('OTRA_DUMP_TEXT_BLOCK'))
  {
    define('OTRA_DUMP_TEXT_BLOCK', '<span class="otra-dump--value">');
    define('OTRA_DUMP_END_TEXT_BLOCK', '</span>');
    define('OTRA_DUMP_INDENT_STRING', '│ ');
    define('OTRA_DUMP_HELP_CLASS', 'otra-dump--help');
  }

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
