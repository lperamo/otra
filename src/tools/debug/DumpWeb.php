<?php
declare(strict_types=1);

namespace otra\tools\debug;

use Exception;
use JetBrains\PhpStorm\Pure;
use otra\config\AllConfig;
use ReflectionException, ReflectionProperty;
use const otra\cache\php\CORE_CSS_PATH;
use const otra\services\OTRA_KEY_STYLE_SRC_DIRECTIVE;
use function otra\services\getRandomNonceForCSP;
use function otra\tools\{getSourceFromFile,removeFieldScopeProtection,restoreFieldScopeProtection};

const OTRA_DUMP_INDENT_COLORS = [
  'otra-dump--first',
  'otra-dump--second',
  'otra-dump--third',
  'otra-dump--fourth',
  'otra-dump--fifth'
];
define(__NAMESPACE__ . '\\OTRA_DUMP_INDENT_COLORS_COUNT', count(OTRA_DUMP_INDENT_COLORS));

/**
 * Class that handles the dump mechanism, on web and CLI side.
 *
 * @author Lionel Péramo
 * @package otra
 */
abstract class DumpWeb extends DumpMaster {
  // Display constants
  private const
    OTRA_DUMP_TEXT_BLOCK = '<span class="otra-dump--value">',
    ENDING_SPAN = '</span>',
    OTRA_DUMP_HELP_CLASS = 'otra-dump--help',
    BR = '<br>',
    RIGHT_ARROW = ' => ';

  public const OTRA_DUMP_INDENT_STRING = '│ ';
  private static bool $cssNotAdded = true;

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
      $content .= '<span class="' . OTRA_DUMP_INDENT_COLORS[$index % OTRA_DUMP_INDENT_COLORS_COUNT] . '">' . self::OTRA_DUMP_INDENT_STRING . self::ENDING_SPAN;
    }

    return $content;
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
   * @param mixed      $param
   * @param bool       $notFirstDepth
   * @param int        $depth
   *
   * @throws ReflectionException
   */
  private static function dumpArray(int|string $paramType, mixed $param, bool $notFirstDepth, int $depth) : void
  {
    $description = $paramType . ' (' . count($param) . ') ';

    // If we have reach the depth limit, we exit this function
    if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
    {
      echo self::BR, self::indentColors($depth + 1), '<b>...</b><br>';

      return;
    }

    echo $description, self::ENDING_SPAN;

    if ($notFirstDepth)
      self::createFoldable();
    else
      echo self::BR;

    $loopIndex = 0;

    foreach ($param as $paramItemKey => $paramItem)
    {
      // We show the rest of the variables only if we have not reached the 'maxChildren' limit.
      if (AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_CHILDREN]] < $loopIndex
        && AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_CHILDREN]] !== -1)
      {
        echo self::OTRA_DUMP_TEXT_BLOCK, '...', self::ENDING_SPAN, '<br';
        break;
      }

      self::analyseVar($paramItemKey, $paramItem, $depth + 1, true);
      ++$loopIndex;
    }

    // ending foldable behaviour
    if ($notFirstDepth)
    {
      ?></div><br><?php
    }
  }

  /**
   * @param mixed $param
   * @param bool  $notFirstDepth
   * @param int   $depth
   *
   * @throws ReflectionException
   */
  private static function dumpObject(mixed $param, bool $notFirstDepth, int $depth) : void
  {
    [$className, $description] = parent::getClassDescription($param);
    echo $description, self::ENDING_SPAN;

    if ($notFirstDepth)
      self::createFoldable();
    else
      echo self::BR;

    // If we have reach the depth limit, we exit this function
    if ($depth + 1 > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DEPTH]])
    {
      echo self::indentColors($depth + 1), '<b>...</b><br>';

      return;
    }

    [$properties, $param] = self::getPropertiesViaReflection($className, $param);

    foreach ($properties as $variable)
    {
      self::analyseObjectVar($className, $param, $variable, $depth + 1);
    }

    // ending foldable behaviour
    if ($notFirstDepth)
    {
      ?></div><br><?php
    }
  }

  /**
   * @param string             $className
   * @param object             $param
   * @param ReflectionProperty $property
   * @param int                $depth
   *
   * @throws ReflectionException
   */

  private static function analyseObjectVar(
    string $className,
    object $param,
    ReflectionProperty $property,
    int $depth
  ) : void
  {
    $reflectionClassName = $className !== 'DateTime' ? $className : FakeDateTime::class;
    $propertyName = $property->getName();
    $isPublicProperty = $property->isPublic();
    $visibilityMask = $isPublicProperty
      | $property->isProtected() << 1
      | $property->isPrivate() << 2;

    echo self::OTRA_DUMP_TEXT_BLOCK, self::indentColors($depth + 1), ' ';

    echo '<b class="' . self::OTRA_DUMP_HELP_CLASS . '" title="' .
      self::OTRA_DUMP_VISIBILITIES[$visibilityMask][self::OTRA_DUMP_KEY_VISIBILITY] . '">' .
      self::OTRA_DUMP_VISIBILITIES[$visibilityMask][self::OTRA_DUMP_KEY_VISIBILITY_SYMBOL] . '</b>';

    echo ($property->isStatic()
      ? '<u class="' . self::OTRA_DUMP_HELP_CLASS . '" title="static">' . $propertyName . '</u>'
      : $propertyName),
      ':';

    if (!$isPublicProperty)
      $property = removeFieldScopeProtection($reflectionClassName, $propertyName);

    $propertyValue = $property->isInitialized($param)
      ? $property->getValue($param)
      : null;

    $propertyType = gettype($propertyValue);

    switch($propertyType)
    {
      case 'boolean' :
        echo $propertyType, self::RIGHT_ARROW, $propertyValue ? 'true' : 'false', $property->getDocComment();
        break;
      case 'integer' :
      case 'double' :
        echo $propertyType, self::RIGHT_ARROW, $propertyValue,  $property->getDocComment();
        break;
      case DumpMaster::OTRA_DUMP_TYPE_STRING :
        echo $propertyType, self::RIGHT_ARROW;
        $lengthParam = strlen($propertyValue);

        if ($lengthParam > 50)
          echo self::BR;

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
      case DumpMaster::OTRA_DUMP_TYPE_ARRAY : self::dumpArray(
        $propertyType,
        $propertyValue,
        ($depth !== -1),
        $depth
      );
        break;
      case 'NULL' : echo '<b> null</b>'; break;
      case 'object' :
        self::dumpObject(
          $param,
          ($depth !== -1),
          $depth
        );
        break;
    }

    echo self::ENDING_SPAN;

    if ($propertyType !== DumpMaster::OTRA_DUMP_TYPE_ARRAY)
      echo self::BR;

    if (!$isPublicProperty)
      restoreFieldScopeProtection($reflectionClassName, $propertyName);
  }

  /**
   * @param int|string $paramKey
   * @param mixed      $param
   * @param int        $depth
   * @param bool       $isArray
   *
   * @throws ReflectionException
   * @throws Exception
   */
  public static function analyseVar(int|string $paramKey, mixed $param, int $depth, bool $isArray = false) : void
  {
    if (self::$cssNotAdded)
    {
      ?><style nonce="<?= getRandomNonceForCSP(OTRA_KEY_STYLE_SRC_DIRECTIVE) ?>">
        .otra-dump--first {
          color : #6496c8;
        }

        .otra-dump--second {
          color : #ff6464;
        }

        .otra-dump--third {
          color: #64c864;
        }

        .otra-dump-fourth {
          color: #64c8c8;
        }

        .otra-dump--fifth {
          color : #9600ff;
        }
      </style><?php
      self::$cssNotAdded = false;
    }

    $notFirstDepth = ($depth !== -1);
    $paramType = gettype($param);
    $padding = '';

    if ($notFirstDepth)
      $padding = self::indentColors($depth + 1);

    echo self::OTRA_DUMP_TEXT_BLOCK;

    if ($isArray)
      echo $padding;

    // showing keys
    if ($notFirstDepth)
    {
      echo(gettype($paramKey) !== DumpMaster::OTRA_DUMP_TYPE_STRING
        ? $paramKey
        : '\'' . $paramKey . '\''
      ), self::RIGHT_ARROW;
    }

    // showing values
    switch($paramType)
    {
      case DumpMaster::OTRA_DUMP_TYPE_ARRAY :
        self::dumpArray($paramType, $param, $notFirstDepth, $depth);
        break;
      case 'boolean' :
        echo $paramType, $param ? ' true' : ' false', self::ENDING_SPAN, self::BR;
        break;
      case 'integer' :
      case 'double' :
        echo $paramType, ' ', $param, self::ENDING_SPAN, self::ENDING_SPAN, self::BR;
        break;
      case 'NULL' : echo '<b> null</b><br>'; break;
      case 'object' :
        self::dumpObject($param, $notFirstDepth, $depth);
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
          echo self::BR, $padding;

        echo ' \'', str_replace(
          PHP_EOL,
          PHP_EOL . self::indentColors($depth + 1),
          htmlspecialchars($stringToShow)
        ), '\'';

        if ($lengthParam > AllConfig::$debugConfig[self::OTRA_DUMP_ARRAY_KEY[self::OTRA_DUMP_KEY_MAX_DATA]])
          echo '<b>(cut)</b>';

        echo self::ENDING_SPAN, self::BR;
        break;

      default:
        echo $paramType, ' ', $param, self::ENDING_SPAN;
        break;
    }

    echo self::ENDING_SPAN;
  }

  /**
   * @param string $sourceFile
   * @param int    $sourceLine
   * @param string $content
   */
  protected static function dumpCallback(string $sourceFile, int $sourceLine, string $content) : void
  {
    ?>
    <link rel="stylesheet" href="<?= CORE_CSS_PATH ?>partials/otraDump/otraDump.css"/>
    <div class="otra-dump">
      <span class="otra-dump--intro">
        <?= 'OTRA DUMP - ' . $sourceFile . ':' . $sourceLine ?>
      </span><?php self::createFoldable(true); ?>
      <pre class="otra-dump--string"><!--
     --><strong class="otra--code--container"><mark class="otra--code--container-highlight"><?=
          getSourceFromFile($sourceFile, $sourceLine, 2)
          ?></mark></strong></pre>
    </div>
    <pre class="otra-dump--string">
      <br><?= $content ?>
    </pre>
    </div>
    <?php
  }

  /**
   * A nice dump function that takes as many parameters as we want to put.
   * Calls the DumpMaster::dump that will use the 'dumpCallback' function.
   *
   * @param mixed $params
   */
  public static function dump(... $params) : void
  {
    parent::dump(... $params);
  }
}
