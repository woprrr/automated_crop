<?php

namespace Drupal\automated_crop;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface defining the AutomatedCrop factory object.
 */
interface AutomatedCropInterface extends PluginInspectionInterface, ConfigurablePluginInterface
{

  /**
   * Returns the display label.
   *
   * @return string
   *   The display label.
   */
  public function label();

  /**
   * Get the aspect ratio expected by configuration.
   *
   * @return string
   *   The aspect ratio target by configuration.
   */
  public function getAspectRatio();

  /**
   * Get the aspect ratio expected by configuration.
   *
   * @return string
   *   The aspect ratio target by configuration.
   */
  public function setAspectRatio();

  /**
   * Gets crop anchor (top-left corner of crop area).
   *
   * @return array
   *   Array with two keys (x, y) and anchor coordinates as values.
   */
  public function anchor();

  /**
   * Gets crop anchor (top-left corner of crop area).
   *
   * @return array
   *   Array with two keys (x, y) and anchor coordinates as values.
   */
  public function setAnchor(array $coordinates);

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function size();

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function setCropBoxSize($width, $height);

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function setOriginalSize();

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function getOriginalSize();

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function setImage($image);

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function getImage();

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function setMaxSizes($maxWidth, $maxHeight);
}
