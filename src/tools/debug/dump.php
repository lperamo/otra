<?php
declare(strict_types=1);

namespace otra {
  use config\AllConfig;
  use ReflectionClass, ReflectionException, ReflectionProperty;

  /**
   * Class that handles the dump mechanism, on web and CLI side.
   *
   * @package otra
   */
  class Dump {
    // 'How much' constants
    private const OTRA_DUMP_ARRAY = [128, 512, 3],
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
      OTRA_DUMP_TEXT_BLOCK = '<span class="otra-dump--value">',
      OTRA_DUMP_END_TEXT_BLOCK = '</span>',
  //      OTRA_DUMP_CLI_TEXT_BLOCK = '',
  //      OTRA_DUMP_CLI_END_TEXT_BLOCK = '',
      OTRA_DUMP_INDENT_STRING = '│ ',
      OTRA_DUMP_HELP_CLASS = 'otra-dump--help';

      // Initial depth
      public const OTRA_DUMP_INITIAL_DEPTH = -1;

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

    /**
     * Begins a foldable div
     *
     * @param bool $margin
     */
    public static function createFoldable(bool $margin = false) : void
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
    private static function dumpArray($paramType, $param, bool $notFirstDepth, int $depth) : void
    {
      $description = $paramType . ' (' . count($param) . ') ';

      // If we have reach the depth limit, we exit this function
      if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
      {
        echo '<br>', str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1), '<b>...</b><br>';

        return;
      }

      echo $description, self::OTRA_DUMP_END_TEXT_BLOCK;

      if ($notFirstDepth)
        self::createFoldable();
      else
        echo '<br>';

      $loopIndex = 0;

      foreach ($param as $paramItemKey => $paramItem)
      {
        // We show the rest of the variables only if we have not reach the 'maxChildren' limit.
        if (AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_CHILDREN]] < $loopIndex
          && AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_CHILDREN]] !== -1)
        {
          echo self::OTRA_DUMP_TEXT_BLOCK, '...', self::OTRA_DUMP_END_TEXT_BLOCK, '<br';
          break;
        }

        self::analyseVar($paramItemKey, $paramItem, $depth + 1, true);
        ++$loopIndex;
      }

      if ($notFirstDepth)
        echo '</div><br>';
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

      echo $description, self::OTRA_DUMP_END_TEXT_BLOCK;

      if ($notFirstDepth)
        self::createFoldable();
      else
        echo '<br>';

      // If we have reach the depth limit, we exit this function
      if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
      {
        echo str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1), '<b>...</b><br>';

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

      echo self::OTRA_DUMP_TEXT_BLOCK, str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1), ' ';

      echo '<b class="' . self::OTRA_DUMP_HELP_CLASS . '" title="' .
        self::OTRA_DUMP_VISIBILITIES[$visibilityMask][self::OTRA_DUMP_KEY_VISIBILITY] . '">' .
        self::OTRA_DUMP_VISIBILITIES[$visibilityMask][self::OTRA_DUMP_KEY_VISIBILITY_SYMBOL] . '</b>';

      echo $property->isStatic()
        ? '<u class="' . self::OTRA_DUMP_HELP_CLASS . '" title="static">' . $propertyName . '</u>'
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

      echo self::OTRA_DUMP_END_TEXT_BLOCK;

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
     * @throws ReflectionException
     */
    public static function analyseVar($paramKey, $param, int $depth, bool $isArray = false) : void
    {
      $notFirstDepth = ($depth !== -1);
      $paramType = gettype($param);
      $padding = '';

      if ($notFirstDepth)
        $padding = str_repeat(self::OTRA_DUMP_INDENT_STRING, $depth + 1);

      echo self::OTRA_DUMP_TEXT_BLOCK;

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
          echo $param, '</span>', self::OTRA_DUMP_END_TEXT_BLOCK, '<br>';
          break;
        case 'NULL' : echo '<b>null</b><br>'; break;
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
            echo '<br>', $padding;

          echo ' \'', htmlspecialchars($stringToShow), '\'';

          if ($lengthParam > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]])
            echo '<b>(cut)</b>';

          echo self::OTRA_DUMP_END_TEXT_BLOCK, '<br>';
          break;

        default:
          echo $paramType, $param, self::OTRA_DUMP_END_TEXT_BLOCK;
          break;
      }

      echo self::OTRA_DUMP_END_TEXT_BLOCK;
    }
  }
}

namespace {
  use config\AllConfig;
  use otra\Dump;

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
    $oldOtraDebugValues = Dump::setDumpConfig($options);
    call_user_func_array('cli' === PHP_SAPI ? 'dumpSmallCli' : 'dumpSmall', $params);
    AllConfig::$debugConfig = $oldOtraDebugValues;
  }

  /**
   * A nice dump function that takes as much parameters as we want to put.
   *
   * @param mixed $params
   *
   * @throws ReflectionException
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
        </span><?php Dump::createFoldable(true); ?>
        <pre class="otra-dump--string"><!--
       --><b class="otra--code--container"><mark class="otra--code--container-highlight"><?=
          getSourceFromFile($sourceFile, $sourceLine, 2)
        ?></mark></b></pre>
        </div>
        <pre class="otra-dump--string">
  <br><?php
        foreach ($params as $paramKey => $param)
        {
          Dump::analyseVar($paramKey, $param, Dump::OTRA_DUMP_INITIAL_DEPTH, is_array($param));
        }
        ?></pre>
      </div><?php
  }

  /**
   * @param mixed ...$params
   *
   * @throws ReflectionException
   */
  function dumpSmallCli(...$params)
  {
    $secondTrace = debug_backtrace()[1];

    $sourceFile = $secondTrace['file'];
    $sourceLine = $secondTrace['line'];
    require_once CORE_PATH . 'tools/removeFieldProtection.php';
    require_once CORE_PATH . 'tools/getSourceFromFile.php';

    echo CLI_BLUE, 'OTRA DUMP - ', $sourceFile, ':', $sourceLine, END_COLOR, PHP_EOL, PHP_EOL;
    echo getSourceFromFileCli($sourceFile, $sourceLine), PHP_EOL;

    foreach ($params as $paramKey => $param)
    {
      Dump::analyseVar($paramKey, $param, Dump::OTRA_DUMP_INITIAL_DEPTH, is_array($param));
    }
  }
}
