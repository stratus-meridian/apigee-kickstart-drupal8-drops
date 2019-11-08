<?php

namespace Drupal\file_link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'file_link_default' widget.
 *
 * @FieldWidget(
 *   id = "file_link_default",
 *   label = @Translation("File Link"),
 *   field_types = {
 *     "file_link"
 *   }
 * )
 */
class FileLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Do not allow entity auto-complete provided by parent 'link_default'.
    $element['uri']['#type'] = 'url';
    $element['uri']['#description'] = [
      [
        '#markup' => $this->t("Enter the full file URL, such as <code>http://example.com/doc.pdf</code>."),
      ],
    ];
    $extensions = implode(', ', explode(' ', $this->getFieldSetting('file_extensions')));
    if ($extensions) {
      $element['uri']['#description'][] = [
        '#prefix' => ' ',
        '#markup' => $this->t('Allowed extensions: %ext.', ['%ext' => $extensions]),
      ];
    }
    unset(
      $element['uri']['#target_type'],
      $element['uri']['#attributes']['data-autocomplete-first-character-blacklist'],
      $element['uri']['#process_default_value']
    );

    // Add meta-data values.
    $element['size'] = [
      '#type' => 'value',
      '#value' => (int) (isset($items[$delta]->size) ? $items[$delta]->size : NULL),
    ];
    $element['format'] = [
      '#type' => 'value',
      '#value' => isset($items[$delta]->format) ? $items[$delta]->format : NULL,
    ];
    return $element;
  }

}
