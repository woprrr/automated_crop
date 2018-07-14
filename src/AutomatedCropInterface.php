<?php

namespace Drupal\automated_crop;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface defining the AutomatedCrop plugin objects.
 */
interface AutomatedCropInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Returns the display label.
   *
   * @return string
   *   The display label.
   */
  public function label();

  /**
   * Set the aspect ratio from plugin object.
   *
   * If the plugin configuration is set to `NaN` or not respect the pattern of,
   * aspect ratio (W:H) we will calculate if from the original sizes of image.
   * You should be sure to prevent this case to avoid potential,
   * destructive crop.
   *
   * @return self
   *   The plugin object with aspect_ratio property set.
   */
  public function setAspectRatio();

  /**
   * Get the aspect ratio.
   *
   * @return string
   *   The aspect ratio string formatted like `W:H`.
   */
  public function getAspectRatio();

  /**
   * Gets crop anchor (top-left corner of crop area).
   *
   * @return array
   *   Array with two keys (x, y) and anchor coordinates as values.
   */
  public function anchor();

  /**
   * Set crop anchor (top-left corner of crop area).
   *
   * @param array $coordinates
   *   An array with (x,y) positions of top left corner of crop box.
   *
   * @return self
   *   The plugin object with given (x, y) coordinates properties set.
   */
  public function setAnchor(array $coordinates = []);

  /**
   * Gets crop box size.
   *
   * @param int $maxWidth
   *   The maximum width of the crop box area.
   * @param int $maxHeight
   *   The maximum height of the crop box area.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function setMaxSizes($maxWidth, $maxHeight);

  /**
   * Set crop box sizes.
   *
   *  The width & height of auto crop area must large than min sizes.
   *
   * @param int $width
   *   The width of the crop area to be set.
   * @param int $height
   *   The width of the crop area to be set.
   *
   * @return self
   *   The plugin with two cropBox properties (width, height) set.
   */
  public function setCropBoxSize(int $width, int $height);

  /**
   * Gets crop box size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function size();

  /**
   * Set the original sizes of image.
   *
   * This method should also define the maximum size of the crop area to,
   * ensure that the crop area does not exceed the original. If you want,
   * to go beyond the original frame of the image redefine it.
   *
   * @return self
   *   The plugin with originalImageSizes/maximum sizes property defined.
   */
  public function setOriginalSize();

  /**
   * Get the original image sizes of image to be cropped.
   *
   * @return array
   *   Array with two keys (width, height) represent the original image sizes.
   */
  public function getOriginalSize();

  /**
   * Set the image resource from plugin configuration to be cropped.
   *
   * @param mixed $image
   *   The image resource to be cropped. As default this resource will be an,
   *   drupal Image object, but in some others usecase we can set another kind,
   *   of image resources. You must be sure to give a correct image object.
   *
   * @return self
   *   The plugin object with image property defined.
   */
  public function setImage($image);

  /**
   * Gets the plugin image object to crop.
   *
   * @return \Drupal\Core\Image\ImageInterface
   *   An image file object.
   */
  public function getImage();

  /**
   * Define the percentage of automatic cropping area when initializes.
   *
   * This method is useful to zoom with mobile/retina images.
   *
   * @param int|float $num
   *   The percentage of automatic cropping area.
   *
   * @return self
   *   AutomatedCrop plugin object.
   */
  public function setAutoCropArea($num);

  /**
   * Calculate and set the delta to apply of each size calculation of cropBox.
   *
   * The delta are important to apply enlargement/reduction without loose,
   * aspect ratio. This is the result of height divide by width (H / W) by,
   * default this is calculated from the aspect ratio of image but you can,
   * also calculate it like (originalImageHeight / OriginalImageWidth).
   *
   * @return self
   *   AutomatedCrop plugin object.
   */
  public function setDelta();

  /**
   * Gets the delta of image.
   *
   * @return int|float
   *   The delta calculated to apply to your cropBox sizes.
   */
  public function getDelta();

}
