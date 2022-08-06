<?php
declare(strict_types=1);
namespace otra\tools\files;

/**
 * Compress PHP files contents. Beware, it makes some hard compression that can lead to false positives (in strings)
 */
function compressPHPFile(string $fileToCompress, string $outputFile) : void
{
  // php_strip_whitespace doesn't suppress double spaces in string and others. Beware of that rule, the preg_replace
  // is dangerous !
  // strips HTML comments that are not HTML conditional comments
  file_put_contents(
    $outputFile,
    rtrim(str_replace(
      [
        '; }',
        '} }',
        'strict_types=1); namespace',
        '?> <?php',
        '  ',
        '; ',
        'public function', // a function is public by default...
        'public static function',
        ' = ',
        'if (',
        ' { ',
        ' ?? ',
        ' === ',
        ' == ',
        ' && ',
        ' || '
      ],
      [
        ';}',
        '}}',
        'strict_types=1);namespace',
        '',
        ' ',
        ';',
        'function',
        'static function',
        '=',
        'if(',
        '{',
        '??',
        '===',
        '==',
        '&&',
        '||'
      ],
      preg_replace(
        [
          '@\s+@m',
          '@<!--.*?-->@',
          '@;\s(class\s[^\s]*) { @',
          '@(function\s[^\s]*) {\s*@'

        ],
        [
          ' ',
          '',
          ';$1{',
          '$1{'
        ],
        php_strip_whitespace($fileToCompress)
      ))) . PHP_EOL
  );
}
