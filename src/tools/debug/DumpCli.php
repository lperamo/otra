<?php
declare(strict_types=1);

namespace otra;

use config\AllConfig;
use ReflectionClass, ReflectionException, ReflectionProperty;

/**
 * Class that handles the dump mechanism, on web and CLI side.
 *
 * @package otra
 */
class DumpCli extends DumpMaster
{
  // Display constants
//  private const
//    OTRA_DUMP_TEXT_BLOCK = '<span class="otra-dump--value">',
//    OTRA_DUMP_END_TEXT_BLOCK = '</span>',
    //      OTRA_DUMP_CLI_TEXT_BLOCK = '',
    //      OTRA_DUMP_CLI_END_TEXT_BLOCK = '',
//    OTRA_DUMP_HELP_CLASS = 'otra-dump--help';

  /**
   * @param int|string $paramType
   * @param            $param
   * @param bool       $notFirstDepth
   * @param int        $depth
   *
   * @throws ReflectionException
   */
  private static function dumpArray($paramType, $param, bool $notFirstDepth, int $depth) : void
  {
    $description = $paramType . ' (' . count($param) . ') ';

    // If we have reach the depth limit, we exit this function
    if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
    {
      echo PHP_EOL, str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1), ADD_BOLD, '...',
        REMOVE_BOLD_INTENSITY, PHP_EOL;

      return;
    }

    echo $description;

    if (!$notFirstDepth)
      echo PHP_EOL;

    $loopIndex = 0;

    foreach ($param as $paramItemKey => $paramItem)
    {
      // We show the rest of the variables only if we have not reach the 'maxChildren' limit.
      if (AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_CHILDREN]] < $loopIndex
        && AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_CHILDREN]] !== -1)
      {
        echo '...', PHP_EOL;
        break;
      }

      self::analyseVar($paramItemKey, $paramItem, $depth + 1, true);
      ++$loopIndex;
    }
  }

  /**
   * @param      $param
   * @param bool $notFirstDepth
   * @param int  $depth
   *
   * @throws ReflectionException
   */
  private static function dumpObject($param, bool $notFirstDepth, int $depth) : void
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

    echo $description, PHP_EOL;

    // If we have reach the depth limit, we exit this function
    if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
    {
      echo str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1), ADD_BOLD, '...',
        REMOVE_BOLD_INTENSITY, PHP_EOL;

      return;
    }

    foreach ((new ReflectionClass($className))->getProperties() as $variable)
    {
      self::analyseObjectVar($className, $param, $variable, $depth + 1);
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

  private static function analyseObjectVar(
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

    echo str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1), ' ';
    echo ADD_BOLD . self::OTRA_DUMP_VISIBILITIES[$visibilityMask][self::OTRA_DUMP_KEY_VISIBILITY_SYMBOL]
      . REMOVE_BOLD_INTENSITY;

    echo ($property->isStatic()
      ? ADD_UNDERLINE . $propertyName . REMOVE_UNDERLINE
      : $propertyName),
      ':';

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
        (AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]] === -1
          ? $propertyValue
          : substr(
            $propertyValue,
            0,
            AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]]
          )),
        "'";

        if ($lengthParam > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]])
          echo '<b>(cut)</b>';

        echo ' (', $lengthParam, ') ', $property->getDocComment();
        break;
      case 'array' : self::dumpArray(
        $propertyType,
        $propertyValue,
        ($depth !== -1),
        $depth
      );
        break;
      case 'NULL' : echo '<b>null</b>'; break;
      case 'object' :
        self::dumpObject(
          $param,
          ($depth !== -1),
          $depth
        );
        break;
    }

    if ($propertyType !== 'array')
      echo PHP_EOL;

    if (!$isPublicProperty)
      restoreFieldScopeProtection($className, $propertyName);
  }

  /**
   * @param int|string $paramKey
   * @param mixed      $param
   * @param int        $depth
   * @param bool       $isArray
   *
   * @throws ReflectionException
   */
  public static function analyseVar($paramKey, $param, int $depth, bool $isArray = false) : void
  {
    $notFirstDepth = ($depth !== -1);
    $paramType = gettype($param);
    $padding = '';

    if ($notFirstDepth)
      $padding = str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1);

    if ($isArray)
      echo $padding;

    // showing keys
    echo (gettype($paramKey) !== 'string' ? $paramKey : '\'' . $paramKey . '\''), ' => ';

    // showing values
    switch($paramType)
    {
      case 'array' :
        self::dumpArray($paramType, $param, $notFirstDepth, $depth);
        break;
      case 'integer' :
      case 'float' :
        echo $param, PHP_EOL;
        break;
      case 'NULL' : echo ADD_BOLD, 'null', REMOVE_BOLD_INTENSITY, PHP_EOL; break;
      case 'object' :
        self::dumpObject($param, $notFirstDepth, $depth);
        break;

      case 'string' :
        $stringToShow = (AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]] === -1
          ? $param
          : substr(
            $param,
            0,
            AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]]
          ));
        $lengthParam = mb_strlen($param);
        echo $paramType, ' (', $lengthParam, ')';

        // If the string is too long, we begin it at the next line
        if ($lengthParam > 50)
          echo PHP_EOL, $padding;

        echo ' \'', htmlspecialchars($stringToShow), '\'';

        if ($lengthParam > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]])
          echo ADD_BOLD, '(cut)', REMOVE_BOLD_INTENSITY;

        echo PHP_EOL;
        break;

      default:
        echo $paramType, $param;
        break;
    }
  }

  /**
   * @param mixed ...$params
   *
   * @throws ReflectionException
   */
  public static function dump(...$params)
  {
    $secondTrace = debug_backtrace()[2];

    $sourceFile = $secondTrace['file'];
    $sourceLine = $secondTrace['line'];
    require_once CORE_PATH . 'tools/removeFieldProtection.php';
    require_once CORE_PATH . 'tools/getSourceFromFile.php';

    echo CLI_BLUE, 'OTRA DUMP - ', $sourceFile, ':', $sourceLine, END_COLOR, PHP_EOL, PHP_EOL;
    echo getSourceFromFileCli($sourceFile, $sourceLine), PHP_EOL;

    foreach ($params as $paramKey => $param)
    {
      self::analyseVar($paramKey, $param, self::OTRA_DUMP_INITIAL_DEPTH, is_array($param));
    }
  }
}
