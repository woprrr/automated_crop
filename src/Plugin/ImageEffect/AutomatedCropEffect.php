<?php

namespace Drupal\automated_crop\Plugin\ImageEffect;

use Drupal\automated_crop\AbstractAutomatedCrop;
use Drupal\automated_crop\AutomatedCropManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\ConfigurableImageEffectBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Image\ImageFactory;

/**
 * Provide an Automatic crop tools.
 *
 * @ImageEffect(
 *   id = "automated_crop",
 *   label = @Translation("Automated Crop"),
 *   description = @Translation("Applies automated crop to the image.")
 * )
 */
class AutomatedCropEffect extends ConfigurableImageEffectBase implements ContainerFactoryPluginInterface {

  /**
   * AutomatedCrop object.
   *
   * @var \Drupal\automated_crop\AutomatedCropManager
   */
  protected $automatedCropManager;

  /**
   * Automated crop object loaded with current image.
   *
   * @var \Drupal\automated_crop\AutomatedCropInterface|false
   */
  protected $automatedCrop;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, AutomatedCropManager $plugin_automated_crop, ImageFactory $image_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->automatedCropManager = $plugin_automated_crop;
    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('plugin.manager.automated_crop'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    /** @var \Drupal\automated_crop\AutomatedCropInterface $crop */
    if ($crop = $this->getAutomatedCrop($image)) {
      $anchor = $crop->anchor();
      $size = $crop->size();

      if (!$image->crop($anchor['x'], $anchor['y'], $size['width'], $size['height'])) {
        $this->logger->error('Automated image crop failed using the %toolkit toolkit on %path (%mimetype, %width x %height)', [
            '%toolkit' => $image->getToolkitId(),
            '%path' => $image->getSource(),
            '%mimetype' => $image->getMimeType(),
            '%width' => $image->getWidth(),
            '%height' => $image->getHeight(),
          ]
        );
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'automated_crop_summary',
      '#data' => $this->configuration,
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
        'width' => NULL,
        'height' => NULL,
        'min_width' => NULL,
        'min_height' => NULL,
        'max_width' => NULL,
        'max_height' => NULL,
        'aspect_ratio' => 'NaN',
        'provider' => 'automated_crop_default',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['provider'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Automated crop provider'),
      '#empty_option' => $this->t("- Select a Provider -"),
      '#options' => $this->automatedCropManager->getProviderOptionsList(),
      '#default_value' => $this->configuration['provider'],
      '#description' => $this->t('The name of automated crop provider plugin to use.'),
    ];

    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->configuration['width'],
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#description' => $this->t("If your sizes W + H not respect original aspect ratio, the system adapt it to ensure you don't deform image."),
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['height'],
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#description' => $this->t("If your sizes W + H not respect original aspect ratio, the system adapt it to ensure you don't deform image."),
    ];

    $form['min_sizes'] = [
      '#type' => 'details',
      '#title' => $this->t('Min sizes limits'),
      '#description' => $this->t('Define crop size minimum limit.'),
      '#open' => FALSE,
    ];

    $form['min_sizes']['min_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Min Width'),
      '#default_value' => $this->configuration['min_width'],
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['min_sizes']['min_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Min Height'),
      '#default_value' => $this->configuration['min_height'],
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['max_sizes'] = [
      '#type' => 'details',
      '#title' => $this->t('Max sizes limits'),
      '#description' => $this->t('Define crop size maximum limit.'),
      '#open' => FALSE,
    ];

    $form['max_sizes']['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Width'),
      '#default_value' => $this->configuration['max_width'],
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['max_sizes']['max_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Height'),
      '#default_value' => $this->configuration['max_height'],
      '#field_suffix' => ' ' . $this->t('pixels'),
    ];

    $form['aspect_ratio'] = [
      '#title' => $this->t('Aspect Ratio'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['aspect_ratio'],
      '#attributes' => ['placeholder' => 'W:H'],
      '#description' => $this->t('Set an aspect ratio <b>eg: 16:9</b> or leave this empty for arbitrary aspect ratio'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('aspect_ratio')) && !preg_match(AbstractAutomatedCrop::ASPECT_RATIO_FORMAT_REGEXP, $form_state->getValue('aspect_ratio'))) {
      $form_state->setError($form['aspect_ratio'], $form['aspect_ratio']['#title'] . ': ' . $this->t('Invalid aspect ratio format. Should be defined in H:W form.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['width'] = $form_state->getValue('width');
    $this->configuration['height'] = $form_state->getValue('height');
    $this->configuration['min_width'] = $form_state->getValue('min_sizes')['min_width'];
    $this->configuration['min_height'] = $form_state->getValue('min_sizes')['min_height'];
    $this->configuration['max_width'] = $form_state->getValue('max_sizes')['max_width'];
    $this->configuration['max_height'] = $form_state->getValue('max_sizes')['max_height'];
    $this->configuration['aspect_ratio'] = $form_state->getValue('aspect_ratio');
    $this->configuration['provider'] = $form_state->getValue('provider', 'automated_crop_default');
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    /** @var \Drupal\Core\Image\Image $image */
    $image = $this->imageFactory->get($uri);
    $sizes = $this->getAutomatedCrop($image)->size();

    // The new image will have the exact dimensions defined by effect.
    $dimensions['width'] = $sizes['width'];
    $dimensions['height'] = $sizes['height'];
  }

  /**
   * Gets crop coordinates.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   Image object.
   *
   * @return \Drupal\automated_crop\AutomatedCropInterface|false
   *   Crop coordinates onto original image.
   */
  protected function getAutomatedCrop(ImageInterface $image) {
    if (!isset($this->automatedCrop)) {
      $this->automatedCrop = FALSE;
      $crop_coordinates = $this->automatedCropManager->createInstance($this->configuration['provider'], [
        'image' => $image,
        'width' => $this->configuration['width'],
        'height' => $this->configuration['height'],
        'min_width' => $this->configuration['min_width'],
        'min_height' => $this->configuration['min_height'],
        'max_width' => $this->configuration['max_width'],
        'max_height' => $this->configuration['max_height'],
        'aspect_ratio' => $this->configuration['aspect_ratio'],
      ]);

      if ($crop_coordinates) {
        $this->automatedCrop = $crop_coordinates;
      }
    }

    return $this->automatedCrop;
  }

}
