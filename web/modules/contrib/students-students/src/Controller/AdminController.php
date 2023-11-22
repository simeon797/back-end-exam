<?php

namespace Drupal\students\Controller;

/**
 * @file
 * Contains \Drupal\students\Controller\AdminController.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\students\StudentsStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminController.
 *
 * @package Drupal\students\Controller
 */
class AdminController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Our database repository service.
   *
   * @var \Drupal\students\StudentsStorage
   */
  protected $storage;

  /**
   * Renderer service will be used via Dependency Injection.
   *
   * @var Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('students.storage'),
      $container->get('renderer')
    );
  }

  /**
   * AdminController constructor.
   *
   * @param \Drupal\students\StudentsStorage $storage
   *   Request stack service for the container.
   * @param Drupal\Core\Render\Renderer $renderer
   *   Renderer service for the container.
   */
  public function __construct(StudentsStorage $storage, Renderer $renderer) {
    $this->storage = $storage;
    $this->renderer = $renderer;
  }

  /**
   * Get data as content table.
   *
   * @return array
   *   Content table.
   */
  public function content() {
    $url = Url::fromRoute('students_add');
    $add_link = '<p>' . Link::fromTextAndUrl($this->t('Add Student'), $url)->toString() . '</p>';

    $text = [
      '#type' => 'markup',
      '#markup' => $add_link,
    ];

    // Table header.
    $header = [
      'id' => $this->t('Id'),
      'name' => $this->t('Student name'),
      'gender' => $this->t('Gender'),
      'faculty_number' => $this->t('Faculty number'),
      'operations' => $this->t('Delete'),
    ];
    $rows = [];
    foreach ($this->storage->getAll() as $content) {
      // Row with attributes on the row and some of its cells.
      $editUrl = Url::fromRoute('students_edit', ['id' => $content->id]);
      $deleteUrl = Url::fromRoute('students_delete', ['id' => $content->id]);

      $rows[] = [
        'data' => [
          Link::fromTextAndUrl($content->id, $editUrl)->toString(),
          $content->name,
          $content->gender,
          $content->faculty_number,
          Link::fromTextAndUrl($this->t('Delete'), $deleteUrl)->toString(),
        ],
      ];
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'id' => 'students-table',
      ],
    ];
    return [
      $text,
      $table,
    ];
  }

}
