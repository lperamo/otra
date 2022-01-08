<?php
/**
 * @author Lionel Péramo
 * @package otra\tools
 */
declare(strict_types=1);

namespace otra\tools;

use PDOStatement;
use const otra\cache\php\{APP_ENV, DEV};

const OTRA_LABEL_SELECT = 'SELECT ';

const SQL_CLAUSES = [
  '(SELECT ',
  OTRA_LABEL_SELECT,
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
];

const LEFT_STYLE_CLAUSE_CODE = '<span style="color:#E44">';
const RIGHT_STYLE_CLAUSE_CODE = '</span>';

/**
 * Returns the pretty printed versions of sql clauses
 *
 * @param bool   $raw
 * @param string $leftStyleClauseCode
 * @param string $rightStyleClauseCode
 *
 * @return string[]
 */
function sqlReplacements(
  bool $raw,
  string $leftStyleClauseCode = LEFT_STYLE_CLAUSE_CODE,
  string $rightStyleClauseCode = RIGHT_STYLE_CLAUSE_CODE
) : array
{
  $carriageReturn = $raw ? PHP_EOL : '<br/>';

   return [
    '(' . $carriageReturn . $leftStyleClauseCode . OTRA_LABEL_SELECT . $rightStyleClauseCode,
    $leftStyleClauseCode . OTRA_LABEL_SELECT . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'FROM ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'LEFT OUTER JOIN' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'INNER OUTER JOIN' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'LEFT JOIN' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'INNER JOIN' . $rightStyleClauseCode,
    $leftStyleClauseCode . ' ON ' . $rightStyleClauseCode,
    $leftStyleClauseCode . 'IN ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . '  AND ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . '  OR ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'WHERE ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'UNION ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'GROUP BY ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'ORDER BY ' . $rightStyleClauseCode,
    $carriageReturn . $leftStyleClauseCode . 'LIMIT ' . $rightStyleClauseCode,
    $leftStyleClauseCode . 'OFFSET ' . $rightStyleClauseCode
  ];
}

/**
 * @param string $rawSql Raw sql to pretty print
 * @param bool   $raw    Do we want the raw sql or the styled sql ?
 *
 * @return string
 */
function rawSqlPrettyPrint(string $rawSql, bool $raw = false) : string
{
  $leftStyleClauseCode = $rightStyleClauseCode = $output = '';

  // If we want to style the SQL with HTML markup + CSS
  if (!$raw)
  {
    $output = '<div class="profiler--sql-logs--request">';
    $leftStyleClauseCode = $_SERVER[APP_ENV] === DEV
      ? '<span class="profiler--sql-logs--clause">'
      : LEFT_STYLE_CLAUSE_CODE;
    $rightStyleClauseCode = RIGHT_STYLE_CLAUSE_CODE;
  }

  $output .= $rawSql;

  if (!$raw)
  {
    $output = preg_replace(
      '/(:?\.)[^ (]+/',
      '<span class="profiler--sql-logs--field">$0</span>',
      preg_replace(
        '/:[^ )]+/',
        '<span style="color: #4b4;">$0</span>',
        $output
      )
    );
  }

  $output = str_replace(
    SQL_CLAUSES,
    sqlReplacements($raw, $leftStyleClauseCode, $rightStyleClauseCode),
    $output
  );

  return $output . (!$raw ? '</div>' : PHP_EOL . PHP_EOL);
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
  if (!$raw)
  {
    $output = '<div>';
    $leftStyleClauseCode = '<span style="color: #e44;">';
    $rightStyleClauseCode = '</span>';
  }

  ob_start();
  $statement->debugDumpParams();
  $queryInformations = ob_get_clean();
  $rawSql = substr($queryInformations, 0, strpos($queryInformations, 'Params'));
  $parameters = [];

  $output .= $rawSql;

  // Replaces the parameters name by their values in the SQL query
  if ($replaceParameters)
  {
    foreach ($statement->getParameters() as $parameterName => &$parameterValue)
    {
      $output = str_replace($parameterName, '"' . $parameterValue . '"', $rawSql);
    }
  }

  if (!$raw)
  {
    $output = preg_replace(
      '/(:?\.)[^ (]+/',
      '<span style="color: #44f;">$0</span>',
      preg_replace(
        '/:[^ )]+/',
        '<span style="color: #4b4;">$0</span>',
        $output
      )
    );
  }

  $output = str_replace(
    SQL_CLAUSES,
    sqlReplacements($raw, $leftStyleClauseCode, $rightStyleClauseCode),
    $output
  );

  $output .= PHP_EOL . PHP_EOL;

  // Display parameters at end.
  if (!$replaceParameters)
  {
    foreach ($parameters as $parameterName => $parameterValue)
    {
      $output .= str_pad($parameterName, 30, '.') . ' => ' . $parameterValue . PHP_EOL;
    }
  }

  if (!$raw)
    $output .= '</div>';

  return $output;
}
