<?php

namespace Drupal\students;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class StudentsStorage.
 *
 * @package Drupal\students
 */
class StudentsStorage extends ControllerBase {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  /**
   * Method getAll().
   *
   * @return mixed
   *   DB query.
   */
  public function getAll() {
    $result = $this->connection->select('students', 's')
      ->fields('s')
      ->execute();
    return $result;
  }

  /**
   * Get if $id exists.
   *
   * @param string $id
   *   Id of the record.
   *
   * @return bool
   *   Execute get($id) method and return bool.
   */
  public function exists($id) {
    return (bool) $this->get($id);
  }

  /**
   * Getter of DB Students data.
   *
   * @param string $id
   *   Id of the record.
   *
   * @return bool|array
   *   DB query.
   */
  public function get($id) {
    $result = $this->connection->query('SELECT * FROM {students} WHERE id = :id', [':id' => $id])
      ->fetchAllAssoc('id');
    if ($result) {
      return $result[$id];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Add method.
   *
   * @param string $name
   *   Student's name.
   * @param string $gender
   *   Student's gender.
   * @param string $faculty_number
   *   Student's faculty number.
   * @param string|null $uid
   *   Account User ID.
   *
   * @throws \Exception
   *   DB insert query.
   *
   * @return int|null
   *   DB insert query return value.
   */
  public function add($name, $gender, $faculty_number, $uid = NULL) {
    $fields = [
      'name' => $name,
      'gender' => $gender,
      'faculty_number' => $faculty_number,
    ];
    $return_value = NULL;
    try {
      $return_value = $this->connection->insert('students')
        ->fields($fields)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage($this->t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value;
  }

  /**
   * Edit method.
   *
   * @param string $id
   *   Student's id.
   * @param string $name
   *   Student's name.
   * @param string $gender
   *   Student's gender.
   * @param string $faculty_number
   *   Student's faculty number.
   */
  public function edit($id, $name, $gender, $faculty_number) {
    $fields = [
      'name' => $name,
      'gender' => $gender,
      'faculty_number' => $faculty_number,
    ];
    $this->connection->update('students')
      ->fields($fields)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Delete method.
   *
   * @param string $id
   *   DB delete query.
   */
  public function delete($id) {
    $this->connection->delete('students')
      ->condition('id', $id)
      ->execute();
  }

}
