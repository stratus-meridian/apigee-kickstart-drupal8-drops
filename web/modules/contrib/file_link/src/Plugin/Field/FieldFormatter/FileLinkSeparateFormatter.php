<?php

namespace Drupal\file_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkSeparateFormatter;
use Drupal\file_link\FileLinkFormatterTrait;

/**
 * Plugin implementation of the 'file_link_separate' formatter.
 *
 * @FieldFormatter(
 *   id = "file_link_separate",
 *   label = @Translation("Separate file link text and URL"),
 *   field_types = {
 *     "file_link"
 *   }
 * )
 */
class FileLinkSeparateFormatter extends LinkSeparateFormatter {

  use FileLinkFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      if (isset($element[$delta])) {
        $element[$delta]['#theme'] = 'file_link_formatter_link_separate';
        $element[$delta] += [
          '#size' => $this->getSetting('format_size') ? format_size($item->size) : $item->size,
          '#format' => $item->format,
        ];
      }
    }
    return $element;
  }

}
