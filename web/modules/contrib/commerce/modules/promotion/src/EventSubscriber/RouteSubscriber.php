<?php

namespace Drupal\commerce_promotion\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route subscriber that adding the _admin_route option
 * to the routes like "promotion/%/coupons".
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    // Ensure to run after the Views route subscriber.
    // @see \Drupal\views\EventSubscriber\RouteSubscriber.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.commerce_promotion_coupon.collection');
    if ($route) {
      $route->setOption('_admin_route', TRUE);
      $route->setOption('parameters', [
        'commerce_promotion' => [
          'type' => 'entity:commerce_promotion',
        ],
      ]);
    }
  }

}
