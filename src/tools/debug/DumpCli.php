<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\tools\debug
 */

namespace otra;

use config\AllConfig;
use JetBrains\PhpStorm\Pure;
use ReflectionClass, ReflectionException, ReflectionProperty;

if (!defined('OTRA_DUMP_INDENT_COLORS'))
{
  define(
    'OTRA_DUMP_INDENT_COLORS',
    [
      'CLI_BOLD_LIGHT_BLUE',
      'CLI_BOLD_LIGHT_RED',
      'CLI_LIGHT_GREEN',
      'CLI_BOLD_LIGHT_CYAN',
      'CLI_BOLD_VIOLET'
    ]
  );
  define('OTRA_DUMP_INDENT_COLORS_COUNT', count(OTRA_DUMP_INDENT_COLORS));
}

/**
 * Class that handles the dump mechanism, on web and CLI side.
 *
 * @package otra
 */
class DumpCli extends DumpMaster
{
  /**
   * @param int $depth
   *
   * @return string
   */
  #[Pure] private static function indentColors(int $depth) : string
  {
    $content = '';

    for ($index = 0; $index < $depth; ++$index)
    {
      $content .= constant(OTRA_DUMP_INDENT_COLORS[$index % OTRA_DUMP_INDENT_COLORS_COUNT]) . self::OTRA_DUMP_INDENT_STRING;
    }

    return $content . END_COLOR;
  }

  /**
   * @param int|string $paramType
   * @param            $param
   * @param int        $depth
   *
   * @throws ReflectionException
   */
  private static function dumpArray(int|string $paramType, $param, int $depth) : void
  {
    $description = $paramType . ' (' . count($param) . ') ';

    // If we have reach the depth limit, we exit this function
    if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
    {
      echo PHP_EOL, self::indentColors($depth), ADD_BOLD, '...', REMOVE_BOLD_INTENSITY, PHP_EOL;

      return;
    }

    echo $description, PHP_EOL;

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
   * @param int  $depth
   *
   * @throws ReflectionException
   */
  private static function dumpObject($param, int $depth) : void
  {
    [$className, $description] = parent::getClassDescription($param);
    echo $description, PHP_EOL;

    // If we have reach the depth limit, we exit this function
    if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
    {
      echo self::indentColors($depth), ADD_BOLD, '...', REMOVE_BOLD_INTENSITY, PHP_EOL;

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

    echo self::indentColors($depth), ' ', ADD_BOLD .
      self::OTRA_DUMP_VISIBILITIES[$visibilityMask][self::OTRA_DUMP_KEY_VISIBILITY_SYMBOL] . REMOVE_BOLD_INTENSITY;

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
      case 'boolean' :
        echo $propertyType, ' => ', $propertyValue ? ' true' : ' false',  $property->getDocComment();
        break;
      case 'integer' :
      case 'float' :
        echo $propertyType, ' => ', $propertyValue,  $property->getDocComment();
        break;
      case DumpMaster::OTRA_DUMP_TYPE_STRING :
        echo $propertyType, ' => ';
        $lengthParam = strlen($propertyValue);

        if ($lengthParam > 50)
          echo PHP_EOL;

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
          echo ADD_BOLD, '(cut)', REMOVE_BOLD_INTENSITY;

        echo ' (', $lengthParam, ') ', $property->getDocComment();
        break;
      case DumpMaster::OTRA_DUMP_TYPE_ARRAY : self::dumpArray(
        $propertyType,
        $propertyValue,
        $depth
      );
        break;
      case 'NULL' : echo ADD_BOLD, 'null', REMOVE_BOLD_INTENSITY; break;
      case 'object' :
        self::dumpObject(
          $param,
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
  public static function analyseVar(int|string $paramKey, mixed $param, int $depth, bool $isArray = false) : void
  {
    $notFirstDepth = ($depth !== -1);
    $paramType = gettype($param);
    $padding = '';

    if ($notFirstDepth)
      $padding = self::indentColors($depth);

    if ($isArray)
      echo $padding;

    // showing keys
    echo (gettype($paramKey) !== DumpMaster::OTRA_DUMP_TYPE_STRING
      ? $paramKey
      : '\'' . $paramKey . '\''
      ), ' => ';

    // showing values
    switch($paramType)
    {
      case DumpMaster::OTRA_DUMP_TYPE_ARRAY :
        self::dumpArray($paramType, $param, $depth);
        break;
      case 'boolean' :
        echo $param ? ' true' : ' false', PHP_EOL;
        break;
      case 'integer' :
      case 'float' :
        echo $param, PHP_EOL;
        break;
      case 'NULL' : echo ADD_BOLD, 'null', REMOVE_BOLD_INTENSITY, PHP_EOL; break;
      case 'object' :
        self::dumpObject($param, $depth);
        break;

      case DumpMaster::OTRA_DUMP_TYPE_STRING :
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
   * @param string $sourceFile
   * @param int    $sourceLine
   * @param string $content
   */
  protected static function dumpCallback(string $sourceFile, int $sourceLine, string $content)
  {
    echo CLI_BLUE, 'OTRA DUMP - ', $sourceFile, ':', $sourceLine, END_COLOR, PHP_EOL, PHP_EOL;
    echo getSourceFromFileCli($sourceFile, $sourceLine), PHP_EOL;
    echo $content;
  }

  /**
   * Calls the DumpMaster::dump that will use the 'dumpCallback' function.
   *
   * @param mixed $params
   */
  public static function dump(... $params)
  {
    parent::dump(... $params);
  }
}
