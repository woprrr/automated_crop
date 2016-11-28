<?php

namespace Drupal\automated_crop;

/**
 * Provides an interface defining the AutomatedCrop factory object.
 */
interface AutomatedCropInterface {

  /**
   * Get the aspect ratio expected by configuration.
   *
   * @return string
   *   The aspect ratio target by configuration.
   */
  public function getAspectRatio();

  /**
   * Gets crop anchor (top-left corner of crop area).
   *
   * @return array
   *   Array with two keys (x, y) and anchor coordinates as values.
   */
  public function getAnchor();

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function getCropBoxSizes();

}
