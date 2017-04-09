<?php

namespace Drupal\flexible_layout\Plugin\Layout;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutBase;

/**
 * Provides a layout plugin with dynamic theme regions.
 */
class FlexibleLayout extends LayoutBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'num_regions' => 1,
      'rows' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['num_regions'] = [
      '#type' => 'textfield',
      '#title' => 'Number of regions',
      '#default_value' => $this->configuration['num_regions'],
    ];
    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'flexible-layout-form-wrapper',
      ],
    ];
    $rows = !empty($this->configuration['rows']) ? $this->configuration['rows'] : [];
    foreach ($rows as $i => $row) {
      $form['wrapper'][] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['flexible-layout-form-row']],
        ['#markup' => 'Row ' . $i],
      ];
    }
    $form['wrapper']['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#ajax' => [
        'callback' => [self::class, 'addRowAjax'],
        'wrapper' => 'flexible-layout-form-wrapper',
        'method' => 'replace',
      ],
      '#attributes' => [
        'class' => ['flexible-layout-form-add-row'],
      ],
    ];
    $form['#attached']['library'][] = 'flexible_layout/form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionDefinitions() {
    $regions = [];
    $num_regions = !empty($this->configuration['num_regions']) ? $this->configuration['num_regions'] : 1;
    for ($i=0;$i<$num_regions;++$i) {
      $regions['region_' . $i] = [
        'label' => 'Region ' . $i,
      ];
    }
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = $this->pluginDefinition;
    $definition['region_names'] = [];
    $definition['regions'] = $this->getRegionDefinitions();
    foreach ($definition['regions'] as $region_id => $region_definition) {
      $definition['region_names'][$region_id] = $region_definition['label'];
    }
    return $definition;
  }

  public static function addRowAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#array_parents'], 0, -1);
    $selection = NestedArray::getValue($form, $parents);
    return $selection;
  }

}
