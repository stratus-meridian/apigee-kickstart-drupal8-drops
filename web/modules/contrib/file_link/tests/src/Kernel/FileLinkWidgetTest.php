<?php

namespace Drupal\Tests\file_link\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the "file_link" widget
 *
 * @group file_link
 */
class FileLinkWidgetTest extends KernelTestBase {

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

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig(['file_link_test']);
  }

  /**
   * Tests the widget with the locked languages.
   */
  public function testFileLinkWidget() {
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
    $entityFormDisplay = $storage->create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'article',
      'mode' => 'default',
      'status' => TRUE,
    ]);

    $entityFormDisplay->setComponent('url_without_extension', [
      'type' => 'file_link_default',
      'settings' => [
        'placeholder_url' => 'http://example.com',
        'placeholder_title' => 'The placeholder',
      ],
    ])->save();

    $entity = EntityTest::create([
      'name' => $this->randomString(),
      'type' => 'article',
      'url_without_extension' => [
        'uri' => 'http://example.com/file.png',
        'format' => 'image/png',
        'size' => 1000,
      ],
    ]);
    $form = $this->container->get('entity.form_builder')->getForm($entity);
    $widget = $form['url_without_extension']['widget'][0];
    $this->assertEquals('url', $widget['uri']['#type']);
    $this->assertFalse(isset($widget['uri']['#target_type']));
    $this->assertFalse(isset($widget['uri']['#attributes']['data-autocomplete-first-character-blacklist']));
    $this->assertFalse(isset($widget['uri']['#process_default_value']));
    $this->assertEquals(1000, $widget['size']['#value']);
    $this->assertEquals('image/png', $widget['format']['#value']);
  }

}
