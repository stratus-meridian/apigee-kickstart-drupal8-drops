<?php

namespace Drupal\Tests\file_link\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides kernel tests for 'file_link' field type.
 *
 * @group file_link
 */
class FileLinkValidationTest extends KernelTestBase {

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
   * Test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;
  
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['file_link_test']);
    $this->installEntitySchema('entity_test');
    $this->entity = EntityTest::create(['name' => 'Foo', 'type' => 'article']);

    // Disable redirect on validation.
    $settings = Settings::getAll();
    $settings['file_link.follow_redirect_on_validate'] = FALSE;
    new Settings($settings);
  }

  /**
   * Tests file_link field metadata storage with extension.
   */
  public function testWithExtension() {
    $this->entity->set('url_with_extension', ['uri' => static::getFullUrl('')]);
    $violations = $this->entity->get('url_with_extension')->validate();
    $this->assertSame(static::getViolationMessage('Provided file URL has no extension: @uri', ''), (string) $violations->get(0)->getMessage());

    $this->entity->set('url_with_extension', ['uri' => static::getFullUrl('/')]);
    $violations = $this->entity->get('url_with_extension')->validate();
    $this->assertSame(static::getViolationMessage('Provided file URL has no extension: @uri', '/'), (string) $violations->get(0)->getMessage());

    $this->entity->set('url_with_extension', ['uri' => static::getFullUrl('/foo')]);
    $violations = $this->entity->get('url_with_extension')->validate();
    $this->assertSame(static::getViolationMessage('Provided file URL has no extension: @uri', '/foo'), (string) $violations->get(0)->getMessage());

    $this->entity->set('url_with_extension', ['uri' => static::getFullUrl('/foo.pdf')]);
    $violations = $this->entity->get('url_with_extension')->validate();
    $this->assertSame(static::getViolationMessage('Provided file URL has no valid extension: @uri', '/foo.pdf'), (string) $violations->get(0)->getMessage());

    $this->entity->set('url_with_extension', ['uri' => static::getFullUrl('/foo.md')]);
    $violations = $this->entity->get('url_with_extension')->validate();
    $this->assertSame(0, $violations->count());
  }

  /**
   * Tests file_link field metadata storage without extension.
   */
  public function testWithoutExtension() {
    $this->entity->set('url_without_extension', ['uri' => static::getFullUrl('')]);
    $violations = $this->entity->get('url_without_extension')->validate();
    $this->assertSame(0, $violations->count());
  }

  /**
   * Provides a full URL given a path relative to file_link_test module.
   *
   * @param string $path
   *   A path relative to file_link_test module.
   *
   * @return string
   *   An absolute URL.
   */
  protected static function getFullUrl($path) {
    return Url::fromUri('base:/' . drupal_get_path('module', 'file_link_test') . $path, ['absolute' => TRUE])->toString();
  }

  /**
   * Provides the violation message for the URl returned by ::getFullUrl().
   *
   * @param $message
   *   Error message.
   * @param string $path
   *   A path relative to file_link_test module.
   *
   * @return string
   *   The translated violation message.
   */
  protected static function getViolationMessage($message, $path) {
    return (new TranslatableMarkup($message, ['@uri' => static::getFullUrl($path)]))->__toString();
  }

}
