<?
// TODO Not fully tested !

define('SQL_CLAUSES', [
  '(SELECT ',
  'SELECT ',
  'FROM ',
  'LEFT OUTER JOIN',
  'RIGHT OUTER JOIN',
  'LEFT JOIN',
  'INNER JOIN',
  ' ON ',
  'IN ',
  'AND ',
  'OR ',
  'WHERE ',
  'UNION ',
  'GROUP BY ',
  'ORDER BY ',
  'LIMIT ',
  'OFFSET '
]);

define('LEFT_STYLE_CLAUSE_CODE', '<span style="color:#E44">');
define('RIGHT_STYLE_CLAUSE_CODE', '</span>');

/**
 * Returns the pretty printed versions of sql clauses
 *
 * @param string $leftStyleClauseCode
 * @param string $rightStyleClauseCode
 *
 * @return array
 */
function sqlReplacements(
  string $leftStyleClauseCode = LEFT_STYLE_CLAUSE_CODE,
  string $rightStyleClauseCode = RIGHT_STYLE_CLAUSE_CODE
) : array
{
   return [
    '(' . "\n" . $leftStyleClauseCode . 'SELECT ' . $rightStyleClauseCode,
    $leftStyleClauseCode . 'SELECT ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'FROM ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'LEFT OUTER JOIN' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'INNER OUTER JOIN' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'LEFT JOIN' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'INNER JOIN' . $rightStyleClauseCode,
    $leftStyleClauseCode . ' ON ' . $rightStyleClauseCode,
    $leftStyleClauseCode . 'IN ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . '  AND ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . '  OR ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'WHERE ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'UNION ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'GROUP BY ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'ORDER BY ' . $rightStyleClauseCode,
    "\n" . $leftStyleClauseCode . 'LIMIT ' . $rightStyleClauseCode,
    $leftStyleClauseCode . 'OFFSET ' . $rightStyleClauseCode
  ];
}

/**
 * TODO Not tested yet !
 *
 * @param string $rawSql            Raw sql to pretty print
 * @param bool   $raw               Do we want the raw sql or the styled sql ?
 *
 * @return string
 */
function rawSqlPrettyPrint(string $rawSql, bool $raw = false) : string
{
  $leftStyleClauseCode = $rightStyleClauseCode = $output = '';

  // If we want to style the SQL with HTML markup + CSS
  if ($raw === false) {
    $output = '<pre class="sql-request">';
    $leftStyleClauseCode = XMODE === 'dev' ? '<span class="sql-clause">' : LEFT_STYLE_CLAUSE_CODE;
    $rightStyleClauseCode = RIGHT_STYLE_CLAUSE_CODE;
  }

  $output .= $rawSql;

  if ($raw === false) {
    $output = preg_replace(
      '/(:?\.)[^ (]{1,}/',
      '<span class="sql-field">$0</span>',
      preg_replace(
        '/:[^ )]{1,}/',
        '<span style="color:#4B4">$0</span>',
        $output
      )
    );
  }

  $output = str_replace(
    SQL_CLAUSES,
    sqlReplacements($leftStyleClauseCode, $rightStyleClauseCode),
    $output
  );

  $output .= "\n" . "\n";

  if ($raw === false) {
    $output .= '</pre>';
  }

  return $output;
}

/**
 * @param PDOStatement $statement         Statement to use if we want to replace parameters by their values
 * @param bool         $raw               Do we want the raw sql or the styled sql ?
 * @param bool         $replaceParameters Do we have to replace the parameters by their values ?
 *
 * @return string
 */
function statementPrettyPrint(PDOStatement $statement, bool $raw = false, bool $replaceParameters = true) : string
{
  $leftStyleClauseCode = $rightStyleClauseCode = $output = '';

  // If we want to style the SQL with HTML markup + CSS
  if ($raw === false) {
    $output = '<pre>';
    $leftStyleClauseCode = '<span style="color:#E44">';
    $rightStyleClauseCode = '</span>';
  }

  $queryInformations = $statement->debugDumpParams();
  $rawSql = substr($queryInformations, 0, strpos($queryInformations, 'Params'));
  $parameters = []; // TODO retrieve the SQL statement parameters in an array !

  $output .= $rawSql;

  // Replaces the parameters name by their values in the SQL query
  if ($replaceParameters === true) {
    foreach ($statement->getParameters() as $key => &$parameter) {
      $output = str_replace($key, '"' . $parameter . '"', $rawSql);
    }
  }

  if ($raw === false) {
    $output = preg_replace(
      '/(:?\.)[^ (]{1,}/',
      '<span style="color:#44F">$0</span>',
      preg_replace(
        '/:[^ )]{1,}/',
        '<span style="color:#4B4">$0</span>',
        $output
      )
    );
  }

  $output = str_replace(
    SQL_CLAUSES,
    sqlReplacements($leftStyleClauseCode, $rightStyleClauseCode),
    $output
  );

  $output .= "\n" . "\n";

  // Display parameters at end.
  if ($replaceParameters === false) {
    foreach ($parameters as $key => &$parameter) {
      $output .= str_pad($key, 30, '.') . ' => ' . $parameter . "\n";
    }
  }

  if ($raw === false) {
    $output .= '</pre>';
  }

  return $output;
}
?>
