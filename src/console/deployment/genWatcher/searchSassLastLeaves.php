<?php
declare(strict_types=1);
namespace otra\console\deployment\genWatcher;

use otra\OtraException;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR};
use function otra\console\deployment\getPathInformations;
use function otra\tools\files\returnLegiblePath2;

const
  KEY_ALL_SASS = 0,
  KEY_MAIN_TO_LEAVES = 1,
  KEY_FULL_TREE = 2,
  // we do not store informations about SASS internals
  REGEX_SASS_IMPORT = '`@(?:import|use)\s\'([^\':]{0,})\'\s{0,}(?:as\s[^;]{0,}\s{0,})?;`',
  SASS_TREE_STRING_INIT = '$sassTree[' . KEY_FULL_TREE . ']';

/**
 * @param array  $sassTree
 * @param string $realPath
 * @param string $previousImportedCssFound
 */
function updateSassTree(array &$sassTree, string $realPath, string $previousImportedCssFound)
{
  // if the stylesheet IS NOT the main stylesheet then we add it to the tree
  if ($realPath !== $previousImportedCssFound)
  {
    $sassTreeKeys = array_keys($sassTree[KEY_ALL_SASS]);
    // If we did not already stored the identifier found in the references array that matches this file, we add it to
    // the sass tree
    if (!isset($sassTree[KEY_MAIN_TO_LEAVES][array_search($previousImportedCssFound, $sassTreeKeys)])
      || !in_array(
        array_search($realPath, $sassTreeKeys),
        $sassTree[KEY_MAIN_TO_LEAVES][array_search($previousImportedCssFound, $sassTreeKeys)]
      )
    )
      $sassTree[KEY_MAIN_TO_LEAVES]
        [array_search($previousImportedCssFound, $sassTreeKeys)]
        [] = array_search($realPath, $sassTreeKeys);
  }
}

/**
 * We go to the SASS/SCSS dependencies tree (the top having the files without '_') and
 * stores the links between leaves and main stylesheets.
 *
 * @param array  $sassTree
 * @param string $realPath
 * @param string $previousImportedCssFound
 * @param string $dotExtension
 * @param int    $level
 * @param string $sassTreeString
 *
 * @throws OtraException
 */
function searchSassLastLeaves(
  array &$sassTree,
  string $realPath,
  string $previousImportedCssFound,
  string $dotExtension,
  int &$level,
  string &$sassTreeString
) : void
{
  // To prevent a cycles loop in the dependency tree
  if (str_contains($sassTreeString, $previousImportedCssFound))
    return;

  // Moves the cursor in the SASS dependency tree to updates the right section and prepare for the tree for an update
  $sassTree[KEY_ALL_SASS][$previousImportedCssFound] = true;
  $sassTreeString .= '[\'' . array_search($previousImportedCssFound, array_keys($sassTree[KEY_ALL_SASS])) . '\']';
  eval($sassTreeString . '=[];');

  // Backups the state of the cursor to be able to restore it when we finish the sub branches.
  $oldSassTreeString = $sassTreeString;

  [, $resourcesMainFolder, $resourcesFolderEndPath, ] =
    getPathInformations($previousImportedCssFound);

  $resourcesPath = $resourcesMainFolder . $resourcesFolderEndPath;

  $fileContent = file_get_contents($previousImportedCssFound);
  preg_match_all(
    REGEX_SASS_IMPORT,
    $fileContent,
    $importedCssFound
  );

  // If this file does not contain any import rule, we look into other watched main resources
  // files.
  if (!isset($importedCssFound[1][0]))
  {
    updateSassTree($sassTree, $realPath, $previousImportedCssFound);

    // adds an entry to the full dependency tree
    $lastOpeningBracketPos = strrpos($sassTreeString, '[');
    $sassTreeString = substr($sassTreeString, 0, $lastOpeningBracketPos);
    eval('if (!isset(' . $sassTreeString . ')) ' . $sassTreeString . '=[];');

    return;
  }

  $importedCssFound = $importedCssFound[1];

  foreach($importedCssFound as $matchKey => $importedStylesheetFound)
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
      echo CLI_ERROR, 'In the file ', returnLegiblePath2($previousImportedCssFound),
        CLI_ERROR, ',', PHP_EOL, 'this import path is wrong ', CLI_INFO_HIGHLIGHT,
        $importedCssFound[0][$matchKey], CLI_ERROR, PHP_EOL, 'as it leads to ',
        returnLegiblePath2($absoluteImportPathWithDots), CLI_ERROR, ' or to ',
        returnLegiblePath2($absoluteImportPathWithDotsAlt), CLI_ERROR, '.', END_COLOR, PHP_EOL;
    }

    updateSassTree($sassTree, $realPath, $previousImportedCssFound);

    ++$level;
    searchSassLastLeaves($sassTree, $realPath, $newResourceToAnalyze, $dotExtension, $level, $sassTreeString);
    --$level;

    // Moves back the cursor up in the tree thanks to the previous backup of the cursor state
    $sassTreeString = $oldSassTreeString;
  }
}
