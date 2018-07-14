<?php

namespace Drupal\automated_crop;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages EntityRoutingMap plugins.
 */
class AutomatedCropManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a new AutomatedCropManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AutomatedCrop', $namespaces, $module_handler, 'Drupal\automated_crop\AutomatedCropInterface', 'Drupal\automated_crop\Annotation\AutomatedCrop');
    $this->alterInfo('automated_crop_info');
    $this->setCacheBackend($cache_backend, 'automated_crop_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'automated_crop_default';
  }

  /**
   * Get the available provider to purpose an automated crop.
   *
   * @return array
   *   The Automated crop provider options list.
   */
  public function getProviderOptionsList() {
    $options = [];
    $plugin_definitions = $this->getDefinitions();
    foreach ($plugin_definitions as $name => $plugin) {
      $options[$name] = $plugin['label'];
    }

    return $options;
  }

}
