<?php
  echo ADD_BOLD, CLI_BOLD_LIGHT_CYAN, '  Requirements', PHP_EOL,
    '  ------------', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;

  require CORE_PATH . 'tools/cli.php';
  define('CLI_ERROR_CODE', 0);
  define('REQUIREMENTS_PADDING', 30);

  // Requirement array
  define('REQ_PKG_NAME', 0);
  define('REQ_NAME', 1);
  define('REQ_DESC', 2);
  define('REQ_CHECK_TYPE', 3);

  // Checks types
  define('REQ_PACKAGE', 0);
  define('REQ_PHP_VERSION', 1);
  define('REQ_PHP_LIB', 2);

  $requirements = [
    [
      'java',
      'JAVA',
      'Software platform => https://www.java.com. Only needed for optimizations with Google Closure Compiler.',
      REQ_PACKAGE
    ],
    [
      'tsc',
      'Typescript',
      'Only needed to contribute. TypeScript is a typed superset of JavaScript that compiles to plain JavaScript. => http://www.typescriptlang.org/',
      REQ_PACKAGE
    ],
    [
      'sass',
      'SASS/SCSS',
      'Only needed to contribute. It is a stylesheet language that’s compiled to CSS => https://sass-lang.com/',
      REQ_PACKAGE
    ],
    [
      'fileinfo',
      'PHP extension \'mbstring\'',
      'Needed for analyzing mime types',
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
      'Needed for OTRA watcher on unix like systems.',
      REQ_PHP_LIB
    ],
    [
      'PHP Version => 7.3',
      'PHP version 7.3.x+',
      'PHP version must be at least 7.3.x.',
      REQ_PHP_VERSION
    ]
  ];

  echo CLI_LIGHT_BLUE;

  foreach ($requirements as &$requirement)
  {
    echo ADD_BOLD;

    // different check whether it's a PHP src or a program
    if ($requirement[REQ_CHECK_TYPE] === REQ_PKG_NAME)
      $error = cli('which ' . $requirement[REQ_PKG_NAME]);
    elseif ($requirement[REQ_CHECK_TYPE] === REQ_PHP_VERSION)
      $error = cli('php -v | egrep -o "PHP\ [7-9]\.[0-9]{1,}\.[0-9]{1,}"');
    elseif ($requirement[REQ_CHECK_TYPE] === REQ_PHP_LIB)
      $error = cli('php -m | grep "' . $requirement[REQ_PKG_NAME] . '"');

    echo $error[CLI_ERROR_CODE] === 0
      ? CLI_GREEN . '  ✔  '
      : CLI_RED . '  ⨯  ', REMOVE_BOLD_INTENSITY, CLI_LIGHT_BLUE,
    str_pad($requirement[REQ_NAME] . ' ', REQUIREMENTS_PADDING, '.'), ' ',
    $requirement[REQ_DESC], PHP_EOL;
  }

  // a last line break in addition to space the whole thing.
  echo PHP_EOL;
?>
