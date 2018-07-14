<?php

namespace Drupal\automated_crop\Plugin\AutomatedCrop;

use Drupal\automated_crop\AbstractAutomatedCrop;

/**
 * Class Generic routing entity mapper.
 *
 * @AutomatedCrop(
 *   id = "automated_crop_default",
 *   label = @Translation("Automated crop"),
 *   description = @Translation("The default strategy for automatic crop."),
 * )
 */
final class AutomatedCropDefault extends AbstractAutomatedCrop {

  /**
   * {@inheritdoc}
   */
  public function calculateCropBoxCoordinates() {
    $this->cropBox['x'] = ($this->originalImageSizes['width'] / 2) - ($this->cropBox['width'] / 2);
    $this->cropBox['y'] = ($this->originalImageSizes['height'] / 2) - ($this->cropBox['height'] / 2);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateCropBoxSize() {
    if (!$this->hasSizes() && !$this->hasHardSizes()) {
      $this->automatedCropBoxCalculation();
    }

    if ('width' === $this->findUnknownValue()) {
      $this->calculateUnknownValue($this->cropBox['width']);
    }

    if ('height' === $this->findUnknownValue()) {
      $this->calculateUnknownValue($this->cropBox['height']);
    }

    // Initialize auto crop area & unsure we can't exceed original image sizes.
    $width = min(max($this->cropBox['width'], $this->cropBox['min_width']), $this->cropBox['max_width']);
    $height = min(max($this->cropBox['height'], $this->cropBox['min_height']), $this->cropBox['max_height']);
    $this->setCropBoxSize($width, $height);

    return $this;
  }

  /**
   * Calculate size automatically based on origin image width.
   *
   * This method admit you want to crop the height of your image in another,
   * ratio with respect of original image homothety. If you not define any,
   * ratio in plugin configuration, nothing happen. If you define a new ratio,
   * your image will conserve his original width but the height will,
   * calculated to respect plugin ratio given.
   *
   * This method contains a system that avoids exceeding,
   * the maximum sizes of the cropBox. Pay attention with the,
   * configurations of max width/height.
   */
  protected function automatedCropBoxCalculation() {
    $delta = $this->getDelta();
    $width = $this->originalImageSizes['width'];
    $height = round(($width * $delta));

    if (!empty($this->cropBox['max_height']) && $height > $this->cropBox['max_height']) {
      $height = $this->cropBox['max_height'];
      $width = round(($height * $delta));
    }

    if (!empty($this->cropBox['max_width']) && $width > $this->cropBox['max_width']) {
      $width = $this->cropBox['max_width'];
      $height = round(($width * $delta));
    }

    $this->cropBox['width'] = $width;
    $this->cropBox['height'] = $height;
  }

  /**
   * Evaluate if with or height need to be calculated.
   *
   * If we have already ALL cropBox sizes we just need to apply,
   * it don't need to evaluate missing values.
   *
   * @return bool|string
   *   The value to find or False if cropBox have any sizes found.
   */
  protected function findUnknownValue() {
    if (!$this->hasSizes()) {
      return FALSE;
    }

    $valueToSearch = 'width';
    if (!empty($this->cropBox['width']) && empty($this->cropBox['height'])) {
      $valueToSearch = 'height';
    }

    return $valueToSearch;
  }

  /**
   * Calculate the new value of given width or height respecting homothety.
   *
   * @param int $value
   *   Value to convert with image delta to found compatible new sizes.
   *
   * @return int
   *   The new height or width respect the homothety of image.
   */
  protected function calculateUnknownValue($value) {
    return round(($value * $this->delta));
  }

}
