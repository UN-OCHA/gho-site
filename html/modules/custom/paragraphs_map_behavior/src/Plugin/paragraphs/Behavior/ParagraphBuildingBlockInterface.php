<?php

namespace Drupal\paragraphs_map_behavior\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for paragraph building blocks.
 */
interface ParagraphBuildingBlockInterface {

  /**
   * Get a list of subforms for the behavior configuration form.
   *
   * @return array
   *   An associative array of form builder methods, keyed by a unique string.
   */
  public function getSubforms();

  /**
   * Get a default value for a form item..
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will use $values[$key] = $value. If $key is an array, each
   *   element of the array will be used as a nested key.
   *   If $key = array('foo', 'bar') it will use $values['foo']['bar'] = $value.
   *
   * @return mixed
   *   The value retrieved for the given key.
   */
  public function getDefaultFormValueFromFormState(FormStateInterface $form_state, $key);

}
