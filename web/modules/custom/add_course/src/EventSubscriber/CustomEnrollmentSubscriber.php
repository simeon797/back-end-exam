<?php

namespace Drupal\add_course\EventSubscriber;

use Drupal\add_course\Event\CustomEnrollmentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserLoginSubscriber.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class CustomEnrollmentSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Static class constant => method on this class.
      CustomEnrollmentEvent::UPDATE_NODE => 'onNodeUpdate',
      CustomEnrollmentEvent::REMOVE_COURSE => 'onNodeRemove',
    ];
  }

  public function onNodeRemove(CustomEnrollmentEvent $event) {

    $node = $event->getNode();
    $key = 'remove';
    $config = \Drupal::config('custom_emails_config.settings');
    $emails = array_merge([
      \Drupal::currentUser()
        ->getEmail(),
    ], $config->get('admin_emails'));
    foreach ($emails as $email) {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'add_course';
      $to = $email;
      $params['message'] = 'data';
      $params['node'] = $node;
      $params['node_title'] = 'data';
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = TRUE;
      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    }

  }

  public function onNodeUpdate(CustomEnrollmentEvent $event) {
    $node = $event->getNode();
    $key = 'update';
    $config = \Drupal::config('custom_emails_config.settings');
    $emails = array_merge([
      \Drupal::currentUser()
        ->getEmail(),
    ], $config->get('admin_emails'));
    foreach ($emails as $email) {
      $mailManager = \Drupal::service('plugin.manager.mail');
      $module = 'add_course';
      $to = $email;
      $params['message'] = 'data';
      $params['node'] = $node;
      $params['node_title'] = 'data';
      $langcode = \Drupal::currentUser()->getPreferredLangcode();
      $send = TRUE;
      $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    }
  }

}
