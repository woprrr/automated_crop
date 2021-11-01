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
    $this->automatedCropBoxCalculation();
    
    $width = $this->cropBox['width'];
    $height = $this->cropBox['height'];

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
    // $delta = $this->getDelta();
    $width = $this->originalImageSizes['width'];
    $height = $this->originalImageSizes['height'];
    $aspectWidth = $width;
    $aspectHeight = $height;

    if (!empty($this->cropBox['aspect_ratio'])) {
      $measures = explode(':', $this->cropBox['aspect_ratio']);
      $aspectWidth = $measures[0];
      $aspectHeight = $measures[1];
    }

    $aspectRatio = $aspectWidth / $aspectHeight;

    // Vertical.
    if ($aspectRatio < 1) {
      $newWidth = min($height * $aspectRatio, $width);
      $newHeight = $newWidth / $aspectRatio;

    // Horizontal.
    } else {
      $newHeight = min($width / $aspectRatio, $height);
      $newWidth = $newHeight * $aspectRatio;
  
    }

    $this->cropBox['width'] = $newWidth;
    $this->cropBox['height'] = $newHeight;
  }

}
