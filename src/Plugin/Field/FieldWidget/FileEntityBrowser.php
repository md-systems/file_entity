<?php
/**
 * @file
 * Contains \Drupal\file_entity\Plugin\Field\FieldWidget\FileEditableWidget.
 */

namespace Drupal\file_entity\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\Plugin\Field\FieldWidget\EntityReference;

/**
 * Entity browser file widget.
 *
 * @FieldWidget(
 *   id = "file_entity_browser",
 *   label = @Translation("Entity browser (file)"),
 *   provider = "entity_browser",
 *   multiple_values = TRUE,
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class FileEntityBrowser extends EntityReference {

  /**
   * Due to the table structure, this widget has a different depth.
   *
   * @var int
   */
  protected static $deleteDepth = 3;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['view_mode'] = 'thumbnail';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['field_widget_display']['#access'] = FALSE;
    $element['field_widget_display_settings']['#access'] = FALSE;


    $element['view_mode'] = [
      '#title' => t('File view mode'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => \Drupal::service('entity_display.repository')->getViewModeOptions('file'),
    ];

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $entity_browser_id = $this->getSetting('entity_browser');
    $view_mode = $this->getSetting('view_mode');

    if (empty($entity_browser_id)) {
      return [t('No entity browser selected.')];
    }
    else {
      $browser = $this->entityManager->getStorage('entity_browser')
        ->load($entity_browser_id);
      $summary[] = t('Entity browser: @browser', ['@browser' => $browser->label()]);
    }

    if (!empty($view_mode)) {
      $summary[] = t('View mode: @name', ['@name' => $view_mode]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function displayCurrentSelection($details_id, $field_parents, $entities, FieldItemListInterface $items) {

    $view_mode = $this->getSetting('view_mode');

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('file');
    $config = \Drupal::config('file.settings');

    $delta = 0;

    $order_class = $this->fieldDefinition->getName() . '-delta-order';

    $current = [
      '#type' => 'table',
      '#header' => [$this->t('Preview'), $this->t('Metadata'), ['data' => $this->t('Operations'), 'colspan' => 2], t('Order', array(), array('context' => 'Sort order'))],
      '#empty' => $this->t('No files yet'),
      '#attributes' => ['class' => ['entities-list']],
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $order_class,
        ),
      ),
    ];
    foreach ($entities as $entity) {
      $display = $view_builder->view($entity, $view_mode);

      // Find the default description.
      $description = '';
      $weight = $delta;
      foreach ($items as $item) {
        if ($item->target_id == $entity->id()) {
          $description = $item->description;
          $weight = $item->_weight ?: $delta;
        }
      }

      $current[$entity->id()] = [
        '#attributes' => [
          'class' => ['draggable'],
          'data-entity-id' => $entity->id()
        ],
        'display' => $display,
        'meta' => [
          'description' => [
            '#type' => $config->get('description.type'),
            '#title' => t('Description'),
            '#default_value' => $description,
            '#maxlength' => $config->get('description.length'),
            '#description' => t('The description may be used as the label of the link to the file.'),
            '#access' => $this->fieldDefinition->getType() == 'file',
          ],
        ],
        'edit_button' => [
          '#type' => 'submit',
          '#value' => $this->t('Edit'),
          '#ajax' => [
            'url' => Url::fromRoute('entity_browser.edit_form', ['entity_type' => $entity->getEntityTypeId(), 'entity' => $entity->id()])
          ],
          '#access' => (bool) $this->getSetting('field_widget_edit')
        ],
        'remove_button' => [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#ajax' => [
            'callback' => [get_class($this), 'updateWidgetCallback'],
            'wrapper' => $details_id,
          ],
          '#submit' => [[get_class($this), 'removeItemSubmit']],
          '#name' => $this->fieldDefinition->getName() . '_remove_' . $entity->id(),
          '#limit_validation_errors' => [array_merge($field_parents, [$this->fieldDefinition->getName()])],
          '#attributes' => ['data-entity-id' => $entity->id()],
          '#access' => (bool) $this->getSetting('field_widget_remove')
        ],
        '_weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for row @number', array('@number' => $delta + 1)),
          '#title_display' => 'invisible',
          // Note: this 'delta' is the FAPI #type 'weight' element's property.
          '#delta' => count($entities),
          '#default_value' => $weight,
          '#attributes' => ['class' => array($order_class)],
        ],
      ];

      $delta++;
    }

    return $current;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $ids = empty($values['target_id']) ? [] : explode(' ', trim($values['target_id']));
    $return = [];
    foreach ($ids as $id) {
      $item_values = [
        'target_id' => $id,
        '_weight' => $values['current'][$id]['_weight'],
      ];
      if ($this->fieldDefinition->getType() == 'file' && isset($values['current'][$id]['meta']['description'])) {
        $item_values['description'] = $values['current'][$id]['meta']['description'];
      }
      $return[] = $item_values;
    }

    // Return ourself as the structure doesn't match the default.
    usort($return, function ($a, $b) {
      return SortArray::sortByKeyInt($a, $b, '_weight');
    });

    return array_values($return);
  }

}
