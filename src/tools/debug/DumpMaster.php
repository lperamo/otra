<?php
declare(strict_types=1);

namespace otra\tools\debug;

use otra\config\AllConfig;
use ReflectionClass;
use ReflectionException;
use const otra\cache\php\CORE_PATH;

/**
 * Class that provides things for both web and CLI sides of the dump function.
 *
 * @author Lionel Péramo
 * @package otra\tools\debug
 */
abstract class DumpMaster
{
  protected const
    // 'How much' constants
    OTRA_DUMP_ARRAY = [128, 512, 3],
    OTRA_DUMP_ARRAY_KEY = ['maxChildren', 'maxData', 'maxDepth'],
    OTRA_DUMP_KEY_MAX_CHILDREN = 0,
    OTRA_DUMP_KEY_MAX_DATA = 1,
    OTRA_DUMP_KEY_MAX_DEPTH = 2,
    OTRA_DUMP_MAX_CHILDREN = 128,
    OTRA_DUMP_MAX_DATA = 512,
    OTRA_DUMP_MAX_DEPTH = 3,
    OTRA_DUMP_CONFIGURATION = [
      self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_CHILDREN] => self::OTRA_DUMP_MAX_CHILDREN,
      self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA] => self::OTRA_DUMP_MAX_DATA,
      self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH] => self::OTRA_DUMP_MAX_DEPTH,
    ],

    // visibilities constants
    OTRA_DUMP_VISIBILITY_PUBLIC = 1,
    OTRA_DUMP_VISIBILITY_PROTECTED = 2,
    OTRA_DUMP_VISIBILITY_PRIVATE = 4,
    OTRA_DUMP_KEY_VISIBILITY = 0,
    OTRA_DUMP_KEY_VISIBILITY_SYMBOL = 1,
    OTRA_DUMP_VISIBILITIES = [
      self::OTRA_DUMP_VISIBILITY_PUBLIC => ['public', '+'],
      self::OTRA_DUMP_VISIBILITY_PROTECTED => ['protected', '#'],
      self::OTRA_DUMP_VISIBILITY_PRIVATE => ['private', '-']
    ],

    // Display constants
    OTRA_DUMP_INDENT_STRING = '│ ',

  // Initial depth
  OTRA_DUMP_INITIAL_DEPTH = -1,

  // Types
  OTRA_DUMP_TYPE_STRING = 'string',
  OTRA_DUMP_TYPE_ARRAY = 'array';

  /**
   * Sets the dump configuration to the defaults if the dump configuration is not set.
   * Returns the values passed in parameters if they exist otherwise
   * returns the configuration if it exists
   * otherwise returns the default configuration.
   *
   * @param ?int[] $options
   *
   * @return array{
   *   maxChildren ?: int,
   *   maxData ?:int,
   *   maxDepth ?: int,
   *   autoLaunch ?: bool,
   *   barPosition ?: string
   * } Returns the actual dump configuration.
   */
  public static function setDumpConfig(array $options = null) : array
  {
    // We ensure us that there are values set to the dump keys
    AllConfig::$debugConfig = !isset(AllConfig::$debugConfig)
      ? self::OTRA_DUMP_CONFIGURATION
      : array_merge(self::OTRA_DUMP_CONFIGURATION, AllConfig::$debugConfig);

    // If there is no option, we return the merged array we've just done
    if ($options === null)
      return AllConfig::$debugConfig;

    // Stores the actual config
    $oldConfig = AllConfig::$debugConfig ?? self::OTRA_DUMP_CONFIGURATION;

    // for each OTRA dump key, we update its value according to the passed parameters
    foreach (self::OTRA_DUMP_ARRAY as $optionKey => $option)
    {
      // if the dump key exists in the configuration
      if (isset(AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[$optionKey]]))
      {
        AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[$optionKey]] =
          $options[$optionKey] ?? AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[$optionKey]];

        // Handles the -1 value
        if (AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[$optionKey]] === -1)
          AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[$optionKey]] = 100000;
      }
    }

    return $oldConfig;
  }

  /**
   * @param object $param
   *
   * @throws ReflectionException
   * @return string[]
   */
  protected static function getClassDescription(object $param) : array
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

    return [$className, $description];
  }

  /**
   * @param mixed ...$params
   */
  public static function dump(... $params) : void
  {
    $debugBacktrace = debug_backtrace();

    // We check if we come from the 'dump' function shortcut or the 'paramDump' function
    $secondTrace = ($debugBacktrace[3]['file'] === CORE_PATH . 'tools/debug/dump.php')
      ? $debugBacktrace[4]
      : $debugBacktrace[3];

    require_once CORE_PATH . 'tools/removeFieldProtection.php';
    require_once CORE_PATH . 'tools/getSourceFromFile.php';

    ob_start();

    foreach ($params as $paramKey => $param)
    {
      static::analyseVar($paramKey, $param, self::OTRA_DUMP_INITIAL_DEPTH, is_array($param));
    }

    static::dumpCallback($secondTrace['file'], $secondTrace['line'], ob_get_clean());
  }

  /**
   * @param string $className
   * @param mixed  $param
   *
   * @throws ReflectionException
   * @return array{0: ReflectionProperty[], 1: mixed} [$properties, $param]
   */
  public static function getPropertiesViaReflection(string $className, mixed $param) : array
  {
    $properties = (new ReflectionClass($className))->getProperties();

    // We need a fake class as DateTime does not handle reflection :(
    if ($className === 'DateTime')
    {
      $param = new FakeDateTime($param);
      $properties = (new ReflectionClass(FakeDateTime::class))->getProperties();
    }

    return [$properties, $param];
  }
}
