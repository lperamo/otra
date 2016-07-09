<?
/**
 * @param string $message
 */
function lg(string $message)
{
  require_once CORE_PATH . 'Logger.php';
  lib\myLibs\Logger::logTo($message, 'trace');
}

/**
 * A nice dump function that takes as much parameters as we want to put. Somewhat disables XDebug.
 */
function dump()
{
  $oldXDebug = ini_get('xdebug.var_display_max_data');
  ini_set('xdebug.var_display_max_data', -1);

	call_user_func_array('dumpSmall', func_get_args());
  ini_set('xdebug.var_display_max_data', $oldXDebug);
}

/**
 * A nice dump function that takes as much parameters as we want to put.
 */
function dumpSmall()
{
  echo '<pre>';
  foreach (func_get_args() as &$param)
  {
    var_dump(is_string($param) ? htmlspecialchars($param) : $param);
    echo '<br />';
  }
  echo '</pre>';
}

/**
 * Puts new lines in order to add lisibility to a code in debug mode
 *
 * @param string $stringToFormat The ... (e.g. : self::$template
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
 * @param        $dataToShow     Array to convert
 * @param string $title          Table name to show in the header
 * @param null   $indexToExclude Index to exclude from the render
 */
function convertArrayToShowable(&$dataToShow, string $title, $indexToExclude = null)
{
//var_dump($dataToShow);
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
    <?
      recurArrayConvertTab($dataToShow, $indexToExclude);
    ?></tbody></table><?
    $dataToShow = ob_get_clean();
}

/** Converts a php array into stylish console table. TODO finish it !
 *
 * @param $dataToShow array  Array to convert
 * @param $title      string Table name to show in the header
 * @param $indexToExclude string Index to exclude from the render
 */
function convertArrayToShowableConsole(&$dataToShow, $title, $indexToExclude = null)
{
  return;

  echo $title, PHP_EOL,
    lightBlueText('|'), ' Name' ,
    lightBlueText('|'), ' Index or value if array',
    lightBlueText('|'), ' Value if array', PHP_EOL;
  //recurArrayConvertTab($dataToShow, $indexToExclude);
}

function getArgumentType(&$index, &$value)
{
  switch($index)
  {
    case 0:
      if (true === is_int($value) && true === isset(lib\myLibs\Lionel_Exception::$codes[$value]))
      {
        $value = lib\myLibs\Lionel_Exception::$codes[$value];
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
 * @param $donnees        array|object  Array or object to convert
 * @param $indexToExclude string        Index to exclude from the render
 * @param $loop           int           Number of recursions
 * @return int
 */
function recurArrayConvertTab($donnees, $indexToExclude = null, int $loop = -1)
{
  $i = 0;
  $oldLoop = $loop;
  ++$loop;

  foreach ($donnees as $index => &$donnee)
  {
    if ($index === $indexToExclude)
      continue;

    $donnee = (true === is_array($donnee) || true === is_object($donnee) || true === is_numeric($donnee)) ? $donnee : '\'' . $donnee . '\'';

    // End of the table that shows the inner headers
    if (0 === $loop)
    {
      ?> </tbody></table><table class="test"><tbody><?
    }

    if ((true === is_array($donnee) || true === is_object($donnee)) && false === empty($donnee))
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
        } else if ($loop > 1)
          echo '<tr class="foldable">',
                 '<td colspan="', $loop, '"></td>',
                 '<td colspan="0">', $index,  '</td>',
                 '<td colspan="0" class="dummy"></td>',
               '</tr>';
        else
          echo '<tr class="foldable no-dummy">',
                 '<td colspan="">Index:' , getArgumentType($index, $donnee), ', Loop:' . $loop . '</td>';

        $oldLoop = recurArrayConvertTab($donnee, $indexToExclude, $loop);
    } else
    {
      if (true === is_array($donnee))
        $donnee = 'Empty';

//      if (0 === $loop)
        echo '<tr class="no-dummy" >',
               '<td>', getArgumentType($index, $donnee), '</td>',
               '<td colspan="2">', $donnee , '</td>',
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

  return true === isset($_SESSION['debuglp_']) && 'Dev' === $_SESSION['debuglp_'];
}
?>
