<?php
declare(strict_types=1);

namespace src\console\architecture;

use otra\OtraException;
use PHPUnit\Framework\TestCase;
use const otra\cache\php\CONSOLE_PATH;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\architecture\checkBooleanArgument;

/**
 * It fixes issues like when AllConfig is not loaded while it should be
 * @preserveGlobalState disabled
 * @runTestsInSeparateProcesses
 */
class CheckBooleanArgumentTest extends TestCase
{
  final public const TEST_BUNDLE = 'test';
  private const
    TEST_TASK = 'createBundle',
    OTRA_BINARY = 'otra.php',
    CREATE_BUNDLE_ARG_FORCE = 5,
    CREATE_BUNDLE_MASK = 0,
    ARGUMENT_NAME = 'force',
    NO_INTERACTIVE_MODE = 'false',
    PASSED_DEFAULT_VALUE = 'false';

  protected function setUp(): void
  {
    parent::setUp();
    require CONSOLE_PATH . 'architecture/checkBooleanArgument.php';
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testNoArgument() : void
  {
    // launching
    $valid = checkBooleanArgument(
      [
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::CREATE_BUNDLE_MASK,
        self::NO_INTERACTIVE_MODE
      ],
      self::CREATE_BUNDLE_ARG_FORCE,
      self::ARGUMENT_NAME,
      self::PASSED_DEFAULT_VALUE
    );

    // testing
    static::assertSame(
      false,
      $valid,
      'Checks if the function correctly returns the default value of ' . CLI_INFO_HIGHLIGHT .
      self::PASSED_DEFAULT_VALUE . END_COLOR . '.'
    );
  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testWrongArgument() : void
  {
    // context
    $wrongForceArgument = 'fal';

    // testing exception
    $this->expectException(OtraException::class);

    // launching
    checkBooleanArgument(
      [
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::CREATE_BUNDLE_MASK,
        self::NO_INTERACTIVE_MODE,
        $wrongForceArgument
      ],
      self::CREATE_BUNDLE_ARG_FORCE,
      self::ARGUMENT_NAME,
      self::PASSED_DEFAULT_VALUE
    );

    // testing
    $this->expectOutputString(
      CLI_ERROR . 'The parameter ' . CLI_INFO_HIGHLIGHT . self::ARGUMENT_NAME . ' ' . CLI_ERROR .
      'is not correct. You typed ' . CLI_INFO_HIGHLIGHT . $wrongForceArgument . CLI_ERROR . '. Type ' .
      CLI_INFO_HIGHLIGHT . 'true' . CLI_ERROR . ' or ' . CLI_INFO_HIGHLIGHT . 'false' . CLI_ERROR . ' instead.' .
      END_COLOR . PHP_EOL
    );

  }

  /**
   * @author Lionel Péramo
   * @throws OtraException
   */
  public function testGoodArgument() : void
  {
    // context
    $passedForceArgument = 'true';

    // launching
    $valid = checkBooleanArgument(
      [
        self::OTRA_BINARY,
        self::TEST_TASK,
        self::TEST_BUNDLE,
        self::CREATE_BUNDLE_MASK,
        self::NO_INTERACTIVE_MODE,
        $passedForceArgument
      ],
      self::CREATE_BUNDLE_ARG_FORCE,
      self::ARGUMENT_NAME,
      self::PASSED_DEFAULT_VALUE
    );

    // testing
    static::assertSame(
      true,
      $valid,
      'Checks if the function correctly returns true when we pass the correct value of ' . CLI_INFO_HIGHLIGHT .
      $passedForceArgument . END_COLOR . '.'
    );
  }
}
