<?php

/**
 * @file
 * Contains \Drupal\rng_conflict\Form\ConflictForm.
 */

namespace Drupal\rng_conflict\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\rng\EventManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Configure event conflict settings.
 */
class ConflictForm extends FormBase {

  /**
   * The RNG event manager.
   *
   * @var \Drupal\rng\EventManagerInterface
   */
  protected $eventManager;

  /**
   * Constructs a new MessageActionForm object.
   *
   * @param \Drupal\rng\EventManagerInterface $event_manager
   *   The RNG event manager.
   */
  public function __construct(EventManagerInterface $event_manager) {
    $this->eventManager = $event_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rng.event_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rng_event_conflict';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $event = NULL) {


    /** @var EntityInterface $event_entity */
    $event_entity = $route_match->getParameter($event);

    $storage = \Drupal::entityTypeManager()->getStorage($event_entity->getEntityTypeId());
    $query = $storage->getQuery();

    $values = [];
    foreach (['field_date', 'field_track'] as $field_name) {
      $values[$field_name] = $event_entity->{$field_name}->value;
      $value = $event_entity->{$field_name}->value;
      $query->condition($field_name, $value);
    }

    // Similar event entity ID's.
    $ids = $query->execute();

    // Unset this event.
    unset($ids[$event_entity->id()]);
//
//    foreach ($ids as $id) {
//      $event = $storage->load($id);
//      $event_meta = $this->eventManager->getMeta($event);
//      $registrant_query = $event_meta->buildRegistrantQuery('user');
//    }

    $form['help']['#plain_text'] = $this->t('A registrant will not be able to register for this event if they are also registered for the following events:');

    $form['events'] = [
      '#type' => 'table',
      '#header' => [$this->t('Label')],
      '#empty' => $this->t('No conflicting events found.'),
    ];

    foreach ($ids as $id) {
      $row = [];;
      $event = $storage->load($id);
      $row['entity']['#markup'] = $event->link();
      $form['events'][] = $row;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
