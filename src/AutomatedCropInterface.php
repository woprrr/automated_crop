<?php

namespace Drupal\automated_crop;


/**
 * Provides an interface defining the AutomatedCrop factory object.
 */
interface AutomatedCropInterface {

  public function getAspectRatio();
  public function getAnchor();
  public function getCropArea();

}
