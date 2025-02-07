<?php
declare(strict_types=1);

namespace src;

use DateTime;
use otra\{OtraException, Session};
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use const otra\cache\php\{APP_ENV, CACHE_PATH, CORE_PATH, PROD, TEST_PATH};
use const otra\config\VERSION;
use function otra\console\convertLongArrayToShort;
use function otra\tools\isSerialized;

/**
 * @author Lionel Péramo
 * @runTestsInSeparateProcesses
 */
class SessionTest extends TestCase
{
  private const int
    ROUNDS = 4, // 4 is the minimum to make the Blowfish algorithm work
    BAR = 1; // testing via an int instead of a string is important to fully test deserialization
    
  private const string
    SESSIONS_CACHE_PATH = CACHE_PATH . 'php/sessions/',
    BLOWFISH_ALGORITHM = '$2y$0' . self::ROUNDS . '$',
    TEST = 'test',
    TEST2 = 'test2',
    SESSION_FILE_BEGINNING = '<?php declare(strict_types=1);namespace otra\cache\php\sessions;';

  private static DateTime $fooThing;
  private static array $testAndTest2;

  protected function setUp(): void
  {
    parent::setUp();
    $_SERVER[APP_ENV] = PROD;
    $_SERVER['REMOTE_ADDR'] = '::1';
    require TEST_PATH . 'config/AllConfigGood.php';
    self::$fooThing = new Datetime('2021-12-09');
    self::$testAndTest2 = [
      self::TEST => self::$fooThing,
      self::TEST2 => self::BAR
    ];

    // cleaning sessions folder
    array_map(unlink(...), glob(self::SESSIONS_CACHE_PATH . '*.php'));
  }

  protected function tearDown(): void
  {
    parent::tearDown();
    array_map(unlink(...), glob(self::SESSIONS_CACHE_PATH . '*.php'));
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testInit() : void
  {
    // context
    $sessionId = session_id();
    $sessionsFile = self::SESSIONS_CACHE_PATH . sha1('ca' . $sessionId . VERSION . 'che') . '.php';

    // launching
    ob_start();
    Session::init(self::ROUNDS);
    $output = ob_get_clean();

    // testing
    $reflectedClass = (new ReflectionClass(Session::class));
    self::assertSame(
      self::SESSIONS_CACHE_PATH,
      $reflectedClass->getProperty('sessionsCachePath')->getValue()
    );
    self::assertSame(
      self::BLOWFISH_ALGORITHM,
      $reflectedClass->getProperty('blowfishAlgorithm')->getValue()
    );
    self::assertSame(
      $sessionId,
      $reflectedClass->getProperty('sessionId')->getValue()
    );
    self::assertSame(
      $sessionsFile,
      $reflectedClass->getProperty('sessionFile')->getValue()
    );
    $identifier = $reflectedClass->getProperty('identifier')->getValue();
    self::assertIsString($identifier);
    self::assertMatchesRegularExpression(
      '@[0-9a-zA-Z]{22}@',
      $identifier
    );
    self::assertFileExists($sessionsFile);
    self::assertStringStartsWith(
      self::SESSION_FILE_BEGINNING,
      file_get_contents($sessionsFile)
    );

    // session file permissions must be 0775
    self::assertSame(0775, fileperms($sessionsFile) & 0777);

    // There should be no output
    self::assertSame('', $output, 'The function should not output anything.');
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testInit_SessionFileAlreadyExists() : void
  {
    // context
    require_once CORE_PATH . 'console/colors.php';
    require_once CORE_PATH . 'console/tools.php';
    $sessionId = session_id();
    $sessionsFile = self::SESSIONS_CACHE_PATH . sha1('ca' . $sessionId . VERSION . 'che') . '.php';
    $_SERVER['REMOTE_ADDR'] = '::1';
    $dataInformation = convertLongArrayToShort(
      [
        ...self::$testAndTest2,
        'otra_i' => bin2hex(openssl_random_pseudo_bytes(11)),
        'otra_b' => self::BLOWFISH_ALGORITHM
      ]
    );
    $fileContent = self::SESSION_FILE_BEGINNING;
    $requires = $useStatements = '';

    file_put_contents(
      $sessionsFile,
      $fileContent . $useStatements . $requires . 'return ' . $dataInformation . ';' . PHP_EOL
    );
    chmod($sessionsFile, 0775);

    // launching
    ob_start();
    Session::init(self::ROUNDS);
    $output = ob_get_clean();

    // testing
    $reflectedClass = (new ReflectionClass(Session::class));
    self::assertSame(
      self::SESSIONS_CACHE_PATH,
      $reflectedClass->getProperty('sessionsCachePath')->getValue()
    );
    self::assertSame(
      self::BLOWFISH_ALGORITHM,
      $reflectedClass->getProperty('blowfishAlgorithm')->getValue()
    );
    self::assertSame(
      $sessionId,
      $reflectedClass->getProperty('sessionId')->getValue()
    );
    self::assertSame(
      $sessionsFile,
      $reflectedClass->getProperty('sessionFile')->getValue()
    );
    $identifier = $reflectedClass->getProperty('identifier')->getValue();
    self::assertIsString($identifier);
    self::assertMatchesRegularExpression(
      '@[0-9a-zA-Z]{22}@',
      $identifier
    );
    self::assertFileExists($sessionsFile);

    $saltForHash = self::BLOWFISH_ALGORITHM . $reflectedClass->getProperty('identifier')->getValue();
    $hashedFirstValue = crypt(serialize(self::$fooThing), $saltForHash);
    $hashedSecondValue = crypt(serialize(self::BAR), $saltForHash);
    $testedArray = $reflectedClass->getProperty('matches')->getValue();

    // Testing if we have the same numbers of keys, and same keys names
    self::assertSame([self::TEST, self::TEST2], array_keys($testedArray));
    self::assertSame(['hashed', 'notHashed'], array_keys($testedArray[self::TEST]));

    // Testing the values of the array
    self::assertSame($hashedFirstValue, $testedArray[self::TEST]['hashed']);
    self::assertEquals(self::$fooThing, $testedArray[self::TEST]['notHashed']);
    self::assertSame(
      [
        'hashed' => crypt(serialize(self::BAR), $saltForHash),
        'notHashed' => self::BAR
      ],
      $testedArray[self::TEST2]
    );

    // There should be no output
    self::assertSame('', $output, 'The function should not output anything.');
    self::assertSame(
      $hashedFirstValue,
      $_SESSION[self::TEST]
    );
    self::assertSame(
      $hashedSecondValue,
      $_SESSION[self::TEST2]
    );
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testInit_badNumberOfRounds(): void
  {
    // testing
    $this->expectException(OtraException::class);
    $this->expectExceptionMessage('Rounds must be in the range 4-31');

    // launching
    Session::init(3);
  }

  /**
   * Testing two initializations to prevent a second initialization from resetting values that were previously put in
   * memory
   *
   * @depends testInit
   * @throws OtraException|ReflectionException
   */
  public function testInit_TwoInits() : void
  {
    // context
    Session::init(self::ROUNDS);
    Session::set(self::TEST, self::TEST);

    // launching
    Session::init();

    // testing
    self::assertSame(
      self::TEST,
      Session::get(self::TEST)
    );
  }

  /**
   * @depends testInit
   * @throws OtraException|ReflectionException
   */
  public function testSet() : void
  {
    // context
    Session::init(self::ROUNDS);

    // launching
    Session::set(self::TEST, self::$fooThing);

    // testing
    $reflectedClass = (new ReflectionClass(Session::class));
    self::assertSame(
      self::TEST,
      array_search(
        crypt(
          serialize(self::$fooThing),
          self::BLOWFISH_ALGORITHM . $reflectedClass->getProperty('identifier')->getValue()
        ),
        $_SESSION
      )
    );
  }

  /**
   * @depends testInit
   * @throws OtraException|ReflectionException
   */
  public function testSet_Overwrite() : void
  {
    // context
    Session::init(self::ROUNDS);
    Session::set(self::TEST, 'test');

    // launching
    Session::set(self::TEST, self::$fooThing);

    // testing
    $reflectedClass = (new ReflectionClass(Session::class));
    self::assertSame(
      self::TEST,
      array_search(
        crypt(
          serialize(self::$fooThing),
          self::BLOWFISH_ALGORITHM . $reflectedClass->getProperty('identifier')->getValue()
        ),
        $_SESSION
      )
    );
  }

  /**
   * @depends testInit
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testSets() : void
  {
    // context
    Session::init(self::ROUNDS);

    // launching
    Session::sets(self::$testAndTest2);

    // testing
    $reflectedClass = (new ReflectionClass(Session::class));
    $saltForHash = self::BLOWFISH_ALGORITHM . $reflectedClass->getProperty('identifier')->getValue();
    self::assertSame(
      self::TEST,
      array_search(
        crypt(serialize(self::$fooThing), $saltForHash),
        $_SESSION
      )
    );

    self::assertSame(
      self::TEST2,
      array_search(
        crypt(serialize(self::BAR), $saltForHash),
        $_SESSION
      )
    );
  }

  /**
   * @depends testInit
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testSets_Overwrite() : void
  {
    // context
    Session::init(self::ROUNDS);
    Session::sets([
      self::TEST => 'test',
      self::TEST2 => 'test'
    ]);

    // launching
    Session::sets(self::$testAndTest2);

    // testing
    $reflectedClass = (new ReflectionClass(Session::class));
    $saltForHash = self::BLOWFISH_ALGORITHM . $reflectedClass->getProperty('identifier')->getValue();
    self::assertSame(
      self::TEST,
      array_search(
        crypt(serialize(self::$fooThing), $saltForHash),
        $_SESSION
      )
    );

    self::assertSame(
      self::TEST2,
      array_search(
        crypt(serialize(self::BAR), $saltForHash),
        $_SESSION
      )
    );
  }

  /**
   * @depends testInit
   * @throws OtraException|ReflectionException
   */
  public function testUnset(): void
  {
    // context
    Session::init(self::ROUNDS);
    Session::set(self::TEST, self::$fooThing);
    Session::set(self::TEST2, self::BAR);

    // launching
    Session::unset(self::TEST);

    // testing
    $reflectedClass = new ReflectionClass(Session::class);
    $matches = $reflectedClass->getProperty('matches')->getValue();

    // Check that the key is removed from self::$matches
    self::assertArrayNotHasKey(self::TEST, $matches);

    // Check that the key is removed from $_SESSION
    self::assertArrayNotHasKey(self::TEST, $_SESSION);

    // Check that the other key still exists
    self::assertArrayHasKey(self::TEST2, $matches);
    self::assertArrayHasKey(self::TEST2, $_SESSION);
  }


  /**
   * @depends testInit
   * @depends testSet
   * @throws OtraException|ReflectionException
   */
  public function testGet(): void
  {
    // context
    Session::init(self::ROUNDS);
    Session::set(self::TEST, self::$fooThing);

    // launching AND testing
    self::assertSame(self::$fooThing, Session::get(self::TEST));
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testGetIfExists_Yes(): void
  {
    // context
    Session::init(self::ROUNDS);
    Session::set(self::TEST, self::$fooThing);

    // launching AND testing
    self::assertSame(
      [true, self::$fooThing],
      Session::getIfExists(self::TEST)
    );
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testGetIfExists_No(): void
  {
    // context
    Session::init(self::ROUNDS);

    // testing
    self::assertSame(
      [false, null],
      Session::getIfExists(self::TEST)
    );
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testGetArrayIfExists(): void
  {
    // context
    Session::init(self::ROUNDS);
    Session::sets(self::$testAndTest2);

    // launching AND testing
    self::assertSame(
      [true, self::$testAndTest2],
      Session::getArrayIfExists([self::TEST, self::TEST2])
    );
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testGetArrayIfExists_No(): void
  {
    // context
    Session::init(self::ROUNDS);
    Session::sets(self::$testAndTest2);

    // launching AND testing
    self::assertSame(
      [false, null],
      Session::getArrayIfExists([self::BAR, self::TEST2])
    );
  }

  /**
   * @throws OtraException|ReflectionException
   */
  public function testGetAll(): void
  {
    // context
    Session::init(self::ROUNDS);
    Session::sets(self::$testAndTest2);

    // launching AND testing
    self::assertSame(
      self::$testAndTest2,
      Session::getAll()
    );
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public function testClean(): void
  {
    // context
    Session::init(self::ROUNDS);
    Session::sets(self::$testAndTest2);
    Session::toFile();
    $reflectedClass = (new ReflectionClass(Session::class));
    $sessionFile = $reflectedClass->getProperty('sessionFile')->getValue();

    // launching
    Session::clean();

    // testing
    self::assertArrayNotHasKey(
      self::TEST,
      $_SESSION
    );
    self::assertEmpty(
      $reflectedClass->getProperty('matches')->getValue()
    );
    self::assertNull($reflectedClass->getProperty('identifier')->getValue());
    self::assertNull($reflectedClass->getProperty('blowfishAlgorithm')->getValue());
    self::assertNull($reflectedClass->getProperty('sessionId')->getValue());
    self::assertNull($reflectedClass->getProperty('sessionFile')->getValue());
    self::assertFileDoesNotExist($sessionFile);
  }

  /**
   * @throws OtraException
   * @throws ReflectionException
   */
  public static function testToFile(): void
  {
    // context
    $sessionId = session_id();
    $sessionsFile = self::SESSIONS_CACHE_PATH . sha1('ca' . $sessionId . VERSION . 'che') . '.php';
    Session::init(self::ROUNDS);
    Session::sets(self::$testAndTest2);
    $_SERVER['REMOTE_ADDR'] = '::1';

    // launching
    Session::toFile();

    // testing
    self::assertFileExists($sessionsFile);
    $reflectedClass = (new ReflectionClass(Session::class));
    $identifier = $reflectedClass->getProperty('identifier')->getValue();
    $dataFromFile = require $sessionsFile;
    require_once CORE_PATH . 'tools/isSerialized.php';

    // Un-serialize objects
    foreach($dataFromFile as &$datum)
    {
      if (isSerialized($datum))
        $datum = unserialize($datum);
    }
    // testing that the count of keys is the same, and that the keys' names are the same
    self::assertSame(
      [
        self::TEST,
        self::TEST2,
        'otra_i',
        'otra_b'
      ],
      array_keys($dataFromFile)
    );

    // testing the values
    self::assertEquals(self::$fooThing, $dataFromFile[self::TEST]);
    self::assertSame(self::BAR, $dataFromFile[self::TEST2]);
    self::assertSame($identifier, $dataFromFile['otra_i']);
    self::assertSame(self::BLOWFISH_ALGORITHM, $dataFromFile['otra_b']);

    // session file permissions must be 0775
    self::assertSame(0775, fileperms($sessionsFile) & 0777);
  }
}
