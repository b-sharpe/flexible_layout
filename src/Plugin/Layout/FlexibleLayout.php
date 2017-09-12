<?php

namespace Drupal\flexible_layout\Plugin\Layout;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides a layout plugin with dynamic theme regions.
 */
class FlexibleLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'layout' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $layout = !empty($this->configuration['layout']) ? $this->configuration['layout'] : [];
    $layout = [];

    $header = [
      'type' => $this->t("Type"),
      'name' => $this->t("Name"),
      'classes' => $this->t("Classes"),
      'actions' => $this->t("Actions"),
    ];

    $form['section_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('There are no items yet. <a class="use-ajax" href=":url">Add item</a>.', [':url' => Url::fromRoute('flexible_layout.layout', ['section' => 'default'])->toString()]),
    ];

    foreach (Element::children($layout) as $section) {
      kint($section);
    }


    // Attach the library for pop-up dialogs/modals.
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';


    $form['layout'] = [
      '#type' => 'textfield',
      '#default_value' => Json::encode($layout),
      '#maxlength' => 1000000000,
      '#attributes' => [
        'class' => ['flexible-layout-json-field', 'visually-hidden'],
      ]
    ];
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

  /**
   * @param $current
   *
   * @return array
   */
  protected function getRegionsFromLayout($current, $prefix = '') {
    $regions = [];
    if ($current['type'] != 'row') {
      $regions[$current['machine_name']] = [
        'label' => $prefix . $current['name'],
      ];
    }
    else {
      $prefix .= '- ';
    }

    foreach ($current['children'] as $column) {
      $regions = array_merge($regions, $this->getRegionsFromLayout($column, $prefix));
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
