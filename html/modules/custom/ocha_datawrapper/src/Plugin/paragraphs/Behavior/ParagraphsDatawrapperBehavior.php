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
 *   id="ocha_datawrapper_demo_behavior",
 *   label=@Translation("Datawrapper options"),
 *   description=@Translation("A Plugin to set datawrapper options (demo)")
 * )
 */
class ParagraphsDatawrapperBehavior extends ParagraphsBehaviorBase {

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
    $form['map_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Map style'),
      '#options' => [
        'a' => $this->t('Style 1'),
        'b' => $this->t('Style 2'),
        'c' => $this->t('Style 3'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->pluginId, 'map_style', 'a'),
    ];

    $form['default_attachment'] = [
      '#type' => 'select',
      '#title' => $this->t('Default attachment'),
      '#options' => [
        'a' => $this->t('Attachment 1'),
        'b' => $this->t('Attachment 2'),
        'c' => $this->t('Attachment 3'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting($this->pluginId, 'default_attachment', 'b'),
    ];

    $form['monitoring_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Monitoring period'),
      '#options' => [
        'a' => $this->t('Period 1'),
        'b' => $this->t('Period 2'),
        'c' => $this->t('Period 3'),
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
