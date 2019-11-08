<?php

namespace Drupal\file_link_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Returns responses for File Link Test routes.
 */
class RedirectController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function redirectToFile($status, $ext) {
    return new RedirectResponse(self::getFullUrl('/foo.' . $ext), $status);
  }

  /**
   * Provides a full URL given a path relative to file_link_test module.
   *
   * @param string $path
   *   A path relative to file_link_test module.
   *
   * @return string
   *   An absolute URL.
   */
  protected static function getFullUrl($path) {
    return Url::fromUri('base:/' . drupal_get_path('module', 'file_link_test') . $path, ['absolute' => TRUE])->toString();
  }

}
