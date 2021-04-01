<?php

namespace Drupal\ocha_datawrapper\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Get a list of charts.
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
    // phpcs:ignore
    $http_client = \Drupal::httpClient();
    $response = $http_client->request(
      'GET',
      'https://api.datawrapper.de/v3/charts',
      [
        'headers' => [
          'authorization' => 'Bearer ' . $this->config('ocha_datawrapper.chartoverview')->get('api_key'),
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
      '#markup' => Markup::create('<ul><li>' . implode('</li><li>', $items) . '</li></ul>'),
    ];

    $form['chart_name'] = [
      '#type' => 'textfield',
      '#title' => 'Name of the chart to create',
      '#required' => TRUE,
    ];

    $form['chart_data'] = [
      '#type' => 'textfield',
      '#title' => 'URL of the data',
      '#description' => $this->t('Should be a list of available sources'),
      '#required' => TRUE,
    ];

    $form['chart_type'] = [
      '#type' => 'radios',
      '#title' => 'Type of the chart',
      '#options' => [
        'tables' => $this->t('Table'),
        'd3-bars-stacked' => $this->t('Stacked bars'),
        'd3-bars-split' => $this->t('Split bars'),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // phpcs:ignore
    $http_client = \Drupal::httpClient();
    $response = $http_client->request(
      'POST',
      'https://api.datawrapper.de/v3/charts',
      [
        'headers' => [
          'authorization' => 'Bearer ' . $this->config('ocha_datawrapper.chartoverview')->get('api_key'),
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
      'https://api.datawrapper.de/v3/charts/' . $chart_id . '/publish',
      [
        'headers' => [
          'authorization' => 'Bearer ' . $this->config('ocha_datawrapper.chartoverview')->get('api_key'),
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
