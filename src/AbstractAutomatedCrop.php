<?php

namespace Drupal\automated_crop;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Xxxxx.
 */
abstract class AbstractAutomatedCrop extends PluginBase implements AutomatedCropInterface, ContainerFactoryPluginInterface
{

  use StringTranslationTrait;

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

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
   * Constructs display plugin.
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


  abstract public function calculateCropBoxSize();

  abstract public function calculateCropBoxCoordinates();

  /**
   * Initialize needed crop box onto image.
   *
   * @param array $sizes
   *   Size of crop area provide by effect configuration.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory
   *   AutomatedCrop object this is being called on.
   */
  public function initCropBox() {
    $this->setImage($this->configuration['image']);
    $this->setCropBoxProperties();
    $this->setOriginalSize();
    $this->setAspectRatio();

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
  public function setMaxSizes($max_width, $max_height) {
    if (!empty($max_width)) {
      $this->cropBox['max_width'] = $max_width;
    }

    if (!empty($max_height)) {
      $this->cropBox['max_height'] = $max_height;
    }

    return $this;
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
    if ('NaN' !== $aspect_ratio && preg_match(AutomatedCropManager::ASPECT_RATIO_FORMAT_REGEXP, $aspect_ratio)) {
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
  public function getAspectRatio() {
    return $this->aspectRatio;
  }

  /**
   * {@inheritdoc}
   */
  public function setAnchor(array $coordinates) {
    return array_merge($coordinates, $this->cropBox);
  }

  /**
   * {@inheritdoc}
   */
  public function setCropBoxSize($width, $height) {
    // The width & height of auto crop area must large than min sizes.
    $this->cropBox['width'] = max($this->cropBox['min_width'], $width * $this->autoCropArea);
    $this->cropBox['height'] = max($this->cropBox['min_height'], $height * $this->autoCropArea);

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
   * Define the percentage of automatic cropping area when initializes.
   *
   * @param int|float $num
   *   The percentage of automatic cropping area.
   *
   * @return self
   *   AutomatedCrop object this is being called on.
   */
  public function setAutoCropArea($num) {
    $this->autoCropArea = $num;

    return $this;
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
   * Set all crop box sizes value entered by plugin configuration.
   *
   * @return self
   *   AutomatedCrop object this is being called on.
   */
  protected function setCropBoxProperties() {
    foreach ($this->configuration as $element => $value) {
      if (array_key_exists($element, $this->cropBox) && !empty($value)) {
        $this->cropBox[$element] = (int) $value;
      }
    }

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

}
