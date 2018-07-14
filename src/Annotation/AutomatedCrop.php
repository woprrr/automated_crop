<?php

namespace Drupal\automated_crop\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines AutomatedCrop annotation object.
 *
 * @see hook_automated_crop_display_info_alter()
 *
 * @Annotation
 */
class AutomatedCrop extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
