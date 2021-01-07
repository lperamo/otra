<?php
declare(strict_types=1);
namespace otra;
use config\AllConfig;

/**
 * Class that provides things for both web and CLI sides of the dump function.
 *
 * @package otra
 */
abstract class DumpMaster {
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
    OTRA_DUMP_INDENT_STRING = '│ ';

  // Initial depth
  protected const OTRA_DUMP_INITIAL_DEPTH = -1;

  /**
   * Sets the dump configuration to the defaults if the dump configuration is not set.
   * Returns the values passed in parameters if they exist otherwise
   * returns the configuration if it exists
   * otherwise returns the default configuration.
   *
   * @param array|null $options
   *
   * @return array Returns the actual dump configuration.
   */
  public static function setDumpConfig(array $options = null) : array
  {
    // We ensure us that there are values set to the dump keys
    AllConfig::$debugConfig = !isset(AllConfig::$debugConfig)
      ? self::OTRA_DUMP_CONFIGURATION
      : array_merge(self::OTRA_DUMP_CONFIGURATION, AllConfig::$debugConfig);

    // If there is no option, we returns the merged array we just done
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
      }
    }

    return $oldConfig;
  }
}
