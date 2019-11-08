<?php

namespace Drupal\file_link\Plugin\Validation\Constraint;

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file_link\Plugin\Field\FieldType\FileLinkItem;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;

/**
 * Validation constraint for file_link, checking that URI points to a file.
 *
 * @Constraint(
 *   id = "LinkToFile",
 *   label = @Translation("Checks that URI links to a file.", context = "Validation"),
 * )
 */
class LinkToFileConstraint extends Constraint implements ConstraintValidatorInterface {

  /**
   * Validation execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($link, Constraint $constraint) {
    /** @var \Drupal\file_link\Plugin\Field\FieldType\FileLinkItem $link */
    if ($link->isEmpty()) {
      return;
    }

    $is_valid = TRUE;
    $uri = $link->get('uri')->getValue();

    // Try to resolve the given URI to a URL. It may fail if it's schemeless.
    try {
      $url = Url::fromUri($uri, ['absolute' => TRUE])->toString();
    }
    catch (\InvalidArgumentException $e) {
      $this->context->addViolation("The following error occurred while getting the link URL: @error", ['@error' => $e->getMessage()]);
      $is_valid = FALSE;
    }

    if ($is_valid) {
      // If URL has no path but it still needs an extension then it's not valid.
      if (!$this->hasPath($url) && $this->needsExtension($link)) {
        $this->context->addViolation("Provided file URL has no path nor extension: @uri", ['@uri' => $uri]);
        $is_valid = FALSE;
      }

      if ($is_valid) {
        // Check for redirect response and get effective URL if any.
        $url = $this->getEffectiveUrl($url);

        if ($this->needsExtension($link) && !$this->hasExtension($url)) {
          $this->context->addViolation("Provided file URL has no extension: @uri", ['@uri' => $uri]);
          $is_valid = FALSE;
        }

        if ($is_valid && $this->hasExtension($url)) {
          $is_valid = $this->hasValidExtension($url, $link);
        }
      }
    }

    // If not valid construct error message.
    if (!$is_valid) {
      $this->context->addViolation("Provided file URL has no valid extension: @uri", ['@uri' => $uri]);
    }
  }

  /**
   * Check whereas given URL has a path.
   *
   * @param string $url
   *   URL.
   *
   * @return bool
   *   Whereas given URL has a path.
   */
  protected function hasPath($url) {
    return !empty($this->getPath($url));
  }

  /**
   * Get URL path.
   *
   * @param string $url
   *   URL.
   *
   * @return string
   *   URL path.
   */
  protected function getPath($url) {
    return trim((string) parse_url($url, PHP_URL_PATH), '/');
  }

  /**
   * Check whereas given URL has an extension.
   *
   * @param string $url
   *   URL.
   *
   * @return bool
   *   Whereas given URL has an extension.
   */
  protected function hasExtension($url) {
    return !empty(pathinfo($this->getPath($url), PATHINFO_EXTENSION));
  }

  /**
   * Check whereas given link field needs an extension.
   *
   * @param \Drupal\file_link\Plugin\Field\FieldType\FileLinkItem $link
   *   Link item.
   *
   * @return bool
   *   Whereas link item needs an extension.
   */
  protected function needsExtension(FileLinkItem $link) {
    return !$link->getFieldDefinition()->getSetting('no_extension');
  }

  /**
   * Check whereas basename has a valid extension.
   *
   * @param string $basename
   *   URL path basename.
   * @param \Drupal\file_link\Plugin\Field\FieldType\FileLinkItem $link
   *   Link item.
   *
   * @return bool
   *   Whereas basename has a valid extension.
   */
  protected function hasValidExtension($basename, FileLinkItem $link) {
    $extensions = trim($link->getFieldDefinition()->getSetting('file_extensions'));
    if (!empty($extensions)) {
      $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($extensions)) . ')$/i';
      return (bool) preg_match($regex, $basename) !== FALSE;
    }
    return TRUE;
  }

  /**
   * Get effective URL by following redirects, if any.
   *
   * @param string $url
   *   Original URL.
   *
   * @return string
   *   Effective URL.
   */
  protected function getEffectiveUrl($url) {

    // Skip performing HTTP requests, useful when running bulk imports.
    if (Settings::get('file_link.disable_http_requests', FALSE) || !Settings::get('file_link.follow_redirect_on_validate', TRUE)) {
      return $url;
    }

    // Setup HTTP client to follow redirect and perform an HEAD request.
    $options = [
      'exceptions' => TRUE,
      'connect_timeout' => TRUE,
      'allow_redirects' => [
        'strict' => TRUE,
        'on_redirect' => function (Request $request, Response $response, Uri $uri) use (&$url) {
          $url = (string) $uri;
        },
      ],
    ];

    try {
      // Perform HEAD request to get actual URL, as in: after the redirect.
      \Drupal::httpClient()->head($url, $options);
    }
    catch (RequestException $e) {
      // Don't fail validation if connection has timed out or URL was not found.
    }

    return $url;
  }

}
