<?php

namespace Drupal\file_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\file_link\FileLinkFormatterTrait;

/**
 * Plugin implementation of the 'file_link' formatter.
 *
 * @FieldFormatter(
 *   id = "file_link",
 *   label = @Translation("File Link"),
 *   field_types = {
 *     "file_link"
 *   }
 * )
 */
class FileLinkFormatter extends LinkFormatter {

  use FileLinkFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      if (!isset($element[$delta])) {
        continue;
      }
      $new_element = [
        '#theme' => 'file_link_formatter',
        '#link' => $element[$delta],
      ];
      if (!empty($item->size)) {
        $size = $this->getSetting('format_size') ? format_size($item->size) : $item->size;
        $new_element['#size'] = $size;
      }
      if (!empty($item->format)) {
        $new_element['#format'] = $item->format;
      }
      $element[$delta] = $new_element;
    }
    return $element;
  }

}
