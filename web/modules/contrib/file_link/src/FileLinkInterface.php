<?php

namespace Drupal\file_link;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides an interface for File Link field.
 */
interface FileLinkInterface {

  /**
   * Get raw file size.
   *
   * @return int
   *   File size in bytes.
   */
  public function getSize();

  /**
   * Get file format.
   *
   * @return string
   *   File format.
   */
  public function getFormat();

  /**
   * Sets the latest HTTP response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The last response to be stored.
   *
   * @return $this
   */
  public function setResponse(ResponseInterface $response);

  /**
   * Gets the latest stored HTTP response.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   A response object.
   */
  public function getResponse();

  /**
   * Clears a previous stored HTTP response.
   *
   * @return $this
   */
  public function clearResponse();

  /**
   * Sets the exception throw by the last HTTP client request.
   *
   * @param \GuzzleHttp\Exception\RequestException $exception
   *   The last Guzzle request exception.
   *
   * @return $this
   */
  public function setException(RequestException $exception);

  /**
   * Gets the last Guzzle client exception.
   *
   * @return \GuzzleHttp\Exception\RequestException
   *   The last Guzzle client exception.
   */
  public function getException();

  /**
   * Clears a previous stored Guzzle exception.
   *
   * @return $this
   */
  public function clearException();

}
