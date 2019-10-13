<?php
  echo ADD_BOLD, CLI_BOLD_LIGHT_CYAN, '  Requirements', PHP_EOL,
    '  ------------', REMOVE_BOLD_INTENSITY, PHP_EOL, PHP_EOL;

  require CORE_PATH . 'tools/Cli.php';
  define('CLI_ERROR_CODE', 0);
  define('REQUIREMENTS_PADDING', 30);
  define('REQ_PKG_NAME', 0);
  define('REQ_NAME', 1);
  define('REQ_DESC', 2);
  define('REQ_PHP_LIB', 3);

  $requirements = [
    [
      'java',
      'JAVA',
      'Software platform => https://www.java.com. Only needed for optimizations with Google Closure Compiler.'
    ],
    [
      'tsc',
      'Typescript',
      'Only needed to contribute. TypeScript is a typed superset of JavaScript that compiles to plain JavaScript. => http://www.typescriptlang.org/'
    ],
    [
      'sass',
      'SASS/SCSS',
      'Only needed to contribute. It is a stylesheet language that’s compiled to CSS => https://sass-lang.com/'
    ],
    [
      'mbstring',
      'PHP extension \'mbstring\'',
      'Needed for string multibyte functions',
      true
    ],
    [
      'inotify',
      'PHP extension \'inotify\'',
      'Needed for OTRA watcher on unix like systems.',
      true
    ],
    [
      'PHP Version => 7.3',
      'PHP version 7.3.x',
      'PHP version must be 7.3.x, will be more flexible in the future.',
      true
    ]
  ];

  echo CLI_LIGHT_BLUE;

  foreach ($requirements as &$requirement)
  {
    echo ADD_BOLD;

    // different check whether it's a PHP lib or a program
    $error = cli(array_key_exists(REQ_PHP_LIB, $requirement) === true
        ? 'php -i | grep "PHP Version => 7.3"'
        : 'which ' . $requirement[REQ_PKG_NAME]
      )[CLI_ERROR_CODE] === 0;

    echo $error === true
      ? CLI_GREEN . '  ✔  '
      : CLI_RED . '  ⨯  ', REMOVE_BOLD_INTENSITY, CLI_LIGHT_BLUE,
    str_pad($requirement[REQ_NAME] . ' ', REQUIREMENTS_PADDING, '.'), ' ',
    $requirement[REQ_DESC], PHP_EOL;
  }

  // a last line break in addition to space the whole thing.
  echo PHP_EOL;
?>
