<?php
declare(strict_types=1);
namespace otra\console\deployment\genWatcher;

use otra\OtraException;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\deployment\getPathInformations;
use function otra\tools\files\returnLegiblePath2;

/**
 * @param array  $sassTree
 * @param string $realPath
 * @param string $previousImportedStylesheetFound
 * @param string $dotExtension
 *
 * @throws OtraException
 */
function searchSassLastLeaves(
  array &$sassTree,
  string $realPath,
  string $previousImportedStylesheetFound,
  string $dotExtension
) : void
{
  [, $resourcesMainFolder, $resourcesFolderEndPath, ] =
    getPathInformations($previousImportedStylesheetFound);

  $resourcesPath = $resourcesMainFolder . $resourcesFolderEndPath;
  $fileContent = file_get_contents($previousImportedStylesheetFound);
  preg_match_all(
    '`@(?:import|use)\s\'([^\':]{0,})\'\s{0,};`',
    $fileContent,
    $importedStylesheetsFound
  );

  // If this file does not contain the modified SASS/SCSS file, we look into other watched main resources
  // files.
  if (!isset($importedStylesheetsFound[1][0]))
    return;

  $importedStylesheetsFound = $importedStylesheetsFound[1];

  foreach($importedStylesheetsFound as $matchKey => $importedStylesheetFound)
  {
    $lastSlashPosition = strrpos($importedStylesheetFound, '/');

    if ($lastSlashPosition !== false)
    {
      $importPath = mb_substr($importedStylesheetFound, 0, $lastSlashPosition + 1);
      $importedFileBaseName = mb_substr($importedStylesheetFound, $lastSlashPosition + 1);
    } else
    {
      $importedFileBaseName = $importedStylesheetFound;
      $importPath = '/';
    }

    // $importPath like ../../configuration
    // $importedStylesheetFound like ../../configuration/generic
    // $importedFileBaseName like generic.scss
    // $resourcesPath like /var/www/html/perso/components/bundles/Ecocomposer/frontend/resources/scss/pages/table/

    // The imported file can have the extension but it is not mandatory
    if (!str_contains($importedFileBaseName, '.'))
      $importedFileBaseName .= $dotExtension;

    $absoluteImportPathWithDots = $resourcesPath . $importPath . $importedFileBaseName;
    $absoluteImportPathWithDotsAlt = $resourcesPath . $importPath. '_' . $importedFileBaseName;

    // Does the file exist without '_' at the beginning
    $newResourceToAnalyze = realpath($absoluteImportPathWithDots);

    if ($newResourceToAnalyze === false)
      // Does the file exist with '_' at the beginning
      $newResourceToAnalyze = realpath($absoluteImportPathWithDotsAlt);

    // If does not exist in the two previously cases, it is surely a wrongly written import
    if ($newResourceToAnalyze === false)
    {
      echo CLI_ERROR, 'In the file ', returnLegiblePath2($previousImportedStylesheetFound),
      CLI_ERROR, ',', PHP_EOL, 'this import path is wrong ', CLI_INFO_HIGHLIGHT, $importedStylesheetsFound[0][$matchKey], CLI_ERROR,
      PHP_EOL,'as it leads to ', returnLegiblePath2($absoluteImportPathWithDots), CLI_ERROR,
      ' or to ', returnLegiblePath2($absoluteImportPathWithDotsAlt), CLI_ERROR, '.',
      END_COLOR, PHP_EOL;
    }

    $sassTree[$previousImportedStylesheetFound][] = $realPath;
    searchSassLastLeaves($sassTree, $realPath, $newResourceToAnalyze, $dotExtension);
  }
}
