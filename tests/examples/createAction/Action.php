<?php
declare(strict_types=1);

namespace bundles\Test\test\controllers\test;

use otra\Controller;

/**
 * @package bundles\Test\test\controllers\test
 */
class TestAction extends Controller
{
  /**
   * TestAction constructor.
   *
   * @param array $baseParams
   * @param array $getParams
   */
  public function __construct(array $baseParams = [], array $getParams = [])
  {
    parent::__construct($baseParams, $getParams);
  }
}
