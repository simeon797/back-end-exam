<?php

namespace Drupal\add_course\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

class CustomEnrollmentEvent extends Event {

  const UPDATE_NODE = 'add_course.node.update';

  const REMOVE_COURSE = 'add_course.node.delete';

  protected NodeInterface $node;

  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

  public function getNode(): NodeInterface {
    return $this->node;
  }


}
