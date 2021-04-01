<?php

namespace Drupal\paragraphs_map_behavior\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for building paragraph behaviors with multi-step config forms.
 */
abstract class ParagraphBuildingBlockBase extends ParagraphsBehaviorBase implements ParagraphBuildingBlockInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getSubforms() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormValueFromFormState(FormStateInterface $form_state, $key) {
    if ($form_state->hasValue($key)) {
      return $form_state->getValue($key);
    }
    $paragraph = $form_state->get('paragraph');
    $settings_key = [$form_state->get('current_subform'), $key];
    return $paragraph->getBehaviorSetting($this->pluginId, $settings_key);
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $wrapper_id = Html::getUniqueId('form-wrapper-multi-step-behavior-config');

    // Provide an anchor fo AJAX, so that we know what to replace.
    $form['#attributes']['id'] = $wrapper_id;

    $forms = $this->getSubforms();
    if (empty($forms)) {
      return parent::buildBehaviorForm($paragraph, $form, $form_state);
    }

    $step = $form_state->has('step') ? $form_state->get('step') : 0;
    if ($step > count($forms)) {
      $step = count($forms);
    }

    $form_keys = array_keys($forms);
    $form_key = $form_keys[$step];
    $form_callback = array_values($forms)[$step];

    if (!method_exists($this, $form_callback)) {
      return parent::buildBehaviorForm($paragraph, $form, $form_state);
    }

    // Set state.
    $form_state->set('step', $step);
    $form_state->set('current_subform', $form_key);
    $form_state->set('paragraph', $paragraph);

    // Prepare the subform.
    $form[$form_key] = [
      '#type' => 'container',
      '#parents' => array_merge($form['#parents'], [$form_key]),
    ];

    // Set initial values for this form step.
    if ($form_state->has($form_key)) {
      // This is a bit awkward and probably the result of me not fully
      // understanding how these subform states are handled, but it seems that
      // they need to be set under 2 different keys, one including the path to
      // the values from the root of the parent form, and one where the value
      // is set relative to the subform.
      $form_state->setValue($form[$form_key]['#parents'], $form_state->get($form_key));
      $form_state->setValue($form_key, $form_state->get($form_key));
    }
    $subform_state = SubformState::createForSubform($form[$form_key], $form, $form_state);

    // And build the subform structure.
    $form[$form_key] += $this->{$form_callback}($form, $subform_state);

    if ($step > 0) {
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

    if ($step < count($forms) - 1) {
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
    }

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

    if (end($triggering_element['#parents']) == 'submit') {
      // Why does this even get called for the general submit action of the
      // paragraph form?
      // Anyway, let's handle this. First get the values and the subform key we
      // have been on when the save button was clicked.
      $values = $form_state->getValues();
      $form_key = $form_state->get('current_subform');

      // Massage the value parents so that we can extract the submitted values
      // relating to our subform.
      $value_parents = array_slice($triggering_element['#parents'], 0, -2);
      unset($value_parents[1]);
      $value_parents = array_merge($value_parents, [
        'behavior_plugins',
        $this->pluginId,
        $form_key,
      ]);

      // Get the values for that subform and put it into the form storage, so
      // that we have them available in submitBehaviorForm().
      $step_values = NestedArray::getValue($values, $value_parents);
      $form_state->set($form_key, $step_values);
      return;
    }

    // Now handle the actual next/back buttons.
    if (end($triggering_element['#parents']) != end($element['#parents'])) {
      return;
    }

    // Get the complete form so that we can extract the paragraph entity and
    // our subform.
    $form = $form_state->getCompleteForm();

    // Extract the action and go up until the level of actual non-button form
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

    // Handle the action.
    $step = $form_state->has('step') ? $form_state->get('step') : 0;

    // Handle the submitted values and put them into the form storage.
    $values = $form_state->getValues();
    $value_parents = array_slice($triggering_element['#parents'], 0, -2);

    $step_values = NestedArray::getValue($values, $value_parents);
    $form_key = $form_state->get('current_subform');
    $form_state->set($form_key, $step_values[$form_key]);

    // Set the new step in the storage.
    $step = $action == 'next' ? $step + 1 : $step - 1;
    $form_state->set('step', $step);

    // Still no effect it seems.
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
    // This get's called when the paragraph settings are submitted.
    $subforms = $this->getSubforms();
    if (empty($subforms)) {
      return;
    }
    // Put stored subform values into the behavior settings for this plugin.
    $settings = [];
    foreach (array_keys($subforms) as $form_key) {
      $settings[$form_key] = $form_state->get($form_key);
    }
    $paragraph->setBehaviorSettings($this->pluginId, $settings);
  }

}
