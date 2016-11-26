<?php

namespace Drupal\automated_crop;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageInterface;

/**
 * AutomatedCropFactory class.
 */
class AutomatedCropFactory implements AutomatedCropInterface{

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  protected $cropBox = [
    'width' => NULL,
    'height' => NULL,
    'min_width' => NULL,
    'min_height' => NULL
  ];

  protected $originalImageSizes;

  protected $aspectRatio = 'NaN';

  protected $image;

  protected $autoCropArea = 1;
  /**
   * Constructs a ImageWidgetCropManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Initialize needed crop box onto image.
   *
   * @param ImageInterface $image
   *   Image object.
   * @param array $sizes
   *   Size of crop area provide by effect configuration.
   * @param string $aspect_ratio
   *   Enforce or not ('NaN') the original aspect ratio of image.
   * @param int $autoCropArea
   *   Define the percentage of automatic cropping area when initializes.
   *
   * @return
   *   Crop coordinates to applie onto image.
   */
  public function initCropBox(ImageInterface $image, array $sizes, $aspect_ratio = 'NaN') {
    $this->setImageToCrop($image);
    $this->setOriginalImageSizes();
    $this->setCropBoxSizes($sizes);
    $this->setAspectRatio($aspect_ratio);
    return $this;
  }

  protected function setImageToCrop(ImageInterface $image) {
    return $this->image = $image;
  }

  protected function setAutoCropArea($num) {
    $this->autoCropArea = $num;
    return $this;
  }

  protected function setOriginalImageSizes() {
    $this->originalImageSizes['width'] = (int) $this->image->getWidth();
    $this->originalImageSizes['height'] = (int) $this->image->getHeight();
    return $this;
  }

  protected function setCropBoxSizes(array $sizes) {
    foreach ($sizes as $element => $value) {
      // @TODO need to fire an exception if element are not available.
        $this->cropBox["$element"] = (int) $value;
    }
    return $this;
  }

  /**
   * Calculate aspect ratio of CropBox area or define new.
   *
   * @param string|NULL $aspect_ratio
   *   The enforced aspect ratio to applie on CropBox or "NaN" to save original cropBox ratio.
   *
   * @return integer|null
   *  Aspect ratio calculated to apply on area sizes.
   */
  protected function setAspectRatio($aspect_ratio) {
    if ($aspect_ratio != 'NaN' && (empty($this->originalImageSizes['width']) && empty($this->originalImageSizes['height']))) {
      $aspect_option = explode(':', $aspect_ratio);
      if (!empty($aspect_option) && (is_int($aspect_option['0']) && is_int($aspect_option['1']))) {
        $this->aspectRatio = (int) $aspect_option['0'] / (int) $aspect_option['1'];
      }
    }

    $this->aspectRatio = $this->originalImageSizes['width'] / $this->originalImageSizes['height'];
  }

  public function getCropArea() {
    $aspect_ratio = $this->aspectRatio;
    if ($aspect_ratio) {
      if ($this->originalImageSizes['height'] * $aspect_ratio > $this->originalImageSizes['width']) {
        $this->cropBox['height'] = $this->cropBox['width'] / $aspect_ratio;
      }
      else {
        $this->cropBox['width'] = $this->cropBox['height'] * $aspect_ratio;
      }
    }

    // Initialize auto crop area & unsure we can't override original image sizes.
    // @TODO we can change $original_w by max_w by example if we need...
    $crop_box['width'] = min(max($this->cropBox['width'], $this->cropBox['min_width']), $this->originalImageSizes['width']);
    $crop_box['height'] = min(max($this->cropBox['height'], $this->cropBox['min_height']), $this->originalImageSizes['height']);

    // The width & height of auto crop area must large than min sizes.
    return [
      'height' => round(max($this->cropBox['min_width'], $this->cropBox['width'] * $this->autoCropArea)),
      'width' => round(max($this->cropBox['min_height'], $this->cropBox['height'] * $this->autoCropArea))
    ];
  }

  public function getAspectRatio() {
    return $this->aspectRatio;
  }

  public function getAnchor() {
    return [
      'x' => ($this->originalImageSizes['width'] / 2) - ($this->cropBox['width'] / 2),
      'y' => ($this->originalImageSizes['height'] / 2) - ($this->cropBox['height'] / 2)
    ];
  }

}
