<?php

namespace Drupal\flexible_layout\Plugin\Layout;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a layout plugin with dynamic theme regions.
 */
class FlexibleLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'layout' => [
        'row_1' => [
          'name' => 'Wrapper',
          'type' => 'row',
          'classes' => '',
          'children' => [
            'column_1' => [
              'name' => 'Content',
              'type' => 'column',
              'classes' => '',
              'children' => [],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['container']['#markup'] = '<div class="flexible-layout-container"></div>';
    $config = $this->getConfiguration();
    $layout = $config['layout'];

    $form['layout'] = [
      '#type' => 'textfield',
      '#default_value' => Json::encode($layout),
      '#maxlength' => 1000000000,
      '#attributes' => [
        'class' => ['flexible-layout-json-field', 'visually-hidden'],
      ],
    ];
    $form['#attached']['library'][] = 'flexible_layout/form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['layout'] = JSON::decode($values['layout']);
  }

  protected function getRegionsFromLayout($current) {
    $regions = [];

    foreach ($current as $machine_name => $item) {
      if ($item['type'] == 'row') {

      }
      else {
        $regions[$machine_name] = [
          'label' => $item['name'],
        ];
      }


      if (!empty($item['children'])) {
        $regions = array_merge($regions, $this->getRegionsFromLayout($item['children']));
      }

    }

    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionDefinitions() {
    $regions = $this->getRegionsFromLayout($this->configuration['layout']);
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = $this->pluginDefinition;
    $definition->setRegions($this->getRegionDefinitions());
    return $definition;
  }

}
