<?php

namespace Drupal\Tests\commerce_log\Kernel;

use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests integration with checkout events.
 *
 * @group commerce
 */
class CheckoutIntegrationTest extends OrderKernelTestBase {

  /**
   * The log storage.
   *
   * @var \Drupal\commerce_log\LogStorageInterface
   */
  protected $logStorage;

  /**
   * The log view builder.
   *
   * @var \Drupal\commerce_log\LogViewBuilder
   */
  protected $logViewBuilder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_log',
    'commerce_checkout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_log');
    $this->logStorage = $this->container->get('entity_type.manager')->getStorage('commerce_log');
    $this->logViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_log');
  }

  /**
   * Tests that a log is generated when an order is placed.
   */
  public function testCheckoutCompletion() {
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'completed',
    ]);
    $order->save();
    $this->container->get('event_dispatcher')->dispatch(CheckoutEvents::COMPLETION, new OrderEvent($order));

    $logs = $this->logStorage->loadMultipleByEntity($order);
    $this->assertEquals(1, count($logs));
    $log = end($logs);
    $build = $this->logViewBuilder->view($log);
    $this->render($build);

    $this->assertText("Customer completed checkout for this order.");
  }

}
