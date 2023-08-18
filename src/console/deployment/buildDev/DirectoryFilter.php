<?php
declare(strict_types=1);

namespace otra\console\deployment\buildDev;

use RecursiveFilterIterator;
use FilesystemIterator;
use const otra\cache\php\CORE_PATH;
use const otra\console\deployment\{PATHS_TO_AVOID, PATHS_TO_HAVE_RESOURCES};
use function otra\console\deployment\isNotInThePath;

/**
 *  Filters directories and specific paths from a recursive directory iterator.
 *
 *  This filter is used to skip over directories and files matching the defined
 *  PATHS_TO_AVOID during iteration. It's primarily aimed at optimizing the iteration
 *  process by excluding unnecessary directories and files.
 */
class DirectoryFilter extends RecursiveFilterIterator
{
  /**
   * Create a RecursiveFilterIterator from a RecursiveIterator.
   *
   * @param FilesystemIterator $iterator              The iterator that this filter will apply to.
   * @param array<string, int>         $resourcesToWatchAssoc Associative array with the extensions as the keys.
   *
   * @link https://php.net/manual/en/recursivefilteriterator.construct.php
   */
  public function __construct(FilesystemIterator $iterator, protected array $resourcesToWatchAssoc = [])
  {
    parent::__construct($iterator);
  }

  public function accept(): bool
  {
    $current = $this->current();
    $realPath = $current->getPathname();

    foreach (PATHS_TO_AVOID as $pathToAvoid)
    {
      if (str_contains($realPath, $pathToAvoid))
        return false;
    }

    if ($current->isDir())
      return true;

    // If it is not a watched extension
    // or if it is a starter (they are only meant to be copied, not used)
    // or the resources' path does not belong to a valid defined path => we filter it
    if (
      !isset($this->resourcesToWatchAssoc[$current->getExtension()])
      || str_contains($realPath, 'starters')
      || isNotInThePath(
        PATHS_TO_HAVE_RESOURCES,
        $realPath,
        (BUILD_DEV_SCOPE === 0 && !str_contains($realPath, CORE_PATH)
          || BUILD_DEV_SCOPE === 1 && str_contains($realPath, CORE_PATH)
          || BUILD_DEV_SCOPE === 2)
      ))
      return false;

    return true;
  }

  /**
   * Checks whether the current element of the iterator has children.
   *
   * @return bool TRUE if the current element has children, otherwise FALSE.
   */
  public function hasChildren(): bool
  {
    // If the current element is not accepted, we must not browse its children
    return $this->accept() && $this->getInnerIterator()->hasChildren();
  }

  /**
   * Returns the inner iterator's children contained in a DirectoryFilter.
   *
   * @return DirectoryFilter
   */
  public function getChildren(): DirectoryFilter
  {
    return new self($this->getInnerIterator()->getChildren(), $this->resourcesToWatchAssoc);
  }
}
