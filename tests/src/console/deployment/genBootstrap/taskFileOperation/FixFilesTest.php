<?php
declare(strict_types=1);

namespace otra\console\deployment\genBootstrap
{
  const VERBOSE = 2;
}

namespace src\console\deployment\genBootstrap\taskFileOperation
{
  use otra\OtraException;
  use PHPUnit\Framework\TestCase;
  use const otra\cache\php\{APP_ENV, BASE_PATH, BUNDLES_PATH, CONSOLE_PATH, PROD, TEST_PATH};
  use const otra\console\{CLI_INFO_HIGHLIGHT, CLI_SUCCESS, CLI_WARNING, END_COLOR};
  use function otra\console\deployment\genBootstrap\fixFiles;

  /**
   * It fixes issues like when AllConfig is not loaded while it should be
   * @preserveGlobalState disabled
   * @runTestsInSeparateProcesses
   */
  class FixFilesTest extends TestCase
  {
    private const int
      DOTS = 80,
      ONE_INDENT = 2,
      TWO_INDENTS = 4;

    private const string
      // folders
      INPUT_FOLDER = 'tests/examples/deployment/fixFiles/input/',
      OUTPUT_FOLDER = TEST_PATH . 'examples/deployment/fixFiles/output/',
      VENDOR_FOLDER = self::INPUT_FOLDER . 'vendor/',

      // files
      FILE_2 = self::INPUT_FOLDER . 'Test2.php',
      FILE_VENDOR = self::VENDOR_FOLDER . 'Test.php',
      FILE_VENDOR_2 = self::VENDOR_FOLDER . 'Test2.php',
      FILE_VENDOR_3 = self::VENDOR_FOLDER . 'Test3.php',
      FILE_VENDOR_4 = self::VENDOR_FOLDER . 'Test4.php',
      FILE_VENDOR_5 = self::VENDOR_FOLDER . 'Test5.php',
      FILE_VENDOR_RETURN = self::VENDOR_FOLDER . 'TestReturn.php',
      FILE_VENDOR_RETURN_IN_FUNCTION = self::VENDOR_FOLDER . 'TestReturnInFunction.php',
      FILE_VENDOR_STATIC_CALL = self::VENDOR_FOLDER . 'TestStatic.php',
      FILE_VENDOR_NAMESPACE = self::VENDOR_FOLDER . 'namespace.php',
      FILE_VENDOR_NAMESPACE_TWO_BLOCKS = self::VENDOR_FOLDER . 'namespaceTwoBlocks.php',
      FILE_VENDOR_FUNCTION_IN_NAMESPACE = self::VENDOR_FOLDER . 'functionInNamespace.php',
      FILE_VENDOR_FUNCTION_CONTAINS_BRACES = self::VENDOR_FOLDER . 'functionContainsBraces.php',
      FILE_VENDOR_FUNCTION_WITH_USE = self::VENDOR_FOLDER . 'functionWithUse.php',
      FILE_VENDOR_CONDITIONAL_FUNCTIONS = self::VENDOR_FOLDER . 'conditionalFunctions.php',
      FILE_VENDOR_COMPLEX_CLASS = self::VENDOR_FOLDER . 'complexClass.php',
      FILE_VENDOR_COMPLEX_USE_CASE = self::VENDOR_FOLDER . 'complexUseCase.php',
      FILE_VENDOR_COMPLEX_USE_CASE_BIS = self::VENDOR_FOLDER . 'complexUseCaseBis.php',
      FILE_VENDOR_USE_CONST = self::VENDOR_FOLDER . 'useConst.php',
      FILE_VENDOR_TEMPLATE = self::VENDOR_FOLDER . 'template.phtml',
      FILE_VENDOR_TEMPLATE_PHP_AND_HTML = self::VENDOR_FOLDER . 'templatePhpAndHtml.phtml',
      FILE_VENDOR_TEMPLATE_HTML_AND_PHP = self::VENDOR_FOLDER . 'templateHtmlAndPhp.phtml',
      FILE_VENDOR_PHP_FULL_HTML = self::VENDOR_FOLDER . 'phpFullHtml.php',
      FILE_VENDOR_PHP_PHP_AND_HTML = self::VENDOR_FOLDER . 'phpAndHtml.php',
      FILE_VENDOR_PHP_HTML_AND_PHP = self::VENDOR_FOLDER . 'htmlAndPhp.php',
      FILE_VENDOR_PHP_FULL_EXAMPLE = self::VENDOR_FOLDER . 'fullExample.php',
      FILE_VENDOR_PHP_CONST = self::VENDOR_FOLDER . 'const.php',
      FILE_VENDOR_PHP_USE_TRAIT = self::VENDOR_FOLDER . 'TestTrait.php',
      FILE_VENDOR_PHP_SHORT_TAG = self::VENDOR_FOLDER . 'shortTag.php',
      FILE_VENDOR_PHP_MINIFIED = self::VENDOR_FOLDER . 'minified.php',
      FILE_VENDOR_PHP_ARRAY_CONSTANTS = self::VENDOR_FOLDER . 'arrayConstants.php',
      FILE_VENDOR_CLASS_NAME_CONFLICT = self::VENDOR_FOLDER . 'TestRequireClassNameConflict.php',
      FILE_VENDOR_CONSTANT_DUPLICATIONS = self::VENDOR_FOLDER . 'constantDuplications.php',
      FILE_ALL_CONFIG = self::VENDOR_FOLDER . 'Config.php',
      FILE_PROD_ALL_CONFIG = self::VENDOR_FOLDER . 'prodConfig.php',

      INCLUDE_FILE = self::INPUT_FOLDER . 'TestInclude.php',
      INCLUDE_ONCE_FILE = self::INPUT_FOLDER . 'TestIncludeOnce.php',

      EXTENDS_FILE = self::INPUT_FOLDER . 'TestExtends.php',
      EXTENDS_WITHOUT_USE_FILE = self::INPUT_FOLDER . 'TestExtendsWithoutUse.php',

      SIMPLE_USE_FILE = self::INPUT_FOLDER . 'TestSimpleUse.php',
      VENDOR_USE_FILE = self::INPUT_FOLDER . 'TestVendorUse.php',
      INLINE_TWO_USE_FILE = self::INPUT_FOLDER . 'TestInlineTwoUse.php',
      INLINE_THREE_USE_FILE = self::INPUT_FOLDER . 'TestInlineThreeUse.php',
      USE_FUNCTION_FILE = self::INPUT_FOLDER . 'TestUseFunction.php',
      USE_CONST_FILE = self::INPUT_FOLDER . 'TestUseConst.php',
      USE_IN_COMMENT = self::INPUT_FOLDER . 'TestUseInComment.php',
      USE_TRAIT = self::INPUT_FOLDER . 'TestUseTrait.php',
      USE_CONST_MULTIPLE_REQUIRE_FILE = self::INPUT_FOLDER . 'TestUseConstMultipleRequire.php',
      USE_NATIVE_CLASS_FILE = self::INPUT_FOLDER . 'TestUseNativeClass.php',
      USE_IN_OR_AFTER_COMMENTS = self::INPUT_FOLDER . 'TestUseInOrAfterComments.php',
      MINIFIED_FILE = self::INPUT_FOLDER . 'TestMinified.php',

      REQUIRE_FILE = self::INPUT_FOLDER . 'TestRequire.php',
      REQUIRE_CLASS_NAME_CONFLICT = self::INPUT_FOLDER . 'TestRequireClassNameConflict.php',
      REQUIRE_CONSTANT_DUPLICATIONS = self::INPUT_FOLDER . 'TestRequireConstantDuplications.php',
      REQUIRE_NAMESPACE_FILE = self::INPUT_FOLDER . 'TestRequireNamespace.php',
      REQUIRE_NAMESPACE_TWO_BLOCKS_FILE = self::INPUT_FOLDER . 'TestRequireNamespaceTwoBlocks.php',
      REQUIRE_FUNCTION_IN_NAMESPACE = self::INPUT_FOLDER . 'TestRequireFunctionInNamespace.php',
      REQUIRE_FUNCTION_CONTAINS_BRACES = self::INPUT_FOLDER . 'TestRequireFunctionContainsBraces.php',
      REQUIRE_FUNCTION_WITH_USE = self::INPUT_FOLDER . 'TestRequireFunctionWithUse.php',
      REQUIRE_USE_CONST = self::INPUT_FOLDER . 'TestRequireUseConst.php',
      REQUIRE_REPLACEABLE_VARIABLES = self::INPUT_FOLDER . 'TestRequireAllConfig.php',
      REQUIRE_CONDITIONAL_FUNCTIONS = self::INPUT_FOLDER . 'TestRequireConditionalFunctions.php',
      REQUIRE_MASTER_CONTROLLER = self::INPUT_FOLDER . 'TestRequireMasterController.php',
      REQUIRE_ARRAY_CONSTANTS = self::INPUT_FOLDER . 'TestRequireArrayConstants.php',
      REQUIRE_RANDOM_FILE = self::INPUT_FOLDER . 'TestRequireRandom.php',
      REQUIRE_TEMPLATE_FILE = self::INPUT_FOLDER . 'TestRequireTemplate.php',
      REQUIRE_TEMPLATE_PHP_AND_HTML_FILE = self::INPUT_FOLDER . 'TestRequireTemplatePhpAndHtml.php',
      REQUIRE_TEMPLATE_HTML_AND_PHP_FILE = self::INPUT_FOLDER . 'TestRequireTemplateHtmlAndPhp.php',
      REQUIRE_PHP_FULL_HTML = self::INPUT_FOLDER . 'TestRequirePhpFullHtml.php',
      REQUIRE_PHP_PHP_AND_HTML = self::INPUT_FOLDER . 'TestRequirePhpPhpAndHtml.php',
      REQUIRE_PHP_HTML_AND_PHP = self::INPUT_FOLDER . 'TestRequirePhpHtmlAndPhp.php',
      REQUIRE_PHP_FULL_EXAMPLE = self::INPUT_FOLDER . 'TestRequirePhpFullExample.php',
      REQUIRE_PHP_CONST = self::INPUT_FOLDER . 'TestRequirePhpConst.php',
      REQUIRE_PHP_SHORT_TAG = self::INPUT_FOLDER . 'TestRequirePhpShortTag.php',
      REQUIRE_COMPLEX_FILE = self::INPUT_FOLDER . 'TestRequireComplex.php',
      REQUIRE_WITH_DIR_FILE = self::INPUT_FOLDER . 'TestRequireDir.php',
      REQUIRE_COMMENTED_FILE = self::INPUT_FOLDER . 'TestRequireCommented.php',
      REQUIRE_COMMENTED_NO_SPACE_FILE = self::INPUT_FOLDER . 'TestRequireCommentedNoSpace.php',
      REQUIRE_ONCE_FILE = self::INPUT_FOLDER . 'TestRequireOnce.php',
      REQUIRE_VENDOR_FILE = self::INPUT_FOLDER . 'TestRequireVendor.php',
      MULTIPLE_REQUIRE_FILE = self::INPUT_FOLDER . 'TestMultipleRequire.php',
      NESTED_REQUIRE_FILE = self::INPUT_FOLDER . 'TestNestedRequire.php',
      DYNAMIC_REQUIRE_FILE = self::INPUT_FOLDER . 'TestDynamicRequire.php',
      DYNAMIC_REQUIRE_SIMPLE_VARIABLE_FILE = self::INPUT_FOLDER . 'TestDynamicRequireSimpleVariable.php',
      DYNAMIC_REQUIRE_KNOWN_CONSTANT_FILE = self::INPUT_FOLDER . 'TestDynamicRequireKnownConstant.php',
      REQUIRE_RETURN_FILE = self::INPUT_FOLDER . 'TestRequireReturn.php',
      REQUIRE_IN_FUNCTION_FILE = self::INPUT_FOLDER . 'TestRequireInFunction.php',
      REQUIRE_RETURN_IN_FUNCTION_FILE = self::INPUT_FOLDER . 'TestRequireReturnInFunction.php',
      REQUIRE_COMPLEX_CLASS = self::INPUT_FOLDER . 'TestRequireComplexClass.php',
      REQUIRE_COMPLEX_USE_CASE = self::INPUT_FOLDER . 'TestRequireComplexUseCase.php',

      STATIC_CALL_FILE = self::INPUT_FOLDER . 'TestStaticCall.php',
      
      // classes
      CLASS_VENDOR_CLASS_NAME_CONFLICT = 'examples\deployment\fixFiles\input\vendor\TestRequireClassNameConflict',

      // texts
      ALREADY_PARSED = ' ALREADY PARSED',
      LOADED = CLI_SUCCESS . ' [LOADED]' . END_COLOR,
      VIA_REQUIRE_INCLUDE_STATEMENT = ' via require/include statement',
      VIA_STATIC_DIRECT_CALL = ' via static direct call',
      VIA_USE_STATEMENT = ' via use statement',
      WARNING_FIRST_FILE = CLI_WARNING . ' first file' . END_COLOR . PHP_EOL;

    protected function setUp(): void
    {
      parent::setUp();
      $_SERVER[APP_ENV] = PROD; // needed for the test data set "replaceable variables"  
      require CONSOLE_PATH . 'deployment/genBootstrap/taskFileOperation.php';
      // Only useful for some tests like 'Dynamic require with a simple variable'
      define(
        'otra\\console\\deployment\\genBootstrap\\PATH_CONSTANTS',
        [
          'externalConfigFile' => BUNDLES_PATH . 'config/Config.php',
          'driver' => 'PDOMySQL',
          "_SERVER[APP_ENV]" => $_SERVER[APP_ENV],
          'temporaryEnv' => PROD
        ]
      );
      define(
        __NAMESPACE__ . '\\PATH_CONSTANTS',
        [
          'externalConfigFile' => BUNDLES_PATH . 'config/Config.php',
          'driver' => 'Pdomysql',
          "_SERVER[APP_ENV]" => PROD,
          'temporaryEnv' => PROD
        ]
      );
    }

    /**
     * Tests various use statements. (need medium only because of the test about MasterController)
     * @medium
     *
     * @dataProvider fileProvider
     *
     * @param string $expectedContent The expected content after replacement
     * @param string $expectedOutput  The expected output
     * @param string $fileToInclude   File to include
     * @param string $temporaryFile   File to show when syntax errors occur
     *
     * @throws OtraException
     * @return void
     */
    public function testVariousFiles(
      string $expectedContent,
      string $expectedOutput,
      string $fileToInclude,
      string $temporaryFile
    ) : void
    {
      ob_start();
      [$finalContent] = fixFiles(
        'test',
        'test',
        file_get_contents($fileToInclude),
        1,
        true,
        $temporaryFile,
        $fileToInclude
      );

      // I do not use expect `$this->expectOutputString($expectedContent);`
      // to ensure the assertions are used in that order
//      static::assertFileDoesNotExist($temporaryFile);
      static::assertSame($expectedOutput, ob_get_clean(), 'Testing launching the file ' . $fileToInclude);
      static::assertSame(
        file_get_contents($expectedContent),
        $finalContent,
        'Testing replaced content vs ' . $expectedContent
      );
    }

    /**
     * @param string  $inputFile
     * @param array   $filesToInclude
     * @param ?string $additionalWarning
     *
     * @return string
     */
    private static function formatString(string $inputFile, array $filesToInclude, ?string $additionalWarning = '') : string
    {
      $string = str_pad($inputFile, self::DOTS, '.') . self::WARNING_FIRST_FILE . $additionalWarning;

      foreach($filesToInclude as $fileToInclude)
      {
        $string .= str_pad(
          str_repeat(' ', $fileToInclude[2]) . '| ' . $fileToInclude[0],
          self::DOTS,
          '.'
          ) . CLI_WARNING . $fileToInclude[1] . END_COLOR . PHP_EOL;
      }

      return $string . PHP_EOL . str_pad( 'Files to include ', self::DOTS, '.') . self::LOADED;
    }

    /**
     * Provides data for testVariousUseStatements.
     *
     * @return array<string, array{0: string, 1: string, 2: array<array-key, string>}>
     */
    public static function fileProvider() : array
    {
      return [
        'Require' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequire.php',
          self::formatString(self::REQUIRE_FILE, [[self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]),
          BASE_PATH . self::REQUIRE_FILE,
          self::OUTPUT_FOLDER . 'errorRequire.php'
        ],
        'Require_once' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireOnce.php',
          self::formatString(self::REQUIRE_ONCE_FILE, [[self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]),
          BASE_PATH . self::REQUIRE_ONCE_FILE,
          self::OUTPUT_FOLDER . 'errorRequireOnce.php'
        ],
        'Include' =>
        [
          self::OUTPUT_FOLDER . 'OutputInclude.php',
          self::formatString(self::INCLUDE_FILE, [[self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]),
          BASE_PATH . self::INCLUDE_FILE,
          self::OUTPUT_FOLDER . 'errorInclude.php'
        ],
        'Include_once' =>
        [
          self::OUTPUT_FOLDER . 'OutputIncludeOnce.php',
          self::formatString(self::INCLUDE_ONCE_FILE, [[self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]),
          BASE_PATH . self::INCLUDE_ONCE_FILE,
          self::OUTPUT_FOLDER . 'errorIncludeOnce.php'
        ],
        // we use `ucfirst` in the `require` to see if functions are working
        'Require (complex)' =>
        [
            self::OUTPUT_FOLDER . 'OutputRequireComplex.php',
            self::formatString(
              self::REQUIRE_COMPLEX_FILE,
              [[self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
            ),
            BASE_PATH . self::REQUIRE_COMPLEX_FILE,
            self::OUTPUT_FOLDER . 'errorRequireComplex.php'
        ],
        'Require with __DIR__' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireDir.php',
          self::formatString(
            self::REQUIRE_WITH_DIR_FILE,
            [[self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_WITH_DIR_FILE,
          self::OUTPUT_FOLDER . 'errorRequireWithDir.php'
        ],
        'Require commented' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireCommented.php',
          self::formatString(self::REQUIRE_COMMENTED_FILE, []),
          BASE_PATH . self::REQUIRE_COMMENTED_FILE,
          self::OUTPUT_FOLDER . 'errorRequireCommented.php'
        ],
        'Require commented, no space' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireCommentedNoSpace.php',
          self::formatString(self::REQUIRE_COMMENTED_NO_SPACE_FILE, []),
          BASE_PATH . self::REQUIRE_COMMENTED_NO_SPACE_FILE,
          self::OUTPUT_FOLDER . 'errorRequireCommentedNoSpace.php'
        ],
        'Extends with use' =>
        [
          self::OUTPUT_FOLDER . 'OutputExtends.php',
          self::formatString(self::EXTENDS_FILE, [[self::FILE_2, self::VIA_USE_STATEMENT, 2]]),
          BASE_PATH . self::EXTENDS_FILE,
          self::OUTPUT_FOLDER . 'errorExtends.php'
        ],
        // We should not need a use statement if the two classes share the same namespace
        'Extends without use' =>
        [
          self::OUTPUT_FOLDER . 'OutputExtendsWithoutUse.php',
          self::formatString(self::EXTENDS_WITHOUT_USE_FILE, [[self::FILE_2, ' via extends statement', 2]]),
          BASE_PATH . self::EXTENDS_WITHOUT_USE_FILE,
          self::OUTPUT_FOLDER . 'errorExtendsWithoutUse.php'
        ],
        'Require with a vendor class' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireVendor.php',
          self::formatString(
            self::REQUIRE_VENDOR_FILE,
            [[self::FILE_VENDOR_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_VENDOR_FILE,
          self::OUTPUT_FOLDER . 'errorRequireVendor.php'
        ],
        'Multiple require' =>
        [
            self::OUTPUT_FOLDER . 'OutputMultipleRequire.php',
            self::formatString(
              self::MULTIPLE_REQUIRE_FILE,
              [
                [self::FILE_VENDOR_3, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
                [self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
              ]
            ),
            BASE_PATH . self::MULTIPLE_REQUIRE_FILE,
            self::OUTPUT_FOLDER . 'errorMultipleRequire.php'
          ],
        'Nested require' =>
        [
          self::OUTPUT_FOLDER . 'OutputNestedRequire.php',
          self::formatString(
            self::NESTED_REQUIRE_FILE,
            [
              [self::FILE_VENDOR, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
              [self::FILE_VENDOR_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS],
            ]
          ),
          BASE_PATH . self::NESTED_REQUIRE_FILE,
          self::OUTPUT_FOLDER . 'errorNestedRequire.php'
        ],
        // OTRA does not handle this case for now so we cannot include the file
        'Dynamic require with a static variable' => [
          self::OUTPUT_FOLDER . 'OutputDynamicRequire.php',
          self::formatString(self::DYNAMIC_REQUIRE_FILE, []),
          BASE_PATH . self::DYNAMIC_REQUIRE_FILE,
          self::OUTPUT_FOLDER . 'errorDynamicRequire.php'
        ],
        // OTRA does not handle this case for now so we cannot include the file
        'Dynamic require with a simple variable' => [
          self::OUTPUT_FOLDER . 'OutputDynamicRequireSimpleVariable.php',
          self::formatString(
            self::DYNAMIC_REQUIRE_SIMPLE_VARIABLE_FILE,
            [],
            CLI_WARNING . 
            'require/include statement not evaluated because of the non defined dynamic variable ' . 
            CLI_INFO_HIGHLIGHT . '$folder' . CLI_WARNING . ' in' . PHP_EOL . '  ' . CLI_INFO_HIGHLIGHT . 
            'require $folder . \'Test2.php\';' . CLI_WARNING . PHP_EOL . '  in the file ' . CLI_INFO_HIGHLIGHT .
            BASE_PATH . self::DYNAMIC_REQUIRE_SIMPLE_VARIABLE_FILE . CLI_WARNING . '!' . END_COLOR . PHP_EOL
          ),
          BASE_PATH . self::DYNAMIC_REQUIRE_SIMPLE_VARIABLE_FILE,
          self::OUTPUT_FOLDER . 'errorDynamicRequireSimpleVariable.php'
        ],
        'Dynamic require with a known constant' => [
          self::OUTPUT_FOLDER . 'OutputDynamicRequireKnownConstant.php',
          self::formatString(
            self::DYNAMIC_REQUIRE_KNOWN_CONSTANT_FILE,
            [[self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::DYNAMIC_REQUIRE_KNOWN_CONSTANT_FILE,
          self::OUTPUT_FOLDER . 'errorDynamicRequireKnownConstant.php'
        ],
        'Require that returns an array' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireReturn.php',
          self::formatString(
            self::REQUIRE_RETURN_FILE,
            [[self::FILE_VENDOR_RETURN, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_RETURN_FILE,
          self::OUTPUT_FOLDER . 'errorRequireReturn.php'
        ],
        // This is only to ensure us that we do not process the `return` keyword from functions the same way that we
        // process `return` from files (top level returns)
        'Require with return in function' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireReturnInFunction.php',
          self::formatString(
            self::REQUIRE_RETURN_IN_FUNCTION_FILE,
            [[self::FILE_VENDOR_RETURN_IN_FUNCTION, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_RETURN_IN_FUNCTION_FILE,
          self::OUTPUT_FOLDER . 'errorRequireReturnInFunction.php'
        ],
        'Require in function' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireInFunction.php',
          self::formatString(
            self::REQUIRE_IN_FUNCTION_FILE,
            [[self::FILE_VENDOR_RETURN, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_IN_FUNCTION_FILE,
          self::OUTPUT_FOLDER . 'errorRequireInFunction.php'
        ],
        // Maybe this test is useless
        'Require random test' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireRandom.php',
          self::formatString(
            self::REQUIRE_RANDOM_FILE,
            []
          ),
          BASE_PATH . self::REQUIRE_RANDOM_FILE,
          self::OUTPUT_FOLDER . 'errorRequireRandom.php'
        ],
        'Require template - Full HTML' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireTemplate.php',
          self::formatString(
            self::REQUIRE_TEMPLATE_FILE,
            [[self::FILE_VENDOR_TEMPLATE, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_TEMPLATE_FILE,
          self::OUTPUT_FOLDER . 'errorRequireTemplate.php'
        ],
        'Require template - PHP + HTML' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireTemplatePhpAndHtml.php',
          self::formatString(
            self::REQUIRE_TEMPLATE_PHP_AND_HTML_FILE,
            [[self::FILE_VENDOR_TEMPLATE_PHP_AND_HTML, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_TEMPLATE_PHP_AND_HTML_FILE,
          self::OUTPUT_FOLDER . 'errorRequireTemplatePhpAndHtml.php'
        ],
        'Require template - HTML + PHP (no PHP closing tag)' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireTemplateHtmlAndPhp.php',
          self::formatString(
            self::REQUIRE_TEMPLATE_HTML_AND_PHP_FILE,
            [[self::FILE_VENDOR_TEMPLATE_HTML_AND_PHP, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_TEMPLATE_HTML_AND_PHP_FILE,
          self::OUTPUT_FOLDER . 'errorRequireTemplateHtmlAndPhp.php'
        ],
        'Require PHP - Full HTML' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequirePhpFullHtml.php',
          self::formatString(
            self::REQUIRE_PHP_FULL_HTML,
            [[self::FILE_VENDOR_PHP_FULL_HTML, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_PHP_FULL_HTML,
          self::OUTPUT_FOLDER . 'errorRequirePhpFullHtml.php'
        ],
        'Require PHP - PHP + HTML' =>
        [
            self::OUTPUT_FOLDER . 'OutputRequirePhpPhpAndHtml.php',
            self::formatString(
              self::REQUIRE_PHP_PHP_AND_HTML,
              [[self::FILE_VENDOR_PHP_PHP_AND_HTML, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
            ),
            BASE_PATH . self::REQUIRE_PHP_PHP_AND_HTML,
            self::OUTPUT_FOLDER . 'errorRequirePhpPhpAndHtml.php'
          ],
        'Require PHP - HTML + PHP' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequirePhpHtmlAndPhp.php',
          self::formatString(
            self::REQUIRE_PHP_HTML_AND_PHP,
            [[self::FILE_VENDOR_PHP_HTML_AND_PHP, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_PHP_HTML_AND_PHP,
          self::OUTPUT_FOLDER . 'errorRequirePhpHtmlAndPhp.php'
        ],
        'Require PHP - PHP + HTML + PHP real function' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequirePhpFullExample.php',
          self::formatString(
            self::REQUIRE_PHP_FULL_EXAMPLE,
            [[self::FILE_VENDOR_PHP_FULL_EXAMPLE, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_PHP_FULL_EXAMPLE,
          self::OUTPUT_FOLDER . 'errorRequirePhpHtmlAndPhp.php'
        ],
        // "separation" as we must separate `const` from `echo` blocks
        'Require PHP - const and separation in the same block' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequirePhpConst.php',
          self::formatString(
            self::REQUIRE_PHP_CONST,
            [[self::FILE_VENDOR_PHP_CONST, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_PHP_CONST,
          self::OUTPUT_FOLDER . 'errorRequirePhpConst.php'
        ],
        'Require PHP - short tag' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequirePhpShortTag.php',
          self::formatString(
            self::REQUIRE_PHP_SHORT_TAG,
            [[self::FILE_VENDOR_PHP_SHORT_TAG, self::VIA_REQUIRE_INCLUDE_STATEMENT, 2]]
          ),
          BASE_PATH . self::REQUIRE_PHP_SHORT_TAG,
          self::OUTPUT_FOLDER . 'errorRequirePhpShortTag.php'
        ],
        // use examples\deployment\fixFiles\input\Test2;
        'Simple use statement' =>
        [
          self::OUTPUT_FOLDER . 'OutputSimpleUse.php',
          self::formatString(self::SIMPLE_USE_FILE, [
            [self::FILE_2, self::VIA_USE_STATEMENT, self::ONE_INDENT],
          ]),
          BASE_PATH . self::SIMPLE_USE_FILE,
          self::OUTPUT_FOLDER . 'errorSimpleUse.php'
        ],
        // use examples\deployment\fixFiles\input\vendor\Test2
        'Vendor use statement' =>
        [
          self::OUTPUT_FOLDER . 'OutputVendorUse.php',
          self::formatString(self::VENDOR_USE_FILE, [
            [self::FILE_VENDOR_2, self::VIA_USE_STATEMENT, self::ONE_INDENT],
          ]),
          BASE_PATH . self::VENDOR_USE_FILE,
          self::OUTPUT_FOLDER . 'errorVendorUse.php'
        ],
        // use xxx/{xxx,xxx}
        'Inline two use statements' =>
        [
          self::OUTPUT_FOLDER . 'OutputInlineTwoUse.php',
          self::formatString(self::INLINE_TWO_USE_FILE, [
            [self::FILE_VENDOR_2, self::VIA_USE_STATEMENT, self::ONE_INDENT],
            [self::FILE_VENDOR_3, self::VIA_USE_STATEMENT, self::ONE_INDENT]
          ]),
          BASE_PATH . self::INLINE_TWO_USE_FILE,
          self::OUTPUT_FOLDER . 'errorInlineTwoUse.php'
        ],
        // use xxx/{xxx,xxx, xxx}
        'Inline three use statements' =>
        [
          self::OUTPUT_FOLDER . 'OutputInlineThreeUse.php',
          self::formatString(self::INLINE_THREE_USE_FILE, [
            [self::FILE_VENDOR_2, self::VIA_USE_STATEMENT, self::ONE_INDENT],
            [self::FILE_VENDOR_3, self::VIA_USE_STATEMENT, self::ONE_INDENT],
            [self::FILE_VENDOR_4, self::VIA_USE_STATEMENT, self::ONE_INDENT]
          ]),
          BASE_PATH . self::INLINE_THREE_USE_FILE,
          self::OUTPUT_FOLDER . 'errorInlineThreeUse.php'
        ],
        // `use function test/myFunction`
        'Use function' =>
        [
          self::OUTPUT_FOLDER . 'OutputUseFunction.php',
          self::formatString(self::USE_FUNCTION_FILE,
          [
            [self::FILE_VENDOR_5, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
          ]),
          BASE_PATH . self::USE_FUNCTION_FILE,
          self::OUTPUT_FOLDER . 'errorUseFunction.php'
        ],
        'Use with constant' =>
        [
          self::OUTPUT_FOLDER . 'OutputUseConst.php',
          self::formatString(self::USE_CONST_FILE, [
            [self::FILE_VENDOR_PHP_CONST, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
          ]),
          BASE_PATH . self::USE_CONST_FILE,
          self::OUTPUT_FOLDER . 'errorUseConst.php'
        ],
        'Use with constant - Multiple Require' =>
        [
          self::OUTPUT_FOLDER . 'OutputUseConstMultipleRequire.php',
          self::formatString(self::USE_CONST_MULTIPLE_REQUIRE_FILE, [
            [self::FILE_2, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
            [self::FILE_VENDOR_PHP_CONST, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
          ]),
          BASE_PATH . self::USE_CONST_MULTIPLE_REQUIRE_FILE,
          self::OUTPUT_FOLDER . 'errorUseConstMultipleRequire.php'
        ],
        // use Exception
        'Use native class' =>
        [
          self::OUTPUT_FOLDER . 'OutputUseNativeClass.php',
          self::formatString(self::USE_NATIVE_CLASS_FILE, []),
          BASE_PATH . self::USE_NATIVE_CLASS_FILE,
          self::OUTPUT_FOLDER . 'errorUseNativeClass.php'
        ],
        'Use in comments or after comments' =>
        [
          self::OUTPUT_FOLDER . 'OutputUseInOrAfterComments.php',
          self::formatString(self::USE_IN_OR_AFTER_COMMENTS, []),
          BASE_PATH . self::USE_IN_OR_AFTER_COMMENTS,
          self::OUTPUT_FOLDER . 'errorUseInOrAfterComments.php'
        ],
        // `use` at the end of a comment
        'Use at end of comment' =>
        [
          self::OUTPUT_FOLDER . 'OutputUseInComment.php',
          self::formatString(self::USE_IN_COMMENT, []),
          BASE_PATH . self::USE_IN_COMMENT,
          self::OUTPUT_FOLDER . 'errorUseInComment.php'
        ],
        'Use trait' =>
        [
          self::OUTPUT_FOLDER . 'OutputUseTrait.php',
          self::formatString(
            self::USE_TRAIT,
            [
              [self::FILE_VENDOR_PHP_USE_TRAIT, self::VIA_USE_STATEMENT, self::ONE_INDENT]
            ]
          ),
          BASE_PATH . self::USE_TRAIT,
          self::OUTPUT_FOLDER . 'errorUseTrait.php'
        ],
        'Minified file' =>
        [
          self::OUTPUT_FOLDER . 'OutputMinified.php',
          self::formatString(self::MINIFIED_FILE,
            [
              [self::FILE_VENDOR_PHP_MINIFIED, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
            ]),
          BASE_PATH . self::MINIFIED_FILE,
          self::OUTPUT_FOLDER . 'errorMinified.php'
        ],
        'Static call' =>
        [
          self::OUTPUT_FOLDER . 'OutputStaticCall.php',
          self::formatString(
            self::STATIC_CALL_FILE,
            [
              [self::FILE_VENDOR_STATIC_CALL, self::VIA_USE_STATEMENT, self::ONE_INDENT]
            ]
          ),
          BASE_PATH . self::STATIC_CALL_FILE,
          self::OUTPUT_FOLDER . 'errorStaticCall.php'
        ],
        // For now, we remove namespace block
        'namespace block' =>
        [
            self::OUTPUT_FOLDER . 'OutputRequireNamespace.php',
            self::formatString(
              self::REQUIRE_NAMESPACE_FILE,
              [
                [self::FILE_VENDOR_NAMESPACE, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
              ]
            ),
            BASE_PATH . self::REQUIRE_NAMESPACE_FILE,
            self::OUTPUT_FOLDER . 'errorRequireNamespace.php'
        ],
        // For now, we remove namespace blocks
        'two namespace blocks' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireNamespaceTwoBlocks.php',
          self::formatString(
            self::REQUIRE_NAMESPACE_TWO_BLOCKS_FILE,
            [
              [self::FILE_VENDOR_NAMESPACE_TWO_BLOCKS, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
            ]
          ),
          BASE_PATH . self::REQUIRE_NAMESPACE_TWO_BLOCKS_FILE,
          self::OUTPUT_FOLDER . 'errorRequireNamespaceTwoBlocks.php'
        ],
        'function in a namespace' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireFunctionInNamespace.php',
          self::formatString(
            self::REQUIRE_FUNCTION_IN_NAMESPACE,
            [
              [self::FILE_VENDOR_FUNCTION_IN_NAMESPACE, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
            ]
          ),
          BASE_PATH . self::REQUIRE_FUNCTION_IN_NAMESPACE,
          self::OUTPUT_FOLDER . 'errorRequireFunctionInNamespace.php'
        ],
        'function contains braces in it' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireFunctionContainsBraces.php',
          self::formatString(
            self::REQUIRE_FUNCTION_CONTAINS_BRACES,
            [
              [self::FILE_VENDOR_FUNCTION_CONTAINS_BRACES, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
            ]
          ),
          BASE_PATH . self::REQUIRE_FUNCTION_CONTAINS_BRACES,
          self::OUTPUT_FOLDER . 'errorRequireFunctionContainsBraces.php'
        ],
        // function myFunction($param1,$param2) use ($param3,$param4){}
        'function with use' =>
        [
            self::OUTPUT_FOLDER . 'OutputRequireFunctionWithUse.php',
            self::formatString(
              self::REQUIRE_FUNCTION_WITH_USE,
              [
                [self::FILE_VENDOR_FUNCTION_WITH_USE, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]
              ]
            ),
            BASE_PATH . self::REQUIRE_FUNCTION_WITH_USE,
            self::OUTPUT_FOLDER . 'errorRequireFunctionWithUse.php'
        ],
        'use const from require' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireUseConst.php',
          self::formatString(
            self::REQUIRE_USE_CONST,
            [[self::FILE_VENDOR_USE_CONST, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_USE_CONST,
          self::OUTPUT_FOLDER . 'errorRequireUseConst.php'
        ],
        'replaceable variables' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireReplaceableVariables.php',
          self::formatString(
            self::REQUIRE_REPLACEABLE_VARIABLES,
            [
              [self::FILE_ALL_CONFIG, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
              [self::FILE_PROD_ALL_CONFIG, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS]
            ]
          ),
          BASE_PATH . self::REQUIRE_REPLACEABLE_VARIABLES,
          self::OUTPUT_FOLDER . 'errorRequireReplaceableVariables.php'
        ],
        'conditional functions' =>
        [
            self::OUTPUT_FOLDER . 'OutputRequireConditionalFunctions.php',
            self::formatString(
              self::REQUIRE_CONDITIONAL_FUNCTIONS,
              [[self::FILE_VENDOR_CONDITIONAL_FUNCTIONS, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
            ),
            BASE_PATH . self::REQUIRE_CONDITIONAL_FUNCTIONS,
            self::OUTPUT_FOLDER . 'errorRequireConditionalFunctions.php'
        ],
        'array constants' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireArrayConstants.php',
          self::formatString(
            self::REQUIRE_ARRAY_CONSTANTS,
            [[self::FILE_VENDOR_PHP_ARRAY_CONSTANTS, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_ARRAY_CONSTANTS,
          self::OUTPUT_FOLDER . 'errorRequireArrayConstants.php'
        ],
        'abstract complex class with nested accolades' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireComplexClass.php',
          self::formatString(
            self::REQUIRE_COMPLEX_CLASS,
            [[self::FILE_VENDOR_COMPLEX_CLASS, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT]]
          ),
          BASE_PATH . self::REQUIRE_COMPLEX_CLASS,
          self::OUTPUT_FOLDER . 'errorRequireComplexClass.php'
        ],
        'complex use case' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireComplexUseCase.php',
          self::formatString(
            self::REQUIRE_COMPLEX_USE_CASE,
            [
              [self::FILE_VENDOR_COMPLEX_USE_CASE, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
              [self::FILE_VENDOR_COMPLEX_USE_CASE_BIS, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS],
              [self::FILE_ALL_CONFIG, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS + self::ONE_INDENT],
              [self::FILE_PROD_ALL_CONFIG, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS + self::TWO_INDENTS]
            ]
          ),
          BASE_PATH . self::REQUIRE_COMPLEX_USE_CASE,
          self::OUTPUT_FOLDER . 'errorRequireComplexUseCase.php'
        ],
        'require MasterController' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireMasterController.php',
          self::formatString(
            self::REQUIRE_MASTER_CONTROLLER,
            [
              ['src/MasterController.php', self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
              ['config/AllConfig.php', self::VIA_USE_STATEMENT, self::TWO_INDENTS],
              ['config/prod/AllConfig.php', self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS + self::ONE_INDENT],
              ['src/templating/HtmlMinifier.php', self::VIA_USE_STATEMENT, self::TWO_INDENTS],
              ['src/services/securityService.php', self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS],
              ['config/AllConfig.php', self::VIA_USE_STATEMENT . self::ALREADY_PARSED, self::TWO_INDENTS + self::ONE_INDENT],
              ['src/MasterController.php', self::VIA_USE_STATEMENT . self::ALREADY_PARSED, self::TWO_INDENTS + self::ONE_INDENT],
              ['src/views/profiler/templateStructure/visualRendering.php', self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS],
              ['src/Session.php', self::VIA_STATIC_DIRECT_CALL, self::TWO_INDENTS],
              ['src/tools/isSerialized.php', self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS + self::ONE_INDENT],
              ['src/console/colors.php', self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS + self::ONE_INDENT],
              ['src/console/tools.php', self::VIA_REQUIRE_INCLUDE_STATEMENT, self::TWO_INDENTS + self::ONE_INDENT],
            ]
          ),
          BASE_PATH . self::REQUIRE_MASTER_CONTROLLER,
          self::OUTPUT_FOLDER . 'errorRequireMasterController.php'
        ],
        'class names conflicts' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireClassNameConflict.php',
          self::formatString(
            self::REQUIRE_CLASS_NAME_CONFLICT,
            [
              [self::FILE_VENDOR_CLASS_NAME_CONFLICT, self::VIA_USE_STATEMENT, self::ONE_INDENT],
            ],
//            CLI_INFO_HIGHLIGHT . 'EXTERNAL LIBRARY CLASS : ' . 
//            self::CLASS_VENDOR_CLASS_NAME_CONFLICT . END_COLOR . PHP_EOL
          ),
          BASE_PATH . self::REQUIRE_CLASS_NAME_CONFLICT,
          self::OUTPUT_FOLDER . 'errorRequireClassNameConflict.php'
        ],
        // Testing constants, parentheses, classic operators, logical operators, multi-line declarations, single line
        // declarations, declarations blocks separated by other code, arrays, constants names conflicts, constants that
        // contain constants, concatenation. All that in a namespace block. Testing also when constants are defined on
        // different files.
        'constant duplications' =>
        [
          self::OUTPUT_FOLDER . 'OutputRequireConstantDuplications.php',
          self::formatString(
            self::REQUIRE_CONSTANT_DUPLICATIONS,
            [
              [self::FILE_VENDOR_CONSTANT_DUPLICATIONS, self::VIA_REQUIRE_INCLUDE_STATEMENT, self::ONE_INDENT],
            ]
          ),
          BASE_PATH . self::REQUIRE_CONSTANT_DUPLICATIONS,
          self::OUTPUT_FOLDER . 'errorRequireConstantDuplications.php'
        ]
      ];
    }
  }
}
