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
   * @param array $otraParams [pattern, bundle, module, controller, action, route, js, css, internalRedirect]
   * @param array $params     [...getParams, ...postParams, etc.]
   */
  public function __construct(array $otraParams = [], array $params = [])
  {
    parent::__construct($otraParams, $params);
  }
}
