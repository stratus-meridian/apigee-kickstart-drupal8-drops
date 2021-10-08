<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for promotions.
 */
class PromotionListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The usage.
   *
   * @var \Drupal\commerce_promotion\PromotionUsageInterface
   */
  protected $usage;

  /**
   * The disabled promotions.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface[]
   */
  protected $disabledEntities = [];

  /**
   * The enabled promotions.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface[]
   */
  protected $enabledEntities = [];

  /**
   * The usage counts.
   *
   * @var array
   */
  protected $usageCounts = [];

  /**
   * Whether tabledrag is enabled.
   *
   * @var bool
   */
  protected $hasTableDrag = TRUE;

  /**
   * Divide the limit by 2 (because we have 2 listings).
   *
   * @var int
   */
  protected $limit = 25;

  /**
   * The status condition value.
   *
   * @var bool
   */
  protected $statusCondition = TRUE;

  /**
   * Constructs a new PromotionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_promotion\PromotionUsageInterface $usage
   *   The usage.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder, PromotionUsageInterface $usage) {
    parent::__construct($entity_type, $storage);

    $this->formBuilder = $form_builder;
    $this->usage = $usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('form_builder'),
      $container->get('commerce_promotion.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_promotions';
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    $entities = $this->storage->loadMultiple($entity_ids);
    // Sort the entities using the entity class's sort() method.
    uasort($entities, [$this->entityType->getClass(), 'sort']);
    // Load the usage counts for each promotion.
    $this->usageCounts += $this->usage->loadMultiple($entities);

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->condition('status', $this->statusCondition)
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['usage'] = $this->t('Usage');
    $header['customer_limit'] = $this->t('Per-customer limit');
    $header['start_date'] = $this->t('Start date');
    $header['end_date'] = $this->t('End date');
    if ($this->hasTableDrag) {
      $header['weight'] = $this->t('Weight');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $current_usage = $this->usageCounts[$entity->id()];
    $usage_limit = $entity->getUsageLimit();
    $usage_limit = $usage_limit ?: $this->t('Unlimited');
    $customer_limit = $entity->getCustomerUsageLimit();
    $customer_limit = $customer_limit ?: $this->t('Unlimited');
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $entity */
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $entity->getWeight();
    $row['name'] = $entity->label();
    $row['usage'] = $current_usage . ' / ' . $usage_limit;
    $row['customer_limit'] = $customer_limit;
    $row['start_date'] = $entity->getStartDate()->format('M jS Y H:i:s');
    $row['end_date'] = $entity->getEndDate() ? $entity->getEndDate()->format('M jS Y H:i:s') : '—';
    if ($this->hasTableDrag && $entity->isEnabled()) {
      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $entity->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $entity->getWeight(),
        '#attributes' => ['class' => ['weight']],
      ];
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->formBuilder->getForm($this);
    $build['#attached']['library'][] = 'commerce_promotion/admin_list';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start by loading the enabled promotions.
    $this->enabledEntities = $this->load();
    if (count($this->enabledEntities) <= 1) {
      $this->hasTableDrag = FALSE;
    }
    $delta = 10;
    // Dynamically expand the allowed delta based on the number of entities.
    $count = count($this->enabledEntities);
    if ($count > 20) {
      $delta = ceil($count / 2);
    }

    $table_header = $this->buildHeader();
    $form['enabled_promotions'] = [
      '#type' => 'table',
      '#header' => $table_header,
      '#empty' => $this->t('There are no enabled @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#caption' => $this->t('Enabled'),
    ];

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $form['pager_enabled_promotions'] = [
        '#type' => 'pager',
        '#element' => 0,
      ];
    }

    // Now load the disabled promotions.
    $this->statusCondition = FALSE;
    $this->disabledEntities = $this->load();
    $form['disabled_promotions'] = [
      '#type' => 'table',
      // Table dragging is only enabled for enabled promotions, therefore,
      // removing the "weight" header if present.
      '#header' => array_diff_key($table_header, ['weight' => 'weight']),
      '#empty' => $this->t('There are no disabled @label.', ['@label' => $this->entityType->getPluralLabel()]),
      '#caption' => $this->t('Disabled'),
    ];

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $form['pager_disabled_promotions'] = [
        '#type' => 'pager',
        '#element' => 1,
      ];
    }

    $entities = array_merge($this->enabledEntities, $this->disabledEntities);
    foreach ($entities as $entity) {
      $row = $this->buildRow($entity);
      $row['name'] = ['#markup' => $row['name']];
      $row['usage'] = ['#markup' => $row['usage']];
      $row['customer_limit'] = ['#markup' => $row['customer_limit']];
      $row['start_date'] = ['#markup' => $row['start_date']];
      $row['end_date'] = ['#markup' => $row['end_date']];
      if (isset($row['weight'])) {
        $row['weight']['#delta'] = $delta;
      }
      if ($entity->isEnabled()) {
        $form['enabled_promotions'][$entity->id()] = $row;
      }
      else {
        $form['disabled_promotions'][$entity->id()] = $row;
      }
    }

    if ($this->hasTableDrag) {
      $form['enabled_promotions']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'weight',
      ];
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('enabled_promotions') as $id => $value) {
      if (isset($this->enabledEntities[$id]) && $this->enabledEntities[$id]->getWeight() != $value['weight']) {
        // Save entity only when its weight was changed.
        $this->enabledEntities[$id]->setWeight($value['weight']);
        $this->enabledEntities[$id]->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('update')) {
      $operations['coupons'] = [
        'title' => $this->t('Coupons'),
        'weight' => 20,
        'url' => new Url('entity.commerce_promotion_coupon.collection', [
          'commerce_promotion' => $entity->id(),
        ]),
      ];

      if (!$entity->isEnabled() && $entity->hasLinkTemplate('enable-form')) {
        $operations['enable'] = [
          'title' => $this->t('Enable'),
          'weight' => -10,
          'url' => $this->ensureDestination($entity->toUrl('enable-form')),
        ];
      }
      elseif ($entity->hasLinkTemplate('disable-form')) {
        $operations['disable'] = [
          'title' => $this->t('Disable'),
          'weight' => 40,
          'url' => $this->ensureDestination($entity->toUrl('disable-form')),
        ];
      }
    }

    return $operations;
  }

}
