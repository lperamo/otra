<?php
declare(strict_types=1);

namespace src\console\helpAndTools\serve;

use otra\console\TasksManager;
use otra\OtraException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use const otra\bin\TASK_CLASS_MAP_PATH;
use const otra\console\{CLI_ERROR, CLI_INFO, CLI_INFO_HIGHLIGHT, CLI_TABLE, CLI_TABLE_HEADER, CLI_WARNING, END_COLOR};

/**
 * @runTestsInSeparateProcesses
 */
class ServeTaskTest extends TestCase
{
  private const string
    TASK_SERVE = 'serve',
    WRONG_PORT = '-50';

  public function testServe() : void
  {
    $initialObLevel = ob_get_level();
    ob_start();

    try
    {
      // Execute the task expected to throw an exception
      TasksManager::execute(
        require TASK_CLASS_MAP_PATH,
        self::TASK_SERVE,
        ['otra.php', self::TASK_SERVE, self::WRONG_PORT]
      );
      self::fail('Expected OtraException was not thrown.');
    }
    catch (OtraException $exception)
    {
      $output = ob_get_clean();
      $beginning = CLI_INFO . 'Launching a PHP web internal server on localhost:-50 in development mode.' . END_COLOR . 
        PHP_EOL .
        CLI_WARNING . 'JIT compiler has been automatically disabled because Xdebug extension is loaded.' . PHP_EOL .
        'Remove Xdebug to benefit from JIT performance improvements!' . END_COLOR . PHP_EOL .
        CLI_ERROR . PHP_EOL . 'PHP exception' . PHP_EOL . '=============' . END_COLOR . PHP_EOL . PHP_EOL .
        'Error type ' . CLI_INFO_HIGHLIGHT . 'UNKNOWN' . END_COLOR .
        ' in ' . CLI_INFO_HIGHLIGHT . 'CORE_PATH + tools/cli.php' . END_COLOR .
        ' at line ' . CLI_INFO_HIGHLIGHT . '53' . END_COLOR .
        PHP_EOL . 'Problem when loading the command:' . PHP_EOL .
        CLI_WARNING . 
        'OTRA_LIVE_APP_ENV=dev OTRA_LIVE_HTTPS=false php -d opcache.jit=disable -d variables_order=EGPCS -S localhost:-50 -t /media/data/web/perso/otra/web web/indexDev.php 2>&1' .
        END_COLOR . PHP_EOL .
        'Shell error code 1. Invalid address: localhost:-50' . PHP_EOL . CLI_TABLE . '┌';
      self::assertStringStartsWith($beginning, $output);
      $output = substr($output, strpos($output,CLI_TABLE . '┌'));

      // Capture the columns' width in the first line
      $regexFirstLine = '@.┌(─+)┬(─+)┬(─+)┬(─+)┬(─+)┐@u';

      // Apply the regex on the first line of the array
      if (!preg_match($regexFirstLine, $output, $matches))
        throw new RuntimeException('La première ligne du tableau ne correspond pas au format attendu.');

      // Extract the columns' width
      $colWidths = array_map(
        fn($col) => mb_strlen(preg_replace('/\033\[[0-9;]*m/', '', $col)),
        array_slice($matches, 1, 5)
      );

      $quotedCliTableHeader = preg_quote(CLI_TABLE_HEADER);
      $quotedCliTable = preg_quote(CLI_TABLE);
      $quotesInfoHighlight = preg_quote(CLI_INFO_HIGHLIGHT);
      $quotesEndColor = preg_quote(END_COLOR);

      // Dividing line (ex: ├───┼───┤ ou └───┴───┘)
      $regexSeparator = sprintf(
        '@' . $quotedCliTable . '[├└](─{%d})[┼┴](─{%d})[┼┴](─{%d})[┼┴](─{%d})[┼┴](─{%d})[┤┘]@u',
        $colWidths[0], $colWidths[1], $colWidths[2], $colWidths[3], $colWidths[4]
      );

      // For table headers
      $regexHeaderRow = '@' . $quotedCliTable . '│' .
        $quotedCliTableHeader . ' Type {4}' . $quotedCliTable . '│' .
        $quotedCliTableHeader . ' Function +' . $quotedCliTable . '│' .
        $quotedCliTableHeader . ' Line ' . $quotedCliTable . '│' .
        $quotedCliTableHeader . ' File +' . $quotedCliTable . '│' .
        $quotedCliTableHeader . ' Arguments {11}' . $quotedCliTable . '│@u';

      // Data line (ex: │ ... │)
      $regexRow = sprintf(
        '@' . $quotedCliTable . '│.{%d}│.{%d}│.{%d}│ (' .
        $quotesInfoHighlight . 'BASE_PATH' . $quotesEndColor . '.{%d}' . $quotedCliTable .  '|' .
        $quotesInfoHighlight . 'CORE_PATH' . $quotesEndColor . '.{%d}' . $quotedCliTable .  '|' .
        $quotesInfoHighlight . 'CONSOLE_PATH' . $quotesEndColor . '.{%d}' . $quotedCliTable .  '|.{%d}' . 
        $quotedCliTable . ')│.{%d}│@u',
        $colWidths[0], $colWidths[1], $colWidths[2], $colWidths[3] - 10, $colWidths[3] - 10, $colWidths[3] - 13, $colWidths[3] - 1, $colWidths[4]
      );

      $tableRows = explode(PHP_EOL, trim($output));
      $firstLineChecked = $lastLineChecked = false;
      $lastLine = count($tableRows) - 1;
      
      foreach ($tableRows as $index => $tableRow) 
      {
        // Check the first line
        if ($index === 0) 
        {
          self::assertMatchesRegularExpression($regexFirstLine, $tableRow);
          $firstLineChecked = true;
          continue;
        }

        // Check the last line (└───┴───┘)
        if ($index === $lastLine) 
        {
          self::assertMatchesRegularExpression($regexSeparator, $tableRow);
          $lastLineChecked = true;
          continue;
        }

        // Check the separators (├───┼───┤)
        if (str_contains($tableRow, '├')) 
        {
          self::assertMatchesRegularExpression($regexSeparator, $tableRow);
          continue;
        }

        // Check the headers (│ ... │)
        if (str_contains($tableRow, CLI_TABLE_HEADER . ' Type'))
        {
          self::assertMatchesRegularExpression($regexHeaderRow, $tableRow);
          continue;
        }

        // Check the data lines (│ ... │)
        if (str_contains($tableRow, CLI_TABLE . '│'))
        {
          self::assertMatchesRegularExpression($regexRow, $tableRow);
          continue;
        }

        throw new RuntimeException("Found unexpected line: $tableRow\n$regexRow");
      }

      // Check that the edges are checked
      self::assertTrue($firstLineChecked, 'The first line hasn\'t been checked.');
      self::assertTrue($lastLineChecked, 'The last line hasn\'t been checked.');
    }
  }
}
