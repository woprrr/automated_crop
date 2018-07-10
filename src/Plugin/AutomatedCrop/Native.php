<?php

namespace Drupal\automated_crop\Plugin\AutomatedCrop;

use Drupal\automated_crop\AbstractAutomatedCrop;
use Drupal\automated_crop\Annotation\AutomatedCrop;

/**
 * Class Generic routing entity mapper.
 *
 * @AutomatedCrop(
 *   id = "native",
 *   label = @Translation("Automated crop Native."),
 *   description = @Translation("Super description..."),
 * )
 */
class Native extends AbstractAutomatedCrop
{

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
    // By default we use Hard sizes.
    $width = $this->cropBox['width'];
    $height = $this->cropBox['height'];

    $ratio = explode(':', $this->getAspectRatio());
    $delta = $ratio['1'] / $ratio['0'];

    if (!$this->hasSizes() && !$this->hasHardSizes()) {
      $width = $this->originalImageSizes['width'];
      $height = round(($width * $delta));
      // If the calculated height exceeds the limit of the image we need,
      // to crop width instead of height.
      if (!empty($this->cropBox['max_height']) && $height > $this->cropBox['max_height']) {
        $height = $this->cropBox['max_height'];
        $width = round(($height * $delta));
      }

      if (!empty($this->cropBox['max_width']) && $width > $this->cropBox['max_width']) {
        $width = $this->cropBox['max_width'];
        $height = round(($width * $delta));
      }
    }
    elseif ($this->hasSizes() && !empty($width)) {
      $height = round(($width * $delta));
    }
    elseif ($this->hasSizes() && !empty($height)) {
      $width = round(($height * $delta));
    }

    // Initialize auto crop area & unsure we can't exceed original image sizes.
    $width = min(max($width, $this->cropBox['min_width']), $this->cropBox['max_width']);
    $height = min(max($height, $this->cropBox['min_height']), $this->cropBox['max_height']);
    $this->setCropBoxSize($width, $height);

    return $this;
  }
}
