<?php

namespace Drupal\file_link\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file_link\FileLinkQueueItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'file_link_metadata_update' queue worker.
 *
 * @QueueWorker(
 *   id = "file_link_metadata_update",
 *   title = @Translation("File Link Metadata Update"),
 *   cron = {"time" = 60}
 * )
 */
class FileLinkMetadataUpdate extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Counter of processing queue workers.
   *
   * @var int
   */
  protected static $processing = 0;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FileLinkMetadataUpdate constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data instanceof FileLinkQueueItem) {
      $storage = $this->entityTypeManager->getStorage($data->getType());

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if ($data->getRevisionId() !== NULL) {
        $entity = $storage->loadRevision($data->getRevisionId());
      }
      else {
        $entity = $storage->load($data->getId());
      }
      if ($entity === NULL) {
        // The entity must have been removed.
        return;
      }
      if ($entity->hasTranslation($data->getLang())) {
        $entity = $entity->getTranslation($data->getLang());
      }
      // Do not create a revision but re-save the revision.
      if ($entity->getEntityType()->isRevisionable()) {
        $entity->setNewRevision(FALSE);
      }

      if ($entity instanceof EntityChangedInterface) {
        if ($entity->getChangedTime() > $data->getTime()) {
          // The entity has been changed since.
          return;
        }
        // Do not update the changed time here.
        $entity->setChangedTime($entity->getChangedTime());
      }

      // Set the static property to be processing.
      static::$processing++;
      try {
        $entity->save();
      }
      catch (\Exception $exception) {
        // Decrease the counter and re-throw the exception.
        static::$processing--;
        throw $exception;
      }
      static::$processing--;
    }
  }

  /**
   * Indication of whether the queue is processing.
   *
   * @return bool
   *   True if the queue worker is processing.
   */
  public static function isProcessing(): bool {
    return static::$processing > 0;
  }

}
