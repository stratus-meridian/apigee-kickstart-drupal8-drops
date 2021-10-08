<?php

namespace Drupal\file_link_test;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * A middleware for guzzle to test requests.
 */
class HttpMiddleware {

  public static $recorder = [];

  public static function getRequestCount($key) {
    if (!isset(static::$recorder[$key])) {
      static::$recorder[$key] = 0;
    }

    return static::$recorder[$key];
  }

  /**
   * Invoked method that returns a promise.
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        $uri = $request->getUri();
        $settings = Settings::get('file_link_test_middleware', []);
        // Check if the request is made to one of our fixtures.
        $key = $uri->getScheme() . '://' . $uri->getHost() . $uri->getPath();

        if (array_key_exists($key, $settings)) {
          if (!isset(static::$recorder[$key])) {
            static::$recorder[$key] = 0;
          }
          static::$recorder[$key]++;

          return $this->createPromise($request, $settings[$key]);
        }

        // Otherwise, no intervention. We defer to the handler stack.
        return $handler($request, $options);
      };
    };
  }

  /**
   * Creates a promise for the file_link fixture request.
   *
   * @param RequestInterface $request
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   */
  protected function createPromise(RequestInterface $request, $fixture) {
    // Create a response from the fixture.
    $response = new Response($fixture['status'] ?? 200, $fixture['headers'] ?? [], $fixture['body'] ?? NULL);
    return new FulfilledPromise($response);
  }

}
