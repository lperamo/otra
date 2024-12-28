<?php
declare(strict_types=1);

namespace src\console\deployment\genBootstrap\taskFileOperation;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use function otra\console\deployment\genBootstrap\resolveInclusionPath;
use const otra\cache\php\BUNDLES_PATH;
use const otra\cache\php\CONSOLE_PATH;
use const otra\console\
{CLI_ERROR, CLI_INFO_HIGHLIGHT, CLI_WARNING, END_COLOR};
use function otra\console\deployment\genBootstrap\evalPathVariables;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class ResolveInclusionPathTest extends TestCase
{
  /**
   * expected, expression
   * @return array<string,string[]>
   */
  public static function expressionsProvider(): array
  {
    return [
      'expression with variable' =>
      [
        '$basePath',
        '$basePath'
      ],
      'simple path in a single quoted string' =>
      [
        'a.php',
        '\'a.php\'',
      ],
      'simple path in a double quoted string' =>
      [
        'a.php',
        '"a.php"'
      ],
      'constant to replace' =>
      [
        BUNDLES_PATH . 'a.php',
        'BUNDLES_PATH . \'a.php\''
      ],
      'function call like ucFirst' =>
      [
        'A.php',
        'ucFirst(\'a\') . \'.php\''
      ],
      'invalid function call' =>
      [
        'a(\'b\')',
        'a(\'b\')'
      ],
      'already a simple value' =>
      [
        '3',
        '3'
      ],
      'unnecessary empty string' =>
      [
        'ab',
        "'a' . 'b'"
      ],
      'complex expression with unknown constant' =>
      [
        'TEST.\'Ecocomposer/backend/services/createLicenseEntry.php\'',
        'TEST . \'Ecocomposer/backend/services/\' . \'createLicenseEntry.php\''
      ],
      'complex expression with a known constant that has a path' =>
      [
        BUNDLES_PATH . 'config/prod/AllConfig.php',
        'otra\\cache\\php\\BUNDLES_PATH . \'config/\' . \'prod\' . \'/AllConfig.php\''
      ],
      'complex expression with a known constant that contains itself a constant' =>
      [
        BUNDLES_PATH . 'config/prod/AllConfig.php',
        'RECURSIVE_PATH . \'config/\' . \'prod\' . \'/AllConfig.php\''
      ],
      'known variable' =>
      [
        'hey',
        '$test'
      ]
    ];
  }

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'deployment/genBootstrap/resolveInclusionPath.php';
  }

  /**
   * @author Lionel Péramo
   * @dataProvider expressionsProvider
   */
  public function test(string $expectedExpression, string $expression) : void
  {
    // context (only useful to some tests)
    define('BUNDLES_PATH', BUNDLES_PATH);
    define('RECURSIVE_PATH', 'BUNDLES_PATH');
    define('PATH_CONSTANTS', ['test' => 'hey']);
    // launching and testing
    static::assertSame($expectedExpression, resolveInclusionPath($expression));
  }
}
