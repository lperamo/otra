<?php
declare(strict_types=1);

// variables declaration
$blabla = "blabla";
$superCool = 'superCool';

// comments
echo $blabla;
echo $blabla . $superCool;
echo $superCool;

/**
 * @return string
 */
function testFunction()
{
  return 'test';
}

echo testFunction();
