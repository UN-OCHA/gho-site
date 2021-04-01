<?php

namespace Drupal\ocha_datawrapper\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * POC behavior plugin.
 *
 * @ParagraphsBehavior(
 *   id="ocha_datawrapper_data_behavior",
 *   label=@Translation("Datawrapper data"),
 *   description=@Translation("A Plugin to select the data (demo)")
 * )
 */
class ParagraphsHumDataBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
    $container->get('entity_field.manager'),
    );
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
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data source options'),
    ];

    $form['wrapper']['map_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Source'),
      '#options' => [
        'a' => $this->t('Plan'),
        'b' => $this->t('Style 2'),
        'c' => $this->t('Style 3'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->pluginId, 'map_style', 'a'),
    ];

    $form['wrapper']['default_attachment'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Columns'),
      '#options' => [
        'a' => $this->t('Col 1'),
        'b' => $this->t('Col 2'),
        'c' => $this->t('Col 3'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->pluginId, 'default_attachment', 'b'),
    ];

    $form['wrapper']['monitoring_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Some filters'),
      '#options' => [
        'a' => $this->t('Filter 1'),
        'b' => $this->t('Filter 2'),
        'c' => $this->t('Filter 3'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->pluginId, 'monitoring_period', 'c'),
    ];

    return parent::buildBehaviorForm($paragraph, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $paragraph->setBehaviorSettings($this->pluginId, [
      'map_style', $form_state->getValue('map_style'),
      'default_attachment', $form_state->getValue('default_attachment'),
      'monitoring_period', $form_state->getValue('monitoring_period'),
    ]);
    parent::submitBehaviorForm($paragraph, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function entityViewModeAlter(&$view_mode, ParagraphInterface $paragraph, array $context) {
  }

}
