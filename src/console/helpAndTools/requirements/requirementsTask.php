<?php
declare(strict_types=1);

/**
 * @author Lionel Péramo
 * @package otra\console\helpAndTools
 */

echo ADD_BOLD, CLI_INFO_HIGHLIGHT, '  Requirements', PHP_EOL,
    '  ------------', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;

  require CORE_PATH . 'tools/cli.php';
const REQUIREMENTS_PADDING = 30;

  // Requirement array
const REQ_PKG_NAME = 0;
const REQ_NAME = 1;
const REQ_DESC = 2;
const REQ_CHECK_TYPE = 3;

  // Checks types
const REQ_PACKAGE = 0;
const REQ_PHP_VERSION = 1;
const REQ_PHP_LIB = 2;

const OTRA_REQUIREMENTS = [
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
    'Zend OPcache',
    'PHP extension \'zend-opcache\'',
    CLI_INFO_HIGHLIGHT . '[Optional]' . CLI_INFO . ' Needed to use the preloading feature available since PHP 7.4',
    REQ_PHP_LIB
  ],
  [
    'PHP Version => 8.0',
    'PHP version 8.0.x+',
    'PHP version must be at least 8.0.x.',
    REQ_PHP_VERSION
  ]
];

  echo CLI_INFO;

  // For Windows, it returns WINNT
const OTRA_SEARCHING_COMMAND = (PHP_OS === 'Linux') ? 'which ' : 'where ';

  foreach (OTRA_REQUIREMENTS as $requirement)
  {
    echo ADD_BOLD;

    /** @var string $error */
    // different check whether it's a PHP src or a program
    if ($requirement[REQ_CHECK_TYPE] === REQ_PKG_NAME)
      [$error,] = cliCommand(OTRA_SEARCHING_COMMAND . $requirement[REQ_PKG_NAME], null, false);
    elseif ($requirement[REQ_CHECK_TYPE] === REQ_PHP_VERSION)
      [$error,] = cliCommand('php -v | egrep -o "PHP\ [8-9]\.[0-9]{1,}\.[0-9]{1,}"', null, false);
    elseif ($requirement[REQ_CHECK_TYPE] === REQ_PHP_LIB)
      [$error,] = cliCommand('php -m | grep "' . $requirement[REQ_PKG_NAME] . '"', null, false);

    echo $error !== 1
      ? CLI_SUCCESS . '  ✔  '
      : CLI_ERROR . '  ⨯  ', REMOVE_BOLD_INTENSITY, CLI_INFO,
    str_pad($requirement[REQ_NAME] . ' ', REQUIREMENTS_PADDING, '.'), ' ',
    $requirement[REQ_DESC], PHP_EOL;
  }

  // a last line break in addition to space the whole thing.
  echo PHP_EOL;

