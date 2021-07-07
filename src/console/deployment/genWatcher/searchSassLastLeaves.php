<?php
declare(strict_types=1);
namespace otra\console\deployment\genWatcher;

use otra\OtraException;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\deployment\getPathInformations;
use function otra\tools\files\returnLegiblePath2;

const REGEX_SASS_IMPORT = '`@(?:import|use)\s\'([^\':]{0,})\'\s{0,};`';

/**
 * We go to the top of the SASS/SCSS dependencies tree (the top having the files without '_') and
 * stores the links between leaves and main stylesheets.
 *
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
    REGEX_SASS_IMPORT,
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

    // if the stylesheet IS NOT the main stylesheet then we add it to the tree
    if ($realPath !== $previousImportedStylesheetFound)
    {
      // If the stylesheet is not in the tree, we add it in the first entry that references all the main paths
      // and we add to the tree the identifier that will be at the end of the references array
      if (!in_array($realPath, $sassTree[0]))
      {
        $sassTree[0][] = $realPath;
        $sassTree[$previousImportedStylesheetFound][count($sassTree[0]) - 1] = true;
      } else
        // If it WAS in the tree, then we just add the identifier found in the references array that matches this file
        $sassTree[$previousImportedStylesheetFound][array_search($realPath, $sassTree[0])] = true;
    }

    searchSassLastLeaves($sassTree, $realPath, $newResourceToAnalyze, $dotExtension);
  }
}
