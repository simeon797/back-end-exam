<?php

namespace Drupal\add_course\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\node\NodeInterface;

class CustomEnrollmentEvent extends Event {

  const UPDATE_NODE = 'add_course.node.update';

  const REMOVE_COURSE = 'add_course.node.delete';

  protected string $key;

  protected NodeInterface $node;

  public function __construct(NodeInterface $node, string $key) {
    $this->node = $node;
    $this->key = $key;
  }

  public function getKey(): string {
    return $this->key;
  }

  public function getNode(): NodeInterface {
    return $this->node;
  }


}
