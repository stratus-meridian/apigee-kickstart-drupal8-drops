<?php

namespace Drupal\Tests\commerce_promotion\Kernel\Entity;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests the Coupon entity.
 *
 * @coversDefaultClass \Drupal\commerce_promotion\Entity\Coupon
 *
 * @group commerce
 */
class CouponTest extends OrderKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('commerce_promotion');
    $this->installEntitySchema('commerce_promotion_coupon');
    $this->installSchema('commerce_promotion', ['commerce_promotion_usage']);
    $this->installConfig(['commerce_promotion']);
  }

  /**
   * @covers ::getPromotion
   * @covers ::getPromotionId
   * @covers ::getCode
   * @covers ::setCode
   * @covers ::getCreatedTime
   * @covers ::setCreatedTime
   * @covers ::getUsageLimit
   * @covers ::setUsageLimit
   * @covers ::getCustomerUsageLimit
   * @covers ::setCustomerUsageLimit
   * @covers ::isEnabled
   * @covers ::setEnabled
   */
  public function testCoupon() {
    $promotion = Promotion::create([
      'status' => FALSE,
    ]);
    $promotion->save();
    $promotion = $this->reloadEntity($promotion);

    $coupon = Coupon::create([
      'status' => FALSE,
      'promotion_id' => $promotion->id(),
    ]);

    $this->assertEquals($promotion, $coupon->getPromotion());
    $this->assertEquals($promotion->id(), $coupon->getPromotionId());

    $coupon->setCode('test_code');
    $this->assertEquals('test_code', $coupon->getCode());

    $coupon->setCreatedTime(635879700);
    $this->assertEquals(635879700, $coupon->getCreatedTime());

    $coupon->setUsageLimit(10);
    $this->assertEquals(10, $coupon->getUsageLimit());

    $coupon->setCustomerUsageLimit(1);
    $this->assertEquals(1, $coupon->getCustomerUsageLimit());

    $coupon->setEnabled(TRUE);
    $this->assertEquals(TRUE, $coupon->isEnabled());
  }

  /**
   * @covers ::available
   */
  public function testAvailability() {
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'test@example.com',
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'store_id' => $this->store,
      'uid' => $this->createUser(),
      'order_items' => [$order_item],
    ]);
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->save();

    $promotion = Promotion::create([
      'order_types' => ['default'],
      'stores' => [$this->store->id()],
      'usage_limit' => 1,
      'usage_limit_customer' => 1,
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $promotion->save();

    /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
    $coupon = Coupon::create([
      'promotion_id' => $promotion->id(),
      'code' => 'coupon_code',
      'status' => TRUE,
    ]);
    $coupon->save();
    $this->assertTrue($coupon->available($order));

    $coupon->setEnabled(FALSE);
    $this->assertFalse($coupon->available($order));
    $coupon->setEnabled(TRUE);

    $this->container->get('commerce_promotion.usage')->register($order, $promotion, $coupon);
    // Test that the promotion usage is checked at the coupon level.
    $this->assertFalse($coupon->available($order));

    $promotion->setUsageLimit(0);
    $promotion->setCustomerUsageLimit(0);
    $promotion->save();
    $promotion = $this->reloadEntity($promotion);
    $this->assertTrue($coupon->available($order));

    // Test the global coupon usage limit.
    $coupon->setUsageLimit(1);
    $this->assertFalse($coupon->available($order));

    // Test limit coupon usage by customer.
    $coupon->setCustomerUsageLimit(1);
    $coupon->setUsageLimit(0);
    $coupon->save();
    $coupon = $this->reloadEntity($coupon);
    $this->assertFalse($coupon->available($order));

    $order->setEmail('another@example.com');
    $order->setRefreshState(Order::REFRESH_SKIP);
    $order->save();
    $order = $this->reloadEntity($order);
    $this->assertTrue($coupon->available($order));

    \Drupal::service('commerce_promotion.usage')->register($order, $promotion, $coupon);
    $this->assertFalse($coupon->available($order));
  }

}
