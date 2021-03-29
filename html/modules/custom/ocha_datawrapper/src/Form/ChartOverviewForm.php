<?php

namespace Drupal\ocha_datawrapper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ChartOverviewForm.
 */
class ChartOverviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chart_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $http_client = \Drupal::httpClient();
    $response = $http_client->request(
      'GET',
      'https://api.datawrapper.de/v3/charts',
      [
        'headers' => [
          'authorization' => 'Bearer ' . $this->config('ocha_datawrapper.chartoverview')->get('api_key')
        ],
      ]
    );

    $body = $response->getBody() . '';
    $body = json_decode($body, TRUE);

    $items = [];
    foreach ($body['list'] as $chart) {
      $items[] = '<h3>' . $chart['title'] . '</h3>' . '<img style="max-width: 150px;max-height: 150px;" src="' . $chart['thumbnails']['plain'] . '">';
    }

    $form['charts'] = [
      '#type' => 'markup',
      '#markup' => \Drupal\Core\Render\Markup::create('<ul><li>' . implode('</li><li>', $items) . '</li></ul>'),
    ];

    $form['chart_name'] = [
      '#type' => 'textfield',
      '#title' => 'Name of the chart to create',
      '#required' => TRUE,
    ];

    $form['chart_data'] = [
      '#type' => 'textfield',
      '#title' => 'URL of the data',
      '#description' => 'Should be a list of availabel sources',
      '#required' => TRUE,
    ];

    $form['chart_type'] = [
      '#type' => 'radios',
      '#title' => 'Type of the chart',
      '#options' => [
        'tables' => 'Table',
        'd3-bars-stacked' => 'Stacked bars',
        'd3-bars-split' => 'Split bars'
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format'?$value['value']:$value));
    }

    $values = $form_state->getValues();

    $http_client = \Drupal::httpClient();
    $response = $http_client->request(
      'POST',
      'https://api.datawrapper.de/v3/charts',
      [
        'headers' => [
          'authorization' => 'Bearer ' . $this->config('ocha_datawrapper.chartoverview')->get('api_key')
        ],
        'body' => json_encode([
          'title' => $values['chart_name'],
          'type' => $values['chart_type'],
          'externalData' => $values['chart_data'],
        ]),
      ]
    );

    $body = $response->getBody() . '';
    $body = json_decode($body, TRUE);

    // Grab Id and publish.
    $chart_id = $body['id'];
    $response = $http_client->request(
      'POST',
      'https://api.datawrapper.de/v3/charts/'. $chart_id .'/publish',
      [
        'headers' => [
          'authorization' => 'Bearer ' . $this->config('ocha_datawrapper.chartoverview')->get('api_key')
        ],
        'body' => json_encode([
          'title' => $values['chart_name'],
          'type' => $values['chart_type'],
          'externalData' => $values['chart_data'],
        ]),
      ]
    );

    $body = $response->getBody() . '';
    $body = json_decode($body, TRUE);

  }

}
