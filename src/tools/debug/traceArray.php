<?php
declare(strict_types=1);

if (!function_exists('getArgumentType'))
{
  define('OTRA_TD_OPENING_TAG', '<td>');
  define('OTRA_TD_ENDING_TAG', '</td>');
  define('OTRA_TR_ENDING_TAG', '</tr>');

  /**
   * @param $index
   * @param $value
   *
   * @return int|string
   */
  function getArgumentType($index, &$value)
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
   * @param int|null         $indexToExclude Index to exclude from the render
   * @param int          $loop           Number of recursions
   *
   * @return int
   */
  function recurArrayConvertTab(array|object $data, ?int $indexToExclude = null, int $loop = -1)
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
            OTRA_TD_OPENING_TAG , $index, OTRA_TD_ENDING_TAG,
            OTRA_TR_ENDING_TAG;
          else
            echo OTRA_TD_OPENING_TAG , $index, OTRA_TD_ENDING_TAG,
            '<td colspan="0" class="dummy"></td>',
            OTRA_TR_ENDING_TAG;
        } elseif ($loop > 1)
          echo '<tr class="foldable">',
          '<td colspan="', $loop, '"></td>',
          '<td colspan="0">', $index,  OTRA_TD_ENDING_TAG,
          '<td colspan="0" class="dummy"></td>',
          OTRA_TR_ENDING_TAG;
        else
          echo '<tr class="foldable no-dummy">',
          '<td colspan="">Index:' ,
          is_numeric($index) ? getArgumentType($index, $datum) : $index,
          ', Loop:', $loop,
          OTRA_TD_ENDING_TAG;

        $oldLoop = recurArrayConvertTab($datum, $indexToExclude, $loop);
      } else
      {
        if (true === is_array($datum))
          $datum = 'Empty';

  //    if (0 === $loop)
        echo '<tr class="no-dummy" >',
        OTRA_TD_OPENING_TAG, getArgumentType($index, $datum), OTRA_TD_ENDING_TAG,
        '<td colspan="2">', $datum , OTRA_TD_ENDING_TAG,
        OTRA_TR_ENDING_TAG;

      }

      ++$i;
    }

    return $oldLoop;
  }

  /**
   * Converts a php trace array into stylish html table
   *
   * @param array  $dataToShow     Array to convert
   * @param string $title          Table name to show in the header
   * @param null   $indexToExclude Index to exclude from the render
   *
   * @return false|string
   */
  function createShowableFromArray(array $dataToShow, string $title, $indexToExclude = null)
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

  /** Converts a php trace array into stylish console table. TODO finish it !
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
}
