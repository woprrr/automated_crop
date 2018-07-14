<?php

namespace Drupal\automated_crop;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a base class for each AutomatedCrop provider plugins.
 */
abstract class AbstractAutomatedCrop extends PluginBase implements AutomatedCropInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Aspect ratio validation regexp.
   *
   * @var string
   */
  const ASPECT_RATIO_FORMAT_REGEXP = '/^\d{1,3}+:\d{1,3}+$/';

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * All available value expected to calculate crop box area.
   *
   * @var array
   */
  protected $cropBox = [
    'width' => 0,
    'height' => 0,
    'min_width' => 0,
    'min_height' => 0,
    'max_width' => 0,
    'max_height' => 0,
    'x' => 0,
    'y' => 0,
    'aspect_ratio' => 'NaN',
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
   * @var \Drupal\Core\Image\ImageInterface
   */
  protected $image;

  /**
   * The percentage of automatic cropping area when initializes.
   *
   * @var int|float
   */
  protected $autoCropArea = 1;

  /**
   * The delta obtained by dividing the height by width.
   *
   * This delta are mandatory to process enlargement/reduction without ratio,
   * destruction. If the aspect ratio are defined the delta are based on your,
   * given ratio, if not we calculate the GCD of your originalImage and,
   * calculate, the ratio. This you can obtained same result by the following,
   * calculation `(original height / original width) x new width = new height`.
   *
   * @var int|float
   */
  protected $delta;

  /**
   * Constructs AutomatedCrop plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->initCropBox();
    $this->calculateCropBoxSize();
    $this->calculateCropBoxCoordinates();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Calculation of the dimensions of the crop area.
   *
   * This method allow you to define a specific logic to calculate,
   * the sizes of crop area.
   *
   * @return self
   *   AutomatedCrop plugin with cropBox set.
   *
   * @see \Drupal\automated_crop\Plugin\AutomatedCrop\AutomatedCropDefault
   */
  abstract public function calculateCropBoxSize();

  /**
   * Calculation of the coordinates of the crop area.
   *
   * This method allow you to define a specific logic to calculate,
   * the position of the top left corner of crop area.
   *
   * @return self
   *   AutomatedCrop plugin with the coordinates set.
   *
   * @see \Drupal\automated_crop\Plugin\AutomatedCrop\AutomatedCropDefault
   */
  abstract public function calculateCropBoxCoordinates();

  /**
   * Initializes the properties of the plugins according to the configurations.
   *
   * @return self
   *   AutomatedCrop plugin initialized.
   */
  public function initCropBox() {
    $this->setImage($this->configuration['image']);
    $this->setCropBoxProperties();
    $this->setOriginalSize();
    $this->setAspectRatio();
    $this->setDelta();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalSize() {
    $this->originalImageSizes['width'] = (int) $this->image->getWidth();
    $this->originalImageSizes['height'] = (int) $this->image->getHeight();

    // Store Max/Min limits of original image by default.
    if (empty($this->cropBox['max_width']) && empty($this->cropBox['max_height'])) {
      $this->setMaxSizes($this->originalImageSizes['width'], $this->originalImageSizes['height']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalSize() {
    $this->originalImageSizes;
  }

  /**
   * {@inheritdoc}
   */
  public function setImage($image) {
    $this->image = $image;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getImage() {
    return $this->image;
  }

  /**
   * {@inheritdoc}
   */
  public function setAspectRatio() {
    $aspect_ratio = isset($this->configuration['aspect_ratio']) ? $this->configuration['aspect_ratio'] : 'NaN';
    if ('NaN' !== $aspect_ratio && preg_match(self::ASPECT_RATIO_FORMAT_REGEXP, $aspect_ratio)) {
      $this->aspectRatio = $aspect_ratio;
      return $this;
    }

    $gcd = $this->calculateGcd($this->originalImageSizes['width'], $this->originalImageSizes['height']);
    $this->aspectRatio = round($this->originalImageSizes['width'] / $gcd) . ':' . round($this->originalImageSizes['height'] / $gcd);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDelta() {
    $ratio = explode(':', $this->getAspectRatio());
    $this->delta = (int) $ratio['1'] / (int) $ratio['0'];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDelta() {
    return $this->delta;
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
  public function setAnchor(array $coordinates = []) {
    array_merge($coordinates, $this->cropBox);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function anchor() {
    return [
      'x' => $this->cropBox['x'],
      'y' => $this->cropBox['y'],
    ];
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
  public function setMaxSizes($maxWidth, $maxHeight) {
    if (!empty($maxWidth)) {
      $this->cropBox['max_width'] = $maxWidth;
    }

    if (!empty($maxHeight)) {
      $this->cropBox['max_height'] = $maxHeight;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCropBoxSize(int $width, int $height) {
    $this->cropBox['width'] = max($this->cropBox['min_width'], $width * $this->autoCropArea);
    $this->cropBox['height'] = max($this->cropBox['min_height'], $height * $this->autoCropArea);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += $this->defaultConfiguration();

    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setAutoCropArea($num) {
    $this->autoCropArea = $num;

    return $this;
  }

  /**
   * Evaluate if crop box has Hard sizes defined.
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
  protected static function calculateGcd($a, $b) {
    if ($b > $a) {
      $gcd = self::calculateGcd($b, $a);
    }
    else {
      while ($b > 0) {
        $t = $b;
        $b = $a % $b;
        $a = $t;
      }
      $gcd = $a;
    }
    return $gcd;
  }

  /**
   * Set all crop box properties from plugin configuration.
   *
   * @return self
   *   AutomatedCrop plugin object.
   */
  protected function setCropBoxProperties() {
    foreach ($this->configuration as $element => $value) {
      if (array_key_exists($element, $this->cropBox) && !empty($value)) {
        $this->cropBox[$element] = (int) $value;
      }
    }

    return $this;
  }

}
