<?php


namespace Drupal\add_course\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\add_course\Event\CustomEnrollmentEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class CourseController extends ControllerBase {

 /**
   * Returns nodes of the "course" content type in JSON format.
   */

   public function coursesApi() {

    $query = \Drupal::entityQuery('node')
    ->condition('type', 'course')
    ->accessCheck(TRUE);
    $nids = $query->execute();

  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  $data = [];
  foreach ($nodes as $node) {
    $instructor_reference = $node->get('field_instructor')->entity;
    $taxonomy_reference = $node->get('field_categories')->entity;
    $resources_reference = $node->get('field_related_resources')->entity;

    $data[] = [
      'courseName' => $node->getTitle(),
      'description' => $node->get('field_description')->value,
      'startDate' => strtotime($node->get('field_start_date')->value),
      'endDate' => strtotime($node->get('field_end_date')->value),
      'instructor' => [
        'name' => $instructor_reference ? $instructor_reference->get('field_name')->value : '',
        'bio' => $instructor_reference ? $instructor_reference->get('field_bio')->value : '',
        'email' => $instructor_reference ? $instructor_reference->get('field_email')->value : '',
        'phone' => $instructor_reference ? $instructor_reference->get('field_phone')->value : '',
        ],
        'Categories' => $taxonomy_reference ? $taxonomy_reference->label() : '',
        'title' => $resources_reference ? $resources_reference->get('field_resource_title')->value : '',
        //'description' => $resources_reference ? $resources_reference->get('field_description1')->value : '',
    ];
  }

  $response = new JsonResponse();
  $response->setData($data);
  $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

  return $response;
}

 

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;


  /**
   * Constructs an EnrollmentsController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   * The database connection service.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   * The date formatter service.
   */
  public function __construct(Connection $database, DateFormatterInterface $dateFormatter) {
    // Assign the database connection service to the class property.
    $this->database = $database;
    // Assign the date formatter service to the class property.
    $this->dateFormatter = $dateFormatter;
  }


  public static function create(ContainerInterface $container) {
    // Creates a new instance of the EnrollmentsController class and pass the services to its constructor.
    return new static(
      // Retrieve the database connection service from the container.
      $container->get('database'),   // Inject the database connection service
      $container->get('date.formatter')
    );
  }

  /**
   * Fetch all students and their enrollments from the database.
   *
   * @return array|null
   */
  protected function load() {
    try {
      // Constructing the query to fetch information about students and their courses.
      $select_query = $this->database->select('users_field_data', 'usr');

      $select_query->join('user__field_first_name', 'ufn', 'usr.uid = ufn.entity_id');
      $select_query->join('user__field_last_name', 'uln', 'usr.uid = uln.entity_id');
      $select_query->join('user__field_enroll_courses', 'ucs', 'usr.uid = ucs.entity_id');
      $select_query->join('node_field_data', 'nfd', 'ucs.field_enroll_courses_target_id = nfd.nid');

      $select_query->addField('usr', 'uid');
      $select_query->addField('usr', 'name');
      $select_query->addExpression('CONCAT(ufn.field_first_name_value, " ", uln.field_last_name_value)', 'full_name');
      $select_query->addField('ucs', 'field_enroll_courses_target_id');
      $select_query->addField('nfd', 'title');
      //$select_query->orderBy('', '');


      // Executing the query and fetching the results as an associative array.
      $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      // Returning the array containing information about students and their courses.
      return $entries;
    } catch (\Exception $e) {
      // Handling exceptions and displaying an error message.
      $this->messenger()->addError(
        t('Unable to access the database at the moment. Error: @error', ['@error' => $e->getMessage()])
      );
      // Returning NULL in case of an error.
      return NULL;
    }
  }

  /**
   * Generates a report of the students and their enrollments.
   *
   * @return array
   */
  public function report() {
    $content = [];
    // Display a message at the top of the report.
    $content['message'] = [
      '#markup' => t('Bellow is a list of students including their information and the name of the course they enrolled.'),
    ];
    // Define headers for the table.
    $headers = [
      t('Id'),
      t('Username'),
      t('Full Name',),
      t('Course Id'),
      t('Course title'),
    ];
    // Load data about students and their enrollments.
    $entries = $this->load();
    // Initialize an array to hold table rows.
    $table_rows = [];
    // Iterate through each entry and format the data for the table.
    foreach ($entries as $entry) {
      $table_rows[] = [
        'id' => $entry['uid'],
        'username' => $entry['name'],
        'full_name' => $entry['full_name'],
        'course_id' => $entry['field_enroll_courses_target_id'],
        'course_title' => $entry['title'],
      ];
    }
    // Build the table with defined headers and formatted rows.
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $table_rows,
      '#empty' => t('No entries available.'),
    ];
    $content['#cache']['max-age'] = 0;
    // Return the renderable content.
    return $content;
  }



  /**
   * Fetch the most recent courses from the database.
   *
   * @return array|null
   */
  protected function loadRecentCourses() {
    try {
      // Constructing the query to fetch information about the most recent courses.
      $select_query = $this->database->select('node_field_data', 'nfd');

      $select_query->addField('nfd', 'title');
      $select_query->addField('nfd', 'created');
      $select_query->orderBy('nfd.created', 'DESC');
      $select_query->condition('nfd.type', 'course');
      // Execute the query and fetching the results as an associative array.
      $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

      return $entries;
    } catch (\Exception $e) {
      $this->messenger()->addError(
        t('Unable to access the database at the moment. Error: @error', ['@error' => $e->getMessage()])
      );
      return NULL;
    }
  }


  /**
   * Generates a report of the recent courses.
   *
   * @return array
   */
  public function reportRecentCourses() {
    $content = [];

    $content['message'] = [
      '#markup' => t('Below is a list of the most recent courses.'),
    ];

    $headers = [
      t('Course title'),
      t('Creation date'),
    ];

    $entries = $this->loadRecentCourses();

    $table_rows = [];
    foreach ($entries as $entry) {
      $table_rows[] = [

        'course_title' => $entry['title'],
        'creation_date' => $this->dateFormatter->format($entry['created'], 'short'), //\Drupal::service('date.formatter')->format($entry['created'], 'short'),
      ];
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $table_rows,
      '#empty' => t('No entries available.'),
    ];

    $content['#cache']['max-age'] = 0;

    return $content;
  }


  /**
   * Fetch the most enrolled courses from the database.
   *
   * @return array|null
   */
  protected function loadMostEnrolled() {
    try {


      $select_query = $this->database->select('user__field_enroll_courses', 'ucs');

      $select_query->addField('ucs', 'field_enroll_courses_target_id');
      $select_query->groupBy('ucs.field_enroll_courses_target_id');
      $select_query->addExpression('COUNT(ucs.field_enroll_courses_target_id)', 'enrollment_count');

      $select_query->leftJoin('node_field_data', 'nfd', 'ucs.field_enroll_courses_target_id = nfd.nid');
      $select_query->addField('nfd', 'title');

      $select_query->orderBy('enrollment_count', 'DESC');
      $select_query->range(0, 10); // Adjust the range as needed


      $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

      return $entries;
    } catch (\Exception $e) {
      $this->messenger()->addError(
        t('Unable to access the database at the moment. Error: @error', ['@error' => $e->getMessage()])
      );
      return NULL;
    }
  }


  /**
   * Returns an array of the most recent courses.
   *
   * @return array
   */
  public function reportMostEnrolled() {
    $content = [];

    $content['message'] = [
      '#markup' => t('Below is a list of the most enrolled courses.'),
    ];

    $headers = [
      t('Course ID'),
      t('Course Title'),
      t('Enrollment Count'),
    ];

    $entries = $this->loadMostEnrolled();

    $table_rows = [];
    foreach ($entries as $entry) {
      $table_rows[] = [

        'course_id' => $entry['field_enroll_courses_target_id'],
        'course_title' => $entry['title'],
        'enrollment_count' => $entry['enrollment_count'],

      ];
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $table_rows,
      '#empty' => t('No entries available.'),
    ];

    $content['#cache']['max-age'] = 0;

    return $content;
  }




  /**
   * Fetch a list of the users sorted by the highest number of courses they enrolled from the database.
   *
   * @return array|null
   */
  protected function loadUsersByEnrollment() {
    try {

      // Constructing the query to fetch information about users and their enrolled courses.
      $select_query = $this->database->select('users_field_data', 'usr');

      $select_query->join('user__field_first_name', 'ufn', 'usr.uid = ufn.entity_id');
      $select_query->join('user__field_last_name', 'uln', 'usr.uid = uln.entity_id');

      $select_query->join('user__field_enroll_courses', 'ucs', 'usr.uid = ucs.entity_id');
      $select_query->join('node_field_data', 'nfd', 'ucs.field_enroll_courses_target_id = nfd.nid');

      $select_query->addField('usr', 'uid');
      $select_query->addField('usr', 'name');
      $select_query->addExpression('CONCAT(ufn.field_first_name_value, " ", uln.field_last_name_value)', 'full_name');
      $select_query->addField('ucs', 'field_enroll_courses_target_id');
      $select_query->addField('nfd', 'title');
      $select_query->addExpression('COUNT(ucs.field_enroll_courses_target_id)', 'course_count'); // Count courses per user
      $select_query->groupBy('usr.uid'); // Group by user ID
      $select_query->orderBy('course_count', 'DESC'); // Order by course count in descending order

      // Execute the query and fetch the results as an associative array.
      $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      // Build a result array for each user, including their enrolled courses.
      $result = [];
      foreach ($entries as $entry) {
        $result[] = [
          'id' => $entry['uid'],
          'username' => $entry['name'],
          'full_name' => $entry['full_name'],
          'courses_enrolled' => $this->getCoursesForUser($entry['uid']),
        ];
      }


      return $result;
    }
    // Handle exceptions and display an error message.
    catch (\Exception $e) {
      $this->messenger()->addError(
        t('Unable to access the database at the moment. Error: @error', ['@error' => $e->getMessage()])
      );
      return NULL;
    }
  }


  /**
   * Fetch the courses enrolled by a specific user.
   *
   * @return array
   */
  protected function getCoursesForUser($userId) {

    $query = $this->database->select('user__field_enroll_courses', 'ucs');
    $query->join('node_field_data', 'nfd', 'ucs.field_enroll_courses_target_id = nfd.nid');

    $query->addField('nfd', 'title');
    $query->condition('ucs.entity_id', $userId);

    // Execute the query and fetch the course titles as an indexed array.
    $courses = $query->execute()->fetchCol();

    return $courses;
  }

  /**
   * Returns an array of users sorted by the highest number of courses they enrolled.
   *
   * @return array
   */
  public function reportUsersByEnrollment() {
    $content = [];

    $content['message'] = [
      '#markup' => t('Below is a list of users sorted by the highest number of courses they enrolled.'),
    ];

    $headers = [

      t('ID'),
      t('Username'),
      t('Full Name'),
      t('Number of Courses Enrolled'),
      t('Courses Enrolled')
    ];

    $entries = $this->loadUsersByEnrollment();

    $table_rows = [];
    foreach ($entries as $entry) {
      $table_rows[] = [

        'id' => $entry['id'],
        'username' => $entry['username'],
        'full_name' => $entry['full_name'],
        'num_courses_enrolled' => count($entry['courses_enrolled']),
        'courses_enrolled' => implode(', ', $entry['courses_enrolled']),

      ];
    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $table_rows,
      '#empty' => t('No entries available.'),
    ];

    $content['#cache']['max-age'] = 0;
    return $content;
  }

  public function enroll(NodeInterface $node) {

    $user_id = \Drupal::currentUser()->id();
    $user_entity = User::load($user_id);
    if ($user_entity->hasField('field_enroll_courses')) {
      $user_entity->get('field_enroll_courses')
        ->appendItem($node);
      $user_entity->save();
      Cache::invalidateTags($node->getCacheTagsToInvalidate());
    }
    $event = new CustomEnrollmentEvent($node);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, CustomEnrollmentEvent::UPDATE_NODE);

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
        if ($user_enroll_course_node->id() === $node->id()) {
          $index = $delta;
          break;
        }
      }
      if ($index !== NULL) {
        $user_enroll_courses_field->removeItem($index);
        $user_entity->save();
        Cache::invalidateTags($node->getCacheTagsToInvalidate());
      }

    $event = new CustomEnrollmentEvent($node);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, CustomEnrollmentEvent::REMOVE_COURSE);
    }
    return new RedirectResponse('/course-view');
  }
}
