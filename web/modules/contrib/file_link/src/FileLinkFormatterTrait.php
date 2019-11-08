<?php

namespace Drupal\file_link;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provide common methods for file link formatters.
 */
trait FileLinkFormatterTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format_size' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['format_size'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Format size value'),
      '#default_value' => $this->getSetting('format_size'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->getSetting('format_size') ? $this->t('Size value: formatted') : $this->t('Size value: plain');
    return $summary;
  }

}
