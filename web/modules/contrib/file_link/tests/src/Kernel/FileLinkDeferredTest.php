<?php

namespace Drupal\Tests\file_link\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\file_link\Plugin\Field\FieldType\FileLinkItem;
use Drupal\file_link_test\HttpMiddleware;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides kernel tests for 'file_link' field type.
 *
 * @group file_link
 */
class FileLinkDeferredTest extends KernelTestBase {

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
  public function testDeferredCheck() {
    $settings = Settings::getAll();
    // Set up the fixtures.
    $settings['file_link_test_middleware'] = [
      'http://file_link.drupal/latentcy-test-file1.txt' => [
        'status' => 200,
        'headers' => ['Content-Type' => 'text/plain', 'Content-Length' => 27],
      ],
      'http://file_link.drupal/latentcy-test-file2.txt' => [
        'status' => 200,
        'headers' => ['Content-Type' => 'text/plain', 'Content-Length' => 27],
      ],
    ];
    new Settings($settings);

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create(['name' => 'Foo', 'type' => 'article']);

    $entity->get('deferred_url')->set(0, ['uri' => 'http://file_link.drupal/latentcy-test-file1.txt']);
    $entity->get('deferred_url')->set(1, ['uri' => 'http://file_link.drupal/latentcy-test-file2.txt']);

    $entity->save();

    static::assertEquals(1, file_link_test_entity_save_counter($entity));

    static::assertEquals(NULL, $entity->get('deferred_url')->get(0)->getFormat());
    static::assertEquals(0, $entity->get('deferred_url')->get(0)->getSize());

    static::assertEquals(0, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file1.txt'));
    static::assertEquals(0, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file2.txt'));

    $this->container->get('cron')->run();

    static::assertEquals(1, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file1.txt'));
    static::assertEquals(1, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file2.txt'));

    static::assertEquals(2, file_link_test_entity_save_counter($entity));

    $entity = EntityTest::load($entity->id());
    static::assertEquals('text/plain', $entity->get('deferred_url')->get(0)->getFormat());
    static::assertEquals(27, $entity->get('deferred_url')->get(0)->getSize());

    // Simulate a new request by resetting the static cache on FileLinkItem.
    $queued = new \ReflectionProperty(FileLinkItem::class, 'queued');
    $queued->setAccessible(TRUE);
    $queued->setValue(NULL, []);

    // Update the entity by updating and adding values to the the field.
    $entity->get('deferred_url')->set(1, ['uri' => 'http://file_link.drupal/latentcy-test-file1.txt']);
    $entity->get('deferred_url')->set(2, ['uri' => 'http://file_link.drupal/latentcy-test-file2.txt']);

    $entity->save();
    static::assertEquals(3, file_link_test_entity_save_counter($entity));

    $entity = EntityTest::load($entity->id());
    static::assertEquals('text/plain', $entity->get('deferred_url')->get(0)->getFormat());
    static::assertEquals(27, $entity->get('deferred_url')->get(0)->getSize());
    static::assertEquals(NULL, $entity->get('deferred_url')->get(1)->getFormat());
    static::assertEquals(0, $entity->get('deferred_url')->get(1)->getSize());

    $this->container->get('cron')->run();

    static::assertEquals(2, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file1.txt'));
    static::assertEquals(2, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file2.txt'));

    // Make sure the entity was only saved once more and all fields are set.
    static::assertEquals(4, file_link_test_entity_save_counter($entity));
    static::assertEquals('text/plain', $entity->get('deferred_url')->get(0)->getFormat());
    static::assertEquals(27, $entity->get('deferred_url')->get(0)->getSize());
    static::assertEquals('text/plain', $entity->get('deferred_url')->get(1)->getFormat());
    static::assertEquals(27, $entity->get('deferred_url')->get(1)->getSize());
    static::assertEquals('text/plain', $entity->get('deferred_url')->get(2)->getFormat());
    static::assertEquals(27, $entity->get('deferred_url')->get(2)->getSize());
  }

  /**
   * Tests file_link field metadata storage with extension.
   */
  public function testDeletedEntity() {
    $settings = Settings::getAll();
    // Set up the fixtures.
    $settings['file_link_test_middleware'] = [
      'http://file_link.drupal/latentcy-test-file.txt' => [
        'status' => 200,
        'headers' => ['Content-Type' => 'text/plain', 'Content-Length' => 27],
      ],
    ];
    new Settings($settings);

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create(['name' => 'Foo', 'type' => 'article']);

    $entity->get('deferred_url')->set(0, ['uri' => 'http://file_link.drupal/latentcy-test-file.txt']);

    $entity->save();

    static::assertEquals(1, file_link_test_entity_save_counter($entity));

    static::assertEquals(NULL, $entity->get('deferred_url')->get(0)->getFormat());
    static::assertEquals(0, $entity->get('deferred_url')->get(0)->getSize());

    static::assertEquals(0, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file.txt'));

    $entity->delete();

    $this->container->get('cron')->run();

    static::assertEquals(0, HttpMiddleware::getRequestCount('http://file_link.drupal/latentcy-test-file.txt'));
  }

}
