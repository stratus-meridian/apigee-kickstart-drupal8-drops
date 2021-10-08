<?php

namespace Drupal\file_link;

use Drupal\Core\Language\Language;

/**
 * Data object for a queue item to update a file link data.
 */
final class FileLinkQueueItem {

  /**
   * The request time of when the entity was queued.
   *
   * @var int
   */
  private $time;

  /**
   * The entity type.
   *
   * @var string
   */
  private $type;

  /**
   * The entity id, this only works on content entities.
   *
   * @var int
   */
  private $id;

  /**
   * The revision id for revisionable entity types.
   *
   * @var int|null
   */
  private $revisionId;

  /**
   * The language code for translatable entities.
   *
   * @var string
   */
  private $lang;

  /**
   * FileLinkQueueItem constructor.
   *
   * @param string $type
   *   The entity type.
   * @param int $id
   *   The entity id.
   * @param string $lang
   *   The language code.
   * @param int|null $revisionId
   *   The revision id.
   * @param int|null $time
   *   The timestamp.
   */
  public function __construct(string $type, int $id, string $lang = Language::LANGCODE_NOT_SPECIFIED, int $revisionId = NULL, int $time = NULL) {
    $this->type = $type;
    $this->id = $id;
    $this->lang = $lang;
    $this->revisionId = $revisionId;
    if ($time === NULL) {
      $time = \Drupal::time()->getRequestTime();
    }
    $this->time = $time;
  }

  /**
   * Get the time of the queue.
   *
   * @return int
   *   The timestamp.
   */
  public function getTime(): int {
    return $this->time;
  }

  /**
   * Get the entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Get the entity id.
   *
   * @return int
   *   The entity id.
   */
  public function getId(): int {
    return $this->id;
  }

  /**
   * Get the revision Id.
   *
   * @return int|null
   *   The revision id.
   */
  public function getRevisionId() {
    return $this->revisionId;
  }

  /**
   * Get the entity language.
   *
   * @return string
   *   The entity language
   */
  public function getLang(): string {
    return $this->lang;
  }

  /**
   * Get a key to keep track of queued items.
   *
   * @return string
   *   The key which identifies the queued entity variation.
   */
  public function getKey(): string {
    return $this->getType() . $this->getId() . $this->getLang() . $this->getRevisionId() ?? '';
  }

}
