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
    'min_height' => NULL,
    'max_width' => NULL,
    'max_height' => NULL
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
    $this->setCropBoxValues($sizes);
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

  protected function setCropBoxValues(array $sizes) {
    foreach ($sizes as $element => $value) {
      // @TODO throw an exception if element are not available.
      if (array_key_exists($element, $this->cropBox)) {
        $this->cropBox["$element"] = (int) $value;
      }
    }
    return $this;
  }

  /**
   * Calculate aspect ratio of CropBox area or define new.
   *
   * @param string $aspect_ratio
   *   The enforced aspect ratio to applie on CropBox or "NaN" to save original cropBox ratio.
   *
   * @return integer|null
   *  Aspect ratio calculated to apply on area sizes.
   */
  protected function setAspectRatio($aspect_ratio = 'NaN') {
    // If Aspect ratio is enforced and match with format W:H.
    if ($aspect_ratio != 'NaN' && preg_match('/^\d{1,3}+:\d{1,3}+$/', $aspect_ratio)) {
      $this->aspectRatio = $aspect_ratio;
    } else {
      $gcd = $this->calculateGCD($this->originalImageSizes['width'], $this->originalImageSizes['height']);
      $this->aspectRatio = round($this->originalImageSizes['width'] / $gcd) . ':' . round($this->originalImageSizes['height'] / $gcd);
    }
  }

  public function hasSizes() {
    return (!empty($this->cropBox['width']) && !empty($this->cropBox['height'])) ? TRUE : FALSE;
  }

  // @TODO Possibly change that to setter, it's not role to getter to calculates...
  public function getCropArea() {
    // If we not have sizes (w or h) and aspect ratio are enforced.
    if (!$this->hasSizes() && $ratio = explode(':', $this->getAspectRatio())) {
      if (!empty($this->cropBox['width'])) {
        $this->cropBox['height'] = ($this->cropBox['width'] * $ratio['1']) / $ratio['0'];
      } elseif (!empty($this->cropBox['height'])) {
        $this->cropBox['width'] = ($this->cropBox['height'] * $ratio['0']) / $ratio['1'];
      } else {
        // If we need to not enforces size but just crop on another aspect ratio on original image.
        $this->cropBox['width'] = $this->originalImageSizes['width'];
        $this->cropBox['height'] = ($this->cropBox['width'] * $ratio['1']) / $ratio['0'];
      }
    }

    // Initialize auto crop area & unsure we can't override original image sizes.
    $this->cropBox['width'] = min(max($this->cropBox['width'], $this->cropBox['min_width']), $this->originalImageSizes['width']);
    $this->cropBox['height'] = min(max($this->cropBox['height'], $this->cropBox['min_height']), $this->originalImageSizes['height']);

    // The width & height of auto crop area must large than min sizes.
    return [
      'width' => round(max($this->cropBox['min_width'], $this->cropBox['width'] * $this->autoCropArea)),
      'height' => round(max($this->cropBox['min_height'], $this->cropBox['height'] * $this->autoCropArea))
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

  /**
   * Calculate the greatest common denominator of two numbers.
   *
   * @param int $a
   *   First number to check.
   * @param int $b
   *   Second number to check.
   *
   * @return integer|null
   *  Greatest common denominator of $a and $b.
   */
  private static function calculateGCD($a, $b) {
    if (extension_loaded('gmp_gcd')) {
      $gcd = gmp_intval(gmp_gcd($a, $b));
    }
    else {
      if ($b > $a) {
        $gcd = self::calculateGCD($b, $a);
      }
      else {
        while ($b > 0) {
          $t = $b;
          $b = $a % $b;
          $a = $t;
        }
        $gcd = $a;
      }
    }
    return $gcd;
  }

}
