<?php

namespace Drupal\students;

/**
 * @file
 * Contains \Drupal\students\AddForm.
 */

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class AddForm.
 *
 * @package Drupal\students
 */
class AddForm extends FormBase implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Our database repository service.
   *
   * @var \Drupal\students\StudentsStorage
   */
  protected $storage;

  /**
   * The current user.
   *
   * We'll need this service in order to check if the user is logged in.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('students.storage'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * AdminController constructor.
   *
   * @param \Drupal\students\StudentsStorage $storage
   *   Request stack service for the container.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Request stack service for the container.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service for the container.
   */
  public function __construct(StudentsStorage $storage, AccountProxyInterface $current_user, RequestStack $request_stack) {
    $this->storage = $storage;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  protected function request() {
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'students_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->id = $this->request()->get('id');
    $students = $this->storage->get($this->id);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $students ? $students->name : '',
    ];
    $form['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#default_value' => $students ? $students->gender : 0,
      '#options' => [
        0 => $this->t('Male'),
        1 => $this->t('Female'),
      ],
    ];
    $form['faculty_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Faculty number'),
      '#default_value' => $students ? $students->faculty_number : '',
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $students ? $this->t('Edit') : $this->t('Add'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify that the user is logged-in.
    if ($this->currentUser->isAnonymous()) {
      $form_state->setError($form['add'], $this->t('You must be logged in to add values to the database.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->currentUser;
    $name = $form_state->getValue('name');
    $gender = $form_state->getValue('gender');
    $faculty_number = $form_state->getValue('faculty_number');
    $uid = $account->id();
    if (!empty($this->id)) {
      $return = $this->storage->edit($this->id, Html::escape($name), Html::escape($gender), Html::escape($faculty_number));
      if ($return) {
        $this->messenger()->addMessage($this->t('Student has been edited.'));
      }
    }
    else {
      $return = $this->storage->add(Html::escape($name), Html::escape($gender), Html::escape($faculty_number), $uid);
      if ($return) {
        $this->messenger()->addMessage($this->t('Student has been saved.'));
      }
    }
    $form_state->setRedirect('students_list');
  }

}
