<?php

namespace Drupal\custom_student\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Custom Student routes.
 */
class CustomStudentController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }
  

}
