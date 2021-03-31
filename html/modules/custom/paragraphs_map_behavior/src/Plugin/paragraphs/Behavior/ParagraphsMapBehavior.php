<?php

namespace Drupal\paragraphs_map_behavior\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define a map behavior for paragraph entity.
 *
 * @ParagraphsBehavior(
 *   id="paragraphs_map_behavior",
 *   label=@Translation("Paragraphs Map Behavior"),
 *   description=@Translation("A Plugin that allows to render a configurable map")
 * )
 */
class ParagraphsMapBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $page = $form_state->getValue('page');
    $this->configuration['page'] = $page;
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'page' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $_form_state = $form_state->getCompleteFormState();
    $wrapper_id = Html::getUniqueId('form-wrapper-plan-attachment-map-behavior-config');

    $subform = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $wrapper_id,
      ],
    ];

    $page = $_form_state->has('page') ? $_form_state->get('page') : 1;
    $subform['page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current page'),
      '#default_value' => $page,
      // Setting #value is BAD.
      '#value' => $page,
    ];

    if ($_form_state->has('page') && $_form_state->get('page') > 1) {
      $subform['actions']['back'] = [
        '#type' => 'button',
        '#name' => 'back-button',
        '#button_type' => 'primary',
        '#value' => $this->t('Back'),
        // '#submit' => [['::submitAjax']],
        '#element_validate' => [[$this, 'validateBehavior']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [$this, 'navigateStep'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
          'method' => 'replace',
        ],
      ];
    }

    $subform['actions']['next'] = [
      '#type' => 'button',
      '#name' => 'next-button',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      // '#submit' => [['::submitAjax']],
      '#element_validate' => [[$this, 'validateBehavior']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'navigateStep'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
        'method' => 'replace',
      ],
    ];

    $form['wrapper'] = $subform;
    return parent::buildBehaviorForm($paragraph, $form, $form_state);
  }

  /**
   * Validate the current page in the behavior settings form.
   *
   * This should only be used for validation, but we use it to set the current
   * navigation step too. Probably not the best idea.
   *
   * @param array $element
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   */
  public function validateBehavior(array &$element, FormStateInterface $form_state) {
    // Every button get's validated, but we only need to handle this once.
    $triggering_element = $form_state->getTriggeringElement();
    if (end($triggering_element['#parents']) != end($element['#parents'])) {
      return;
    }

    $form = $form_state->getCompleteForm();

    $array_parents = $triggering_element['#array_parents'];
    $action = array_pop($array_parents);
    array_pop($array_parents);

    $subform = NestedArray::getValue($form, $array_parents);
    if (!in_array($action, array_keys($subform['actions']))) {
      return;
    }

    $parents = $triggering_element['#parents'];
    array_pop($parents);
    array_pop($parents);

    $page = $form_state->has('page') ? $form_state->get('page') : 1;
    if ($action == 'next') {
      $page++;
    }
    else {
      $page--;
    }

    // Set the new page in the storage.
    $form_state->set('page', $page);

    // This doesn't seem to work.
    $values = $form_state->getValues();
    $step_values = NestedArray::getValue($values, $parents);
    $step_values['page'] = $page;
    $form_state->setValue($parents, $step_values);

    // This doesn't seem to have an effect.
    $form_state->setRebuild();
  }

  /**
   * Ajax callback to load new step.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function navigateStep(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents);
    array_pop($parents);
    $form_state->setRebuild();
    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    // This only get's called when the paragraph settings are submitted.
    $paragraph->setBehaviorSettings($this->pluginId, [
      'page', $form_state->getValue('page'),
    ]);
    parent::submitBehaviorForm($paragraph, $form, $form_state);
  }

}
