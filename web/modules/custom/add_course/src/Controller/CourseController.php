<?php

namespace Drupal\add_course\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CourseController extends ControllerBase {


  public function enroll(NodeInterface $node) {
    // Dependency injection in controller ...

    $user_id = \Drupal::currentUser()->id();
    $user_entity = User::load($user_id);
    if ($user_entity->hasField('field_enroll_courses')) {
      $user_entity->get('field_enroll_courses')
        ->appendItem($node);
      $user_entity->save();
      Cache::invalidateTags($node->getCacheTagsToInvalidate());
    }
    return new RedirectResponse('/course-view');
  }


  public function dissroll(NodeInterface $node) {
    $user_id = \Drupal::currentUser()->id();
    // Load the user entity.
    $user_entity = \Drupal\user\Entity\User::load($user_id);
    if ($user_entity->hasField('field_enroll_courses')) {
      $user_enroll_courses_field = $user_entity->get('field_enroll_courses');
      $index = null;
      foreach ($user_enroll_courses_field as $delta => $field) {
        $user_enroll_course_node = $field->entity;
        if($user_enroll_course_node->id() === $node->id()) {
          $index = $delta;
          break;
        }
      }
      if($index !== NULL) {
      $user_enroll_courses_field->removeItem($index);
      $user_entity->save();
      Cache::invalidateTags($node->getCacheTagsToInvalidate());
      }
    }
    return new RedirectResponse('/course-view');
  }

}
