<?php
declare(strict_types=1);
namespace otra\console\deployment\genWatcher;

use otra\OtraException;

use const otra\cache\php\CONSOLE_PATH;
use const otra\console\{CLI_ERROR, CLI_INFO_HIGHLIGHT, END_COLOR, ERASE_SEQUENCE, SUCCESS};

use function otra\console\convertLongArrayToShort;
use function otra\console\deployment\getPathInformations;
use function otra\tools\files\returnLegiblePath2;

const
  KEY_ALL_SASS = 0,
  KEY_MAIN_TO_LEAVES = 1,
  KEY_FULL_TREE = 2,
  // we do not store informations about SASS internals nor commented imports
  REGEX_SASS_IMPORT = '`^[^/@]{0,}@(?:import|use)\s{0,}\'\\K([^\':]{0,})\'\s{0,}(?:as\s[^;]{0,}\s{0,})?;$`m',
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
 * @param string $importedSass   SASS/SCSS file to get absolute path from
 * @param string $dotExtension   Extension with the dot as in '.scss'
 * @param string $resourcesPath  Folder of the stylesheet that imports $importedSass
 *
 * @return array The absolute path of $importedSass
 */
function getCssPathFromImport(string $importedSass, string $dotExtension, string $resourcesPath) : array
{
  $lastSlashPosition = strrpos($importedSass, '/');

  if ($lastSlashPosition !== false)
  {
    $importPath = mb_substr($importedSass, 0, $lastSlashPosition + 1);
    $importedFileBaseName = mb_substr($importedSass, $lastSlashPosition + 1);
  } else
  {
    $importedFileBaseName = $importedSass;
    $importPath = ($resourcesPath[strlen($resourcesPath) -1 ] === '/') ? '' : '/';
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

  return [$newResourceToAnalyze, $absoluteImportPathWithDots, $absoluteImportPathWithDotsAlt];
}

/**
 * We go to the SASS/SCSS dependencies tree (the top having the files without '_') and
 * stores the links between leaves and main stylesheets.
 *
 * @param array  $sassTree
 * @param string $importingFileAbsolutePath
 * @param string $previousImportedCssFound
 * @param string $dotExtension Extension with the dot as in '.scss'
 * @param string $sassTreeString
 *
 * @throws OtraException
 *
 * @return bool Returns false if an errors occurred
 */
function searchSassLastLeaves(
  array &$sassTree,
  string $importingFileAbsolutePath,
  string $previousImportedCssFound,
  string $dotExtension,
  string &$sassTreeString
) : bool
{
  // To prevent a cycles loop in the dependency tree
  if (str_contains($sassTreeString, $previousImportedCssFound))
    return true;

  // Moves the cursor in the SASS dependency tree to updates the right section and prepare for the tree for an update
  $sassTree[KEY_ALL_SASS][$previousImportedCssFound] = true;
  $sassTreeString .= '[' . array_search($previousImportedCssFound, array_keys($sassTree[KEY_ALL_SASS])) . ']';
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
    updateSassTree($sassTree, $importingFileAbsolutePath, $previousImportedCssFound);

    // adds an entry to the full dependency tree
    $lastOpeningBracketPos = strrpos($sassTreeString, '[');
    $sassTreeString = substr($sassTreeString, 0, $lastOpeningBracketPos);
    eval('if (!isset(' . $sassTreeString . ')) ' . $sassTreeString . '=[];');

    return true;
  }

  $importedCssFound = $importedCssFound[1];

  foreach($importedCssFound as $matchKey => $importedStylesheetFound)
  {
    [$newResourceToAnalyze, $absoluteImportPathWithDots, $absoluteImportPathWithDotsAlt] =
      getCssPathFromImport($importedStylesheetFound, $dotExtension, $resourcesPath);

    // If does not exist in the two previously cases, it is surely a wrongly written import
    if ($newResourceToAnalyze === false)
    {
      echo CLI_ERROR, 'In the file ', returnLegiblePath2($previousImportedCssFound),
        CLI_ERROR, ',', PHP_EOL, 'this import path is wrong ', CLI_INFO_HIGHLIGHT,
        $importedCssFound[0][$matchKey], CLI_ERROR, PHP_EOL, 'as it leads to ',
        returnLegiblePath2($absoluteImportPathWithDots), CLI_ERROR, ' or to ',
        returnLegiblePath2($absoluteImportPathWithDotsAlt), CLI_ERROR, '.', END_COLOR, PHP_EOL;

      return false;
    }

    updateSassTree($sassTree, $importingFileAbsolutePath, $previousImportedCssFound);
    if (!searchSassLastLeaves($sassTree, $importingFileAbsolutePath, $newResourceToAnalyze, $dotExtension, $sassTreeString))
      return false;

    // Moves back the cursor up in the tree thanks to the previous backup of the cursor state
    $sassTreeString = $oldSassTreeString;
  }

  return true;
}

/**
 * @param array $sassTree Sass tree that is actually in memory that needs to be saved into the cache
 *
 * @throws OtraException
 */
function saveSassTree(array $sassTree) : void
{
  require_once CONSOLE_PATH . '/tools.php';
  if (
    file_put_contents(
      SASS_TREE_CACHE_PATH,
      '<?php declare(strict_types=1);namespace otra\cache\css;return ' .
      convertLongArrayToShort($sassTree) . ';' . PHP_EOL
    )
  )
    echo ERASE_SEQUENCE, ERASE_SEQUENCE, 'SASS/SCSS dependency tree built and saved', SUCCESS;
  else
  {
    echo CLI_ERROR, 'Something went wrong when saving the tree.', END_COLOR, PHP_EOL;
    throw new OtraException(code: 1, exit: true);
  }
}

/**
 * This function is used in the case a developer updates a SASS/SCSS stylesheet.
 * This will update the tree in cache accordingly due to those detected changes.
 *
 * @param array  $sassTree      Cached SASS/SCSS dependency tree
 * @param array  $sassTreeKeys  Keys from KEY_ALL_SASS from $sassTree
 * @param string $extension     Extension WITHOUT dot
 * @param int    $sassFileKey   Key from KEY_ALL_SASS of resource to update in the tree
 * @param int    $importingFile Importing file from the cache
 * @param array  $importedFiles Imported files from the cache
 * @param int    $countImports  Actual imports count
 * @param array  $imports       Actual imported files
 *
 * @throws OtraException
 * @return bool
 */
function updateSassTreeAfterEvent(
  array &$sassTree,
  array $sassTreeKeys,
  string $extension,
  int $sassFileKey,
  int $importingFile,
  array &$importedFiles,
  int $countImports,
  array $imports
): bool
{
  if ($importingFile === $sassFileKey)
  {
    if (count($importedFiles) === $countImports)
      return true;
    else
    {
      /** @var int[] $removedImports */
      $removedImports = array_diff(array_keys($importedFiles), $imports);
      /** @var int[] $addedImports */
      $addedImports = array_diff($imports, array_keys($importedFiles));

      // removes "removed stylesheets imports" from the tree
      foreach ($removedImports as $removedImport)
      {
        unset($importedFiles[$removedImport]);

        if (isset($sassTree[KEY_MAIN_TO_LEAVES][$removedImport]))
          unset($sassTree[KEY_MAIN_TO_LEAVES][$removedImport][array_search(
            $sassFileKey,
            $sassTree[KEY_MAIN_TO_LEAVES][$removedImport]
          )]);
      }

      // adds "added stylesheets imports" to the tree
      foreach ($addedImports as $addedImport)
      {
        $sassTreeString = SASS_TREE_STRING_INIT . '[' . $importingFile . ']';

        // if the imported file does not exist, we do not add the file to the tree
        if (!$addedImport)
          break;

        $sassTree[KEY_ALL_SASS][$sassTreeKeys[$addedImport]] = true;
        $sassTree[KEY_MAIN_TO_LEAVES][$addedImport][] = $importingFile;
        // We add the main sass file to the tree
        searchSassLastLeaves($sassTree,
          $sassTreeKeys[$addedImport],
          $sassTreeKeys[$addedImport],
          '.' . $extension,
          $sassTreeString);
      }
    }
  }

  foreach($importedFiles as $newImportingFile => $newImportedFiles)
  {
    if (updateSassTreeAfterEvent(
      $sassTree,
      $sassTreeKeys,
      $extension,
      $sassFileKey,
      $newImportingFile,
      $newImportedFiles,
      $countImports,
      $imports
    ))
      break;
  }

  return false;
}

/**
 * Removes deleted branches from the full SASS/SCSS tree. (Third index of the cached tree)
 * Decrements all keys that are superior to the key that have been removed.
 *
 * @param int   $sassKeyTreeToDelete The stylesheet key to remove from the tree. Key of an importing file.
 * @param array $importedFiles       Tree branch to process
 *
 * @return array
 */
function createPrunedFullTree(int $sassKeyTreeToDelete, array $importedFiles) : array
{
  $newImportedFiles = [];

  foreach($importedFiles as $otherImportingFile => $otherImportedFiles)
  {
    if ($otherImportingFile === $sassKeyTreeToDelete)
      continue;

    $newImportingFile = ($otherImportingFile > $sassKeyTreeToDelete) ? $otherImportingFile - 1 : $otherImportingFile;
    $newImportedFiles[$newImportingFile] = createPrunedFullTree(
      $sassKeyTreeToDelete,
      $otherImportedFiles
    );
  }

  return $newImportedFiles;
}
