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
    $build['page_markup'] = [
      '#markup' => $paragraph->getBehaviorSetting($this->pluginId, 'page', 1),
    ];
    return $build;
  }

  /**
   * Get the default settings for the behavior form.
   *
   * @param \Drupal\paragraphs\Entity\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return array
   *   The settings array.
   */
  private function getDefaultBehaviorSettings(ParagraphInterface $paragraph) {
    $defaults = [
      'page' => 1,
    ];
    $settings = $paragraph->getAllBehaviorSettings();
    return (array_key_exists($this->pluginId, $settings) ? $settings[$this->pluginId] : []) + $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $wrapper_id = Html::getUniqueId('form-wrapper-plan-attachment-map-behavior-config');

    // Provide an anchor fo AJAX, so that we know what to replace.
    $form['#attributes']['id'] = $wrapper_id;

    // Get the form defaults from the paragraph.
    $defaults = $this->getDefaultBehaviorSettings($paragraph);

    $page = $form_state->has('page') ? $form_state->get('page') : $defaults['page'];
    $form['page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current page'),
      '#default_value' => $page,
      // Setting #value is BAD.
      '#value' => $page,
    ];

    if ($page && $page > 1) {
      $form['actions']['back'] = [
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

    $form['actions']['next'] = [
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

    // Get the complete form so that we can extract the paragraph entity and
    // our subform.
    $form = $form_state->getCompleteForm();

    // Extract the action and go up till the level of actual non-button form
    // elements.
    $array_parents = $triggering_element['#array_parents'];
    $action = array_pop($array_parents);
    array_pop($array_parents);

    // Get the subform and check that the current action is actually defined
    // there.
    $subform = NestedArray::getValue($form, $array_parents);
    if (!in_array($action, array_keys($subform['actions']))) {
      return;
    }

    // Get the paragraph.
    $paragraph = NestedArray::getValue($form_state->getCompleteForm(), array_slice($element['#array_parents'], 0, -4))['#entity'];

    // And it's defaults.
    $defaults = $this->getDefaultBehaviorSettings($paragraph);

    // Handle the action.
    $page = $form_state->has('page') ? $form_state->get('page') : $defaults['page'];
    if ($action == 'next') {
      $page++;
    }
    else {
      $page--;
    }

    // Set the new page in the storage.
    $form_state->set('page', $page);
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
    // This get's called when the paragraph settings are submitted.
    $values = $form_state->getValues();
    $paragraph->setBehaviorSettings($this->pluginId, [
      'page' => $values['page'],
    ]);
  }

}
