<?php

define('XDEBUG_VAR_DISPLAY_MAX_DATA', 'xdebug.var_display_max_data');
define('XDEBUG_VAR_DISPLAY_MAX_CHILDREN', 'xdebug.var_display_max_children');

/**
 * @param string $message
 */
function lg(string $message) : void
{
  require_once CORE_PATH . 'Logger.php';
  otra\Logger::logTo($message, 'trace');
}

/**
 * A nice dump function that takes as much parameters as we want to put.
 * Somewhat disables XDebug if some parameters are true.
 *
 * @param bool $maxData     Affects the maximum string length that is shown when variables are displayed
 * @param bool $maxChildren Affects the amount of array children and object's properties are shown
 *                          when variables are displayed
 * @param array ...$args
 */

function dump(bool $maxData = false, bool $maxChildren = false, ... $args) : void
{
  /** @var string $oldXDebugMaxData  */
  if (true === $maxData)
  {
    $oldXDebugMaxData = ini_get(XDEBUG_VAR_DISPLAY_MAX_DATA);
    ini_set(XDEBUG_VAR_DISPLAY_MAX_DATA, -1);
  }

  /** @var string $oldXDebugMaxChildren  */
  if (true === $maxChildren)
  {
    $oldXDebugMaxChildren = ini_get(XDEBUG_VAR_DISPLAY_MAX_CHILDREN);
    ini_set(XDEBUG_VAR_DISPLAY_MAX_CHILDREN, -1);
  }

  call_user_func_array('cli' === PHP_SAPI ? 'dumpSmallCli' : 'dumpSmall', $args);

  if (true === $maxData)
    ini_set(XDEBUG_VAR_DISPLAY_MAX_DATA, $oldXDebugMaxData);

  if (true === $maxChildren)
    ini_set(XDEBUG_VAR_DISPLAY_MAX_CHILDREN, $oldXDebugMaxChildren);
}

/**
 * Returns file and line of the caller for debugging purposes.
 *
 * @return string
 */
function getCaller()
{
  $secondTrace = debug_backtrace()[1];

  return $secondTrace['file'] . ':' . $secondTrace['line'];
}

/**
 * A nice dump function that takes as much parameters as we want to put.
 */
function dumpSmall()
{
  $args = func_get_args();
  $secondTrace = debug_backtrace()[1];
  echo '<pre>', '<p style="color:#3377FF">', 'OTRA DUMP - ' . $secondTrace['file'] . ':' . $secondTrace['line'], '</p>';

  foreach ($args as &$param)
  {
    var_dump(is_string($param) ? htmlspecialchars($param) : $param);
    echo '<br />';
  }

  echo '</pre>';
}

function dumpSmallCli()
{
  $args = func_get_args();
  $secondTrace = debug_backtrace()[1];
  echo 'OTRA DUMP - ' . $secondTrace['file'] . ':' . $secondTrace['line'] . PHP_EOL;

  foreach ($args as &$param)
  {
    var_dump($param);
  }
}

/**
 * Puts <br> between markups in order to add legibility to a code in debug mode and convert other markups in html
 * entities.
 *
 * @param string $stringToFormat The ... (e.g. : self::$template)
 *
 * @return string The formatted string
 */
function reformatSource(string $stringToFormat) : string
{
  return preg_replace('@&gt;\s*&lt;@', "&gt;<br/>&lt;", htmlspecialchars($stringToFormat));
}

/**
 * Converts a php array into stylish html table
 *
 * @param array  $dataToShow     Array to convert
 * @param string $title          Table name to show in the header
 * @param null   $indexToExclude Index to exclude from the render
 *
 * @return false|string
 */
function createShowableFromArray(array &$dataToShow, string $title, $indexToExclude = null)
{
    ob_start();?>
    <table class="test innerHeader">
      <thead>
        <tr>
          <th colspan="3"><?= $title ?></th>
        </tr>
        <tr>
          <th>Name</th>
          <th>Index or value if array</th>
          <th>Value if array</th>
        </tr>
      </thead>
      <tbody>
    <?php
      recurArrayConvertTab($dataToShow, $indexToExclude);
    ?></tbody></table><?php
    return ob_get_clean();
}

/** Converts a php array into stylish console table. TODO finish it !
 *
 * @param null|array $dataToShow     Array to convert
 * @param string     $title          Table name to show in the header
 * @param string     $indexToExclude Index to exclude from the render
 */
function createShowableFromArrayConsole(?array &$dataToShow, string $title, $indexToExclude = null)
{
  return;

//  echo $title, PHP_EOL,
//    CLI_LIGHT_BLUE, '|', END_COLOR, ' Name' ,
//    CLI_LIGHT_BLUE, '|', END_COLOR, ' Index or value if array',
//    CLI_LIGHT_BLUE, '|', END_COLOR, ' Value if array', PHP_EOL;
  //recurArrayConvertTab($dataToShow, $indexToExclude);
}

/**
 * @param $index
 * @param $value
 *
 * @return int|string
 */
function getArgumentType(&$index, &$value)
{
  switch($index)
  {
    case 0:
      if (true === is_int($value) && true === isset(otra\OtraException::$codes[$value]))
      {
        $value = otra\OtraException::$codes[$value];
        return 'Error type';
      }

      return $index;
    case 1: return 'Error';
    case 2: return 'File';
    case 3: return 'Line';
    case 4: return 'Arguments';
  }
}

/** Recursive function that converts a php array into a stylish tbody
 *
 * @param array|object $data           Array or object to convert
 * @param int|string   $indexToExclude Index to exclude from the render
 * @param int          $loop           Number of recursions
 *
 * @return int
 */
function recurArrayConvertTab($data, $indexToExclude = null, int $loop = -1)
{
  $i = 0;
  $oldLoop = $loop;
  ++$loop;

  // We cannot use a reference for $datum as it can be an iterator
  foreach ($data as $index => $datum)
  {
    if ($index === $indexToExclude)
      continue;

    $datum = (true === is_array($datum)
      || true === is_object($datum)
      || true === is_numeric($datum)
      )
      ? $datum
      : '\'' . $datum . '\'';

    // End of the table that shows the inner headers
    if (0 === $loop)
    {
      ?> </tbody></table><table class="test"><tbody><?php
    }

    if ((true === is_array($datum) || true === is_object($datum)) && false === empty($datum))
    {
        if (1 === $loop)
        {
          // if we have lost one dimension
          if ($loop < $oldLoop)
            echo '<tr class="foldable">',
                   '<td colspan="' , $loop , '"></td>',
                   '<td>' , $index, '</td>',
                 '</tr>';
          else
            echo '<td>' , $index, '</td>',
                 '<td colspan="0" class="dummy"></td>',
              '</tr>';
        } elseif ($loop > 1)
          echo '<tr class="foldable">',
                 '<td colspan="', $loop, '"></td>',
                 '<td colspan="0">', $index,  '</td>',
                 '<td colspan="0" class="dummy"></td>',
               '</tr>';
        else
          echo '<tr class="foldable no-dummy">',
                 '<td colspan="">Index:' ,
                    is_numeric($index) ? getArgumentType($index, $datum) : $index,
                  ', Loop:', $loop,
                 '</td>';

        $oldLoop = recurArrayConvertTab($datum, $indexToExclude, $loop);
    } else
    {
      if (true === is_array($datum))
        $datum = 'Empty';

//    if (0 === $loop)
      echo '<tr class="no-dummy" >',
             '<td>', getArgumentType($index, $datum), '</td>',
             '<td colspan="2">', $datum , '</td>',
           '</tr>';

    }

    ++$i;
  }

  return $oldLoop;
}

/**
 * @param bool $noErrors
 *
 * @return bool
 */
function debug(bool $noErrors = true) : bool
{
  if (true === $noErrors)
    error_reporting(0);

  return 'dev' === $_SERVER[APP_ENV];
}

  /**
   * Slightly modified version of the original.
   * No verification of the fileDescriptor, we must check that before.
   *
   * @param string $filepath
   * @param int    $lines
   *
   * @return string
   * @link    http://stackoverflow.com/a/15025877/995958
   * @license http://creativecommons.org/licenses/by/3.0/
   * @author  Torleif Berger, Lorenzo Stanco, Lionel Péramo
   */
function tailCustom(string $filepath, int $lines = 1) : string
{
  $fileDescriptor = fopen($filepath, "rb");

  // Sets buffer size, according to the number of lines to retrieve.
  // This gives a performance boost when reading a few lines from the file.
  $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

  // Jump to last character
  fseek($fileDescriptor, -1, SEEK_END);

  // Read it and adjust line number if necessary
  // (Otherwise the result would be wrong if file doesn't end with a blank line)
  if (fread($fileDescriptor, 1) !== PHP_EOL)
    --$lines;

  // Start reading
  $output = $chunk = '';

  // While we would like more
  while (ftell($fileDescriptor) > 0 && $lines >= 0)
  {
    // Figure out how far back we should jump
    $seek = min(ftell($fileDescriptor), $buffer);

    // Do the jump (backwards, relative to where we are)
    fseek($fileDescriptor, -$seek, SEEK_CUR);

    // Read a chunk and prepend it to our output
    $output = ($chunk = fread($fileDescriptor, $seek)) . $output;

    // Jump back to where we started reading
    fseek($fileDescriptor, -mb_strlen($chunk, '8bit'), SEEK_CUR);

    // Decrease our line counter
    $lines -= substr_count($chunk, PHP_EOL);
  }

  // Close file
  fclose($fileDescriptor);

  // While we have too many lines
  // (Because of buffer size we might have read too many)
  while ($lines++ < 0)
  {
    // Find first newline and remove all text before that
    $output = substr($output, strpos($output, PHP_EOL) + 1);
  }

  return trim($output);
}
