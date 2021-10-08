<?php

namespace Drupal\Tests\file_link\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\file_link_test\HttpMiddleware;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides kernel tests for 'file_link' field type.
 *
 * @group file_link
 */
class FileLinkMultiValueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file_link',
    'file_link_test',
    'entity_test',
    'link',
    'field',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['file_link_test']);
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests file_link field metadata storage with extension.
   */
  public function testMultiValue() {
    $settings = Settings::getAll();
    // Set up the fixtures.
    $settings['file_link_test_middleware'] = [
      'http://file_link.drupal/fancy-file-1.txt' => [
        'status' => 200,
        'headers' => ['Content-Type' => 'text/plain', 'Content-Length' => 27],
      ],
      'http://file_link.drupal/fancy-file-2.txt' => [
        'status' => 200,
        'headers' => ['Content-Type' => 'text/plain', 'Content-Length' => 42],
      ],
      'http://file_link.drupal/fancy-file-3.txt' => [
        'status' => 200,
        'headers' => ['Content-Type' => 'text/plain', 'Content-Length' => 80],
      ],
    ];

    new Settings($settings);

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create(['name' => 'Foo', 'type' => 'article']);

    $entity->get('multivalue_url')->set(0, ['uri' => 'http://file_link.drupal/fancy-file-1.txt']);
    $entity->get('multivalue_url')->set(1, ['uri' => 'http://file_link.drupal/fancy-file-2.txt']);
    $entity->get('multivalue_url')->set(2, ['uri' => 'http://file_link.drupal/fancy-file-1.txt']);

    $entity->save();

    static::assertEquals('text/plain', $entity->get('multivalue_url')->get(0)->getFormat());
    static::assertEquals(27, $entity->get('multivalue_url')->get(0)->getSize());
    static::assertEquals('text/plain', $entity->get('multivalue_url')->get(1)->getFormat());
    static::assertEquals(42, $entity->get('multivalue_url')->get(1)->getSize());
    static::assertEquals('text/plain', $entity->get('multivalue_url')->get(2)->getFormat());
    static::assertEquals(27, $entity->get('multivalue_url')->get(2)->getSize());

    static::assertEquals(2, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-1.txt'));
    static::assertEquals(1, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-2.txt'));
    static::assertEquals(0, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-3.txt'));

    // Save the entity without touching anything.
    $entity->save();

    static::assertEquals(2, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-1.txt'));
    static::assertEquals(1, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-2.txt'));
    static::assertEquals(0, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-3.txt'));

    // Update one of the items and add a new one.
    $entity->get('multivalue_url')->set(1, ['uri' => 'http://file_link.drupal/fancy-file-3.txt']);
    $entity->get('multivalue_url')->set(3, ['uri' => 'http://file_link.drupal/fancy-file-3.txt']);
    $entity->save();

    // Check that all the metadata is correct.
    static::assertEquals('text/plain', $entity->get('multivalue_url')->get(0)->getFormat());
    static::assertEquals(27, $entity->get('multivalue_url')->get(0)->getSize());
    static::assertEquals('text/plain', $entity->get('multivalue_url')->get(1)->getFormat());
    static::assertEquals(80, $entity->get('multivalue_url')->get(1)->getSize());
    static::assertEquals('text/plain', $entity->get('multivalue_url')->get(2)->getFormat());
    static::assertEquals(27, $entity->get('multivalue_url')->get(2)->getSize());
    static::assertEquals('text/plain', $entity->get('multivalue_url')->get(3)->getFormat());
    static::assertEquals(80, $entity->get('multivalue_url')->get(3)->getSize());

    // Check how often the urls are called.
    static::assertEquals(2, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-1.txt'));
    static::assertEquals(1, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-2.txt'));
    static::assertEquals(2, HttpMiddleware::getRequestCount('http://file_link.drupal/fancy-file-3.txt'));
  }

}
