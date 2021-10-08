<?php

namespace Drupal\Tests\file_link\Functional;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the redirect functionality of the 'file_link' field type.
 *
 * @group file_link
 */
class FileLinkRedirectTest extends BrowserTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * Tests redirects.
   *
   * @param string $path
   *    Path to request.
   * @param int $size
   *    Expected file size.
   * @param $format
   *    Expected file format.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *
   * @dataProvider redirectDataProvider
   */
  public function testRedirects($path, $size, $format) {
    $entity = EntityTest::create(['name' => 'Foo', 'type' => 'article']);
    $entity->set('url_without_extension', [
      'uri' => Url::fromUri('base:' . $path, ['absolute' => TRUE])->toString(),
    ]);
    $entity->save();

    /** @var \Drupal\file_link\Plugin\Field\FieldType\FileLinkItem $file_link */
    $file_link = $entity->get('url_without_extension')->first();
    $this->assertEquals($size, $file_link->getSize());
    $this->assertEquals($format, $file_link->getFormat());
  }

  /**
   * Test not valid redirect.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testNotValidRedirect() {
    $entity = EntityTest::create(['name' => 'Foo', 'type' => 'article']);
    $entity->set('url_without_extension', [
      'uri' => Url::fromUri('base:/test/redirect/301/rst', ['absolute' => TRUE])->toString(),
    ]);

    $violations = $entity->get('url_without_extension')->validate();
    $this->assertSame(1, $violations->count());
  }

  /**
   * Data provider.
   *
   * @return array
   *    Redirect test data and expectations.
   */
  public function redirectDataProvider() {
    return [
      ['/test/redirect/301/md', 3, 'application/octet-stream'],
      ['/test/redirect/302/md', 3, 'application/octet-stream'],
    ];
  }
}
