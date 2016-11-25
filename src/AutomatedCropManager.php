<?php

namespace Drupal\automated_crop;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageInterface;

/**
 * AutomatedCropManager calculation class.
 */
class AutomatedCropManager {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

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
   * Create new crop entity with user properties.
   *
   * @param ImageInterface $image
   *   Image object.
   * @param array $sizes
   *   Size of crop area provide by effect configuration.
   * @param string $aspect_ratio
   *   Enforce of not ('NaN') the original aspect ratio of image.
   * @param int $autoCropArea
   *   Define the percentage of automatic cropping area when initializes.
   *
   * @return array
   *   Crop coordinates to applie onto image.
   */
  public function applyAutomatedCrop(ImageInterface $image, $sizes, $aspect_ratio = 'NaN', $autoCropArea = 1) {
    // Get sizes of crop area needed by configuration.
    $crop_box = [
      'height' => $sizes['width'],
      'width' => $sizes['height'],
    ];

    // Retrive sizes of original image.
    $original_w = $image->getWidth();
    $original_h = $image->getHeight();

    $aspect_ratio = $this->getAspectRatio($aspect_ratio, $crop_box, $original_w, $original_h);

    // Preserve aspect ratio.
    if ($aspect_ratio) {
      if ($original_h * $aspect_ratio > $original_w) {
        $crop_box['height'] = $crop_box['width'] / $aspect_ratio;
      }
      else {
        $crop_box['width'] = $crop_box['height'] * $aspect_ratio;
      }
    }

    $crop_box = $this->getCropArea($crop_box, $sizes, $original_w, $original_h, $autoCropArea);

    $anchor = $this->getAnchor($original_w, $original_h, $crop_box);

    return [
      'x' => $anchor['x'],
      'y' => $anchor['y'],
      'width' => $crop_box['width'],
      'height' => $crop_box['height']
    ];
  }

  protected function getAnchor($width, $height, $crop_box) {
    return [
      'x' => ($width / 2) - ($crop_box['width'] / 2),
      'y' => ($height / 2) - ($crop_box['height'] / 2)
    ];
  }

  protected function getCropArea($crop_box, $sizes, $width, $height, $autoCropArea) {
    // Initialize auto crop area & unsure we can't override original image sizes.
    // @TODO we can change $original_w by max_w by example if we need...
    $crop_box['width'] = min(max($crop_box['width'], $sizes['min_width']), $width);
    $crop_box['height'] = min(max($crop_box['height'], $sizes['min_height']), $height);

    // The width & height of auto crop area must large than min sizes.
    return [
      'height' => round(max($sizes['min_width'], $crop_box['width'] * $autoCropArea)),
      'width' => round(max($sizes['min_height'], $crop_box['height'] * $autoCropArea))
    ];
  }

  /**
   * Calculate aspect ratio to homotetic crop or enforce crop area.
   *
   * @param string|NULL $a
   *   The enforced aspect ratio to crop on image.
   * @param array $crop_box
   *   Size of crop area choose by configuration.
   * @param int $width
   *   Original width of image.
   * @param int $height
   *   Original height of image.
   *
   * @return integer|null
   *  Aspect ratio calculated to applie on each sizes to crop on same aspect ratio of image.
   */
  protected function getAspectRatio($aspect_ratio = NULL, $crop_box = [], $width, $height) {
    // @TODO First case When we need to crop automatically in other aspect ratio.
    if ($aspect_ratio != 'NaN' && (empty($crop_box['width']) && empty($crop_box['height']))) {
      $aspect_option = explode(':', $aspect_ratio);
      if (!empty($aspect_option) && (is_int($aspect_option['0']) && is_int($aspect_option['1']))) {
        $width = (int) $aspect_option['0'];
        $height = (int) $aspect_option['1'];
      }
    }

    return $width / $height;
  }

}
