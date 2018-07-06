<?php

namespace Drupal\automated_crop;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageInterface;

/**
 * AutomatedCropFactory class.
 */
class AutomatedCropFactory implements AutomatedCropInterface {

  /**
   * Aspect ratio validation regexp.
   *
   * @var array
   */
  const ASPECT_RATIO_FORMAT_REGEXP = '/^\d{1,3}+:\d{1,3}+$/';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * All available value expected to calculate crop box area.
   *
   * @var array
   */
  protected $cropBox = [
    'width' => NULL,
    'height' => NULL,
    'min_width' => NULL,
    'min_height' => NULL,
    'max_width' => NULL,
    'max_height' => NULL,
  ];

  /**
   * The machine name of this crop type.
   *
   * @var string
   */
  protected $originalImageSizes;

  /**
   * The machine name of this crop type.
   *
   * @var string
   */
  protected $aspectRatio;

  /**
   * The image object to crop.
   *
   * @var \Drupal\Core\Image\ImageInterface;
   */
  protected $image;

  /**
   * The percentage of automatic cropping area when initializes.
   *
   * @var integer|float
   */
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
   * @param \Drupal\Core\Image\ImageInterface $image
   *   Image object.
   * @param array $sizes
   *   Size of crop area provide by effect configuration.
   * @param string $aspect_ratio
   *   Enforce or not ('NaN') the original aspect ratio of image.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  public function initCropBox(ImageInterface $image, array $sizes, $aspect_ratio = 'NaN') {
    $this->setImageToCrop($image);
    $this->setOriginalSizes();
    $this->setAspectRatio($aspect_ratio);
    $this->setCropBoxSizes($sizes);

    return $this;
  }

  /**
   * Store original image to be cropped.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image object.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  private function setImageToCrop(ImageInterface $image) {
    $this->image = $image;

    return $this;
  }

  /**
   * Set original sizes of image.
   *
   * This function "pre-set" max sizes,
   * values as original image limits.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  private function setOriginalSizes() {
    $this->originalImageSizes['width'] = (int) $this->image->getWidth();
    $this->originalImageSizes['height'] = (int) $this->image->getHeight();

    // Store Max/Min limits of original image by default.
    $this->cropBox['max_width'] = (int) $this->image->getWidth();
    $this->cropBox['max_height'] = (int) $this->image->getHeight();

    return $this;
  }

  /**
   * Calculate aspect ratio of CropBox area.
   *
   * Define as fallback the original aspect ratio of image,
   * the aspect ratio are very important for calculation of crop area,
   * that does.
   *
   * @param string $aspect_ratio
   *   The aspect ratio expected.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  public function setAspectRatio($aspect_ratio = 'NaN') {
    // If Aspect ratio is enforced and match with format W:H.
    if ($aspect_ratio != 'NaN' && preg_match($this::ASPECT_RATIO_FORMAT_REGEXP, $aspect_ratio)) {
      $this->aspectRatio = $aspect_ratio;
    } else {
      $gcd = $this->calculateGcd($this->originalImageSizes['width'], $this->originalImageSizes['height']);
      $this->aspectRatio = round($this->originalImageSizes['width'] / $gcd) . ':' . round($this->originalImageSizes['height'] / $gcd);
    }

    return $this;
  }

  /**
   * Calculate (Width/Height) of crop box.
   *
   * That function can evaluate the sizes of CropBox in three cases :
   *
   * Case 1: All sizes of crop box are defined (Width AND Height),
   * and do not take into account the aspect ratio of image.
   *
   * Case 2: Only one size are completed (Width OR Height),
   * the algorithm do calculate the missing value with respect,
   * of aspect ratio. It's important to notice that, if user have
   * define any aspect ratio then the aspect ratio are
   * original image aspect ratio.
   *
   * Case 3: Any sizes values are defined, the algorythm are base
   * on the maximum widht of original image and calculate the height
   * with respect of aspect ratio (in this case too, if user enforce
   * original aspect ratio that take prior).
   *
   * @param array $sizes
   *   All sizes values expected by user (Width, Height, min/max limits),
   *   to define new image cropped.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  public function setCropBoxSizes($sizes) {
    $this->setCropBoxValues($sizes);

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
      if ($height > $this->cropBox['max_height']) {
        $height = $this->cropBox['max_height'];
        $width = round(($width * $delta));
      }
    } elseif ($this->hasSizes() && $width) {
      $height = round(($width * $delta));
    } elseif ($this->hasSizes() && !empty($height)) {
      $width = round(($height * $delta));
    }

    // Initialize auto crop area & unsure we can't exceed original image sizes.
    $width = min(max($width, $this->cropBox['min_width']), $this->cropBox['max_width']);
    $height = min(max($height, $this->cropBox['min_height']), $this->cropBox['max_height']);

    // The width & height of auto crop area must large than min sizes.
    $this->cropBox['width'] = max($this->cropBox['min_width'], $width * $this->autoCropArea);
    $this->cropBox['height'] = max($this->cropBox['min_height'], $height * $this->autoCropArea);

    return $this;
  }

  /**
   * Define the percentage of automatic cropping area when initializes.
   *
   * @param int|float $num
   *   The percentage of automatic cropping area.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  public function setAutoCropArea($num) {
    $this->autoCropArea = $num;

    return $this;
  }

  /**
   * Set all crop box sizes value entered by user.
   *
   * @param array $sizes
   *   All sizes values expected by user (Width, Height, min/max limits),
   *   to define new image cropped.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  protected function setCropBoxValues(array $sizes) {
    foreach ($sizes as $element => $value) {
      if (array_key_exists($element, $this->cropBox) && !empty($value)) {
        $this->cropBox["$element"] = (int) $value;
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function size() {
    return [
      'width' => $this->cropBox['width'],
      'height' => $this->cropBox['height'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAspectRatio() {
    return $this->aspectRatio;
  }

  /**
   * {@inheritdoc}
   */
  public function anchor() {
    return [
      'x' => ($this->originalImageSizes['width'] / 2) - ($this->cropBox['width'] / 2),
      'y' => ($this->originalImageSizes['height'] / 2) - ($this->cropBox['height'] / 2),
    ];
  }

  /**
   * Evaluate if user have set Hard sizes of crop box area.
   *
   * @return bool
   *   Return if we have width AND height value completed.
   */
  public function hasHardSizes() {
    return (!empty($this->cropBox['width']) && !empty($this->cropBox['height'])) ? TRUE : FALSE;
  }

  /**
   * Evaluate if user have set one of crop box area sizes.
   *
   * @return bool
   *   Return if we have width OR height value completed or false.
   */
  public function hasSizes() {
    if (!empty($this->cropBox['width'])) {
      return TRUE;
    }
    if (!empty($this->cropBox['height'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Calculate the greatest common denominator of two numbers.
   *
   * @param int $a
   *   First number to check.
   * @param int $b
   *   Second number to check.
   *
   * @return int|null
   *   Greatest common denominator of $a and $b.
   */
  private static function calculateGcd($a, $b) {
    if (extension_loaded('gmp_gcd')) {
      $gcd = gmp_intval(gmp_gcd($a, $b));
    } else {
      if ($b > $a) {
        $gcd = self::calculateGcd($b, $a);
      } else {
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
