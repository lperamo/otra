<?
function lg($message){
  require_once __DIR__ . '/Logger.php';
  lib\myLibs\core\Logger::logTo($message, 'trace');
};

/* A nice dump function that takes as much parameters as we want to put */
function dump()
{
	echo '<pre>';
	foreach (func_get_args() as $param)
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
function reformatSource($stringToFormat)
{
  return preg_replace('@&gt;\s*&lt;@', "&gt;<br/>&lt;", htmlspecialchars($stringToFormat));
}

/** Converts a php array into stylish html table
 *
 * @param $dataToShow array  Array to convert
 * @param $title      string Table name to show in the header
 * @param $indexToExclude string Index to exclude from the render
 */
function convertArrayToShowable(&$dataToShow, $title, $indexToExclude = null){
    ob_start();?>
    <table class="radius test">
      <thead>
        <tr class="head">
          <th colspan="3"><?= $title ?></th>
        </tr>
        <tr class="head">
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

/** Recursive function that converts a php array into a stylish tbody
*
* @param $donnees        array|object  Array or object to convert
* @param $indexToExclude string        Index to exclude from the render
* @param $boucle         int           Number of recursions
*/
function recurArrayConvertTab($donnees, $indexToExclude = null, $boucle = -1){
  $i = 0;
  $oldBoucle = $boucle;
  ++$boucle;
  foreach($donnees as $index => &$donnee)
  {
    if($index === $indexToExclude)
    {
      // foreach(array_keys($donnees[$index]) as $key) { unset($donnees[$key]); }
      // unset($donnees[$index]);
      continue;
    }

    if($boucle == 0)
    {
      echo '</tbody></table><table class="test"><tbody>';
    }
    if(is_array($donnee) || is_object($donnee))
    {
        if(1 == $boucle){
          if($boucle < $oldBoucle){
            echo '<tr class="foldable"><td colspan="' , $boucle , '"></td><td>\'' , $index, '\'</td></tr>';
          }
          else
            echo '<td>\'' , $index, '\'</td><td colspan="0" class="dummy"></td></tr>';
        }else if($boucle > 1)
          echo '<tr class="foldable"><td colspan="', $boucle, '"></td><td colspan="0">\'' , $index,  '\'</td><td colspan="0" class="dummy"></td></tr>';
        else
          echo '<tr class="foldable"><td>\'' , $index, '\'</td>';

        $oldBoucle = recurArrayConvertTab($donnee, $indexToExclude, $boucle);

        // if($boucle + 1 < $oldBoucle)
        //   echo $boucle, $oldBoucle, '</tr></tbody></table>';
    }else
    {
      if(0 == $boucle){
        echo '<tr class="foldable" ><td>\'', $index, '\'</td><td colspan="2">\'', $donnee , '\'</td></tr>';
      }else{
        if(is_object($donnee))
          $donnee = 'This is an Object non renderable !!';
        echo '<tr class="deepContent"><td colspan="' , $boucle , '"></td><td>\'', $index, '\'</td><td>\'', $donnee , '\'</td></tr>';
      }
    }
    $i += 1;
  }
  return $oldBoucle;
}

function debug($noErrors = true){ if($noErrors) error_reporting(0); return (isset($_SESSION['debuglp_']) && $_SESSION['debuglp_'] == 'Dev');}
?>
