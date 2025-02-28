<?php
/**
 * @author  Lionel Péramo
 * @package otra\console\helpAndTools
 */
declare(strict_types=1);

namespace otra\console\helpAndTools\requirements;

use otra\OtraException;
use const otra\cache\php\CORE_PATH;
use const otra\console\{ADD_BOLD, CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_SUCCESS, REMOVE_BOLD_INTENSITY};
use function otra\tools\cliCommand;

const
  REQUIREMENTS_PADDING = 30,

    // Requirement array
  REQ_PKG_NAME = 0,
  REQ_NAME = 1,
  REQ_DESC = 2,
  REQ_CHECK_TYPE = 3,

    // Checking types
  REQ_PACKAGE = 0,
  REQ_PHP_VERSION = 1,
  REQ_PHP_LIB = 2,

  OTRA_REQUIREMENTS = [
    [
      'java',
      'JAVA',
      'Software platform => https://www.java.com. Only needed for optimizations with Google Closure Compiler.',
      REQ_PACKAGE
    ],
    [
      'tsc',
      'Typescript',
      'Only needed to contribute. TypeScript is a typed superset of JavaScript that compiles to plain JavaScript. => https://www.typescriptlang.org/',
      REQ_PACKAGE
    ],
    [
      'sass',
      'SASS/SCSS',
      'Only needed to contribute. It is a stylesheet language that\'s compiled to CSS => https://sass-lang.com/',
      REQ_PACKAGE
    ],
    [
      'fileinfo',
      'PHP extension \'fileinfo\'',
      'Needed for analyzing MIME types',
      REQ_PHP_LIB
    ],
    [
      'brotli',
      'PHP extension \'brotli\'',
      'Needed for compressing files',
      REQ_PHP_LIB
    ],
    [
      'json',
      'PHP extension \'json\'',
      'Needed for encoding/decoding JSON format. (needed by the developer toolbar)',
      REQ_PHP_LIB
    ],
    [
      'mbstring',
      'PHP extension \'mbstring\'',
      'Needed for string multibyte functions',
      REQ_PHP_LIB
    ],
    [
      'inotify',
      'PHP extension \'inotify\'',
      CLI_INFO_HIGHLIGHT . '[Optional]' . CLI_INFO . ' Needed for OTRA watcher on unix like systems.',
      REQ_PHP_LIB
    ],
    [
      'intl',
      'PHP extension \'intl\'',
      CLI_INFO_HIGHLIGHT . '[Optional]' . CLI_INFO .
      ' Needed for Rector, a library for instant upgrades and automated refactoring.',
      REQ_PHP_LIB
    ],
    [
      'Zend OPcache',
      'PHP extension \'zend-opcache\'',
      CLI_INFO_HIGHLIGHT . '[Optional]' . CLI_INFO . ' Needed to use the preloading feature available since PHP 7.4',
      REQ_PHP_LIB
    ],
    [
      'PHP Version => 8.2',
      'PHP version 8.2.x+',
      'PHP version must be at least 8.2.x.',
      REQ_PHP_VERSION
    ],
    [
      'parallel',
      '\'parallel\' tool',
      CLI_INFO_HIGHLIGHT . '[Optional]' . CLI_INFO .
      ' Needed to use the OTRA task `convertImages` as we use this to speed up the process',
      REQ_PACKAGE
    ]
  ],
  OTRA_SEARCHING_COMMAND = (PHP_OS === 'Linux') ? 'which ' : 'where ';

/**
 * @throws OtraException
 * @return void
 */
function requirements() : void
{
  echo ADD_BOLD, CLI_INFO_HIGHLIGHT, '  Requirements', PHP_EOL, '  ------------', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;
  require CORE_PATH . 'tools/cli.php';
  echo CLI_INFO;

  // For Windows, it returns WINNT
  foreach (OTRA_REQUIREMENTS as $requirement)
  {
    echo ADD_BOLD;

    /** @var string $error */
    // different check whether it's a PHP src or a program
    if ($requirement[REQ_CHECK_TYPE] === REQ_PKG_NAME)
      [$error,] = cliCommand(
        OTRA_SEARCHING_COMMAND . $requirement[REQ_PKG_NAME],
        null,
        false
      );
    elseif ($requirement[REQ_CHECK_TYPE] === REQ_PHP_VERSION)
      [$error,] = cliCommand(
        'php -v | egrep -o "PHP\ 8\.[2-9]{1,}\.[0-9]{1,}"',
        null,
        false
      );
    elseif ($requirement[REQ_CHECK_TYPE] === REQ_PHP_LIB)
      [$error,] = cliCommand(
        'php -m | grep "' . $requirement[REQ_PKG_NAME] . '"',
        null,
        false
      );

    echo $error !== 1
      ? CLI_SUCCESS . '  ✔  '
      : CLI_ERROR . '  ⨯  ', REMOVE_BOLD_INTENSITY, CLI_INFO,
    str_pad($requirement[REQ_NAME] . ' ', REQUIREMENTS_PADDING, '.'), ' ',
    $requirement[REQ_DESC], PHP_EOL;
  }

  // a last line break in addition to space the whole thing.
  echo PHP_EOL;
}
