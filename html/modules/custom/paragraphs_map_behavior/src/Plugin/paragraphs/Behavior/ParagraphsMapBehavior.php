<?php

namespace Drupal\paragraphs_map_behavior\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Define a map behavior for paragraph entity.
 *
 * Note that everything AJAX related is handled in ParagraphBuildingBlockBase,
 * so that this behavior instance can focus on defining forms and config, and
 * altering the render array.
 * Submitted form values will be stored in the behavior settings of the
 * paragraph under the pluginid of this plugin. They are stored as an
 * associative array keyed by the subform keys defined here.
 *
 * @ParagraphsBehavior(
 *   id="paragraphs_map_behavior",
 *   label=@Translation("Paragraphs Map Behavior"),
 *   description=@Translation("A Plugin that allows to render a configurable map")
 * )
 */
class ParagraphsMapBehavior extends ParagraphBuildingBlockBase {

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $items = array_filter([
      $paragraph->getBehaviorSetting($this->pluginId, [
        'basic',
        'input_1',
      ], NULL),
      $paragraph->getBehaviorSetting($this->pluginId, [
        'sidebar_items',
        'input_2',
      ], NULL),
    ]);
    $build['page_markup'] = [
      '#markup' => count($items) ? Markup::create('<p>' . implode('</p><p>', $items) . '</p>') : $this->t('Empty'),
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubforms() {
    return [
      'basic' => 'basicConfigForm',
      'sidebar_items' => 'sidebarItemsForm',
    ];
  }

  /**
   * Form builder for the basic config form.
   *
   * @param array $form
   *   An associative array containing the initial structure of the subform.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The full form array for this subform.
   */
  public function basicConfigForm(array $form, FormStateInterface $form_state) {
    $form['input_1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input 1'),
      '#default_value' => $this->getDefaultFormValueFromFormState($form_state, 'input_1'),
    ];
    return $form;
  }

  /**
   * Form builder for the basic config form.
   *
   * @param array $form
   *   An associative array containing the initial structure of the subform.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The full form array for this subform.
   */
  public function sidebarItemsForm(array $form, FormStateInterface $form_state) {
    $form['input_2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input 2'),
      '#default_value' => $this->getDefaultFormValueFromFormState($form_state, 'input_2'),
    ];
    return $form;
  }

}
