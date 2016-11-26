<?php

namespace Drupal\automated_crop\Plugin\ImageEffect;

use Drupal\automated_crop\AutomatedCropFactory;
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
   * @var \Drupal\automated_crop\AutomatedCropFactory
   */
  protected $automatedCropFactory;

  /**
   * Automated crop object loaded with current image.
   *
   * @var \Drupal\automated_crop\AutomatedCropFactory
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, AutomatedCropFactory $automated_crop_services, ImageFactory $image_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->automatedCropFactory = $automated_crop_services;
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
      $container->get('automated_crop.factory'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    /** @var \Drupal\automated_crop\AutomatedCropFactory $crop */
    if ($crop = $this->getAutomatedCrop($image)) {
      $sizes = $crop->getCropArea();
      $anchor = $crop->getAnchor();
      if (!$image->crop($anchor['x'], $anchor['y'], $sizes['width'], $sizes['height'])) {
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // @TODO prevent user if the entered values break aspect ratio of original image or use Scale & crop to hard crop...
    $form['width'] = array(
      '#type' => 'number',
      '#title' => t('Width'),
      '#default_value' => $this->configuration['width'],
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#description' => $this->t("If your sizes W + H not respect original aspect ratio, the system adapt it to ensure you don't deform image."),
      // @TODO delete this whenever aspect_ratio option are enable.
      '#required' => TRUE,
      '#min' => 1,
    );
    $form['height'] = array(
      '#type' => 'number',
      '#title' => t('Height'),
      '#default_value' => $this->configuration['height'],
      '#field_suffix' => ' ' . $this->t('pixels'),
      '#description' => $this->t("If your sizes W + H not respect original aspect ratio, the system adapt it to ensure you don't deform image."),
      // @TODO delete this whenever aspect_ratio option are enable.
      '#required' => TRUE,
      '#min' => 1,
    );

    // @TODO Not sure that is expected by users...
//    $form['min_sizes'] = [
//      '#type' => 'details',
//      '#title' => $this->t('Min sizes limits'),
//      '#description' => $this->t('Define crop size minimum limit.'),
//      '#open' => FALSE,
//    ];
//
//    $form['min_sizes']['min_width'] = [
//      '#type' => 'number',
//      '#title' => t('Min Width'),
//      '#default_value' => $this->configuration['min_width'],
//      '#field_suffix' => ' ' . t('pixels'),
//    ];
//
//    $form['min_sizes']['min_height'] = [
//      '#type' => 'number',
//      '#title' => t('Min Height'),
//      '#default_value' => $this->configuration['min_height'],
//      '#field_suffix' => ' ' . t('pixels'),
//    ];

    // @TODO Not sure that is expected by users...
//    $form['max_sizes'] = [
//      '#type' => 'details',
//      '#title' => $this->t('Max sizes limits'),
//      '#description' => $this->t('Define crop size maximum limit.'),
//      '#open' => FALSE,
//    ];
//
//    $form['max_sizes']['max_width'] = [
//      '#type' => 'number',
//      '#title' => t('Max Width'),
//      '#default_value' => $this->configuration['max_width'],
//      '#field_suffix' => ' ' . t('pixels'),
//    ];
//
//    $form['max_sizes']['max_height'] = [
//      '#type' => 'number',
//      '#title' => t('Max Height'),
//      '#default_value' => $this->configuration['max_height'],
//      '#field_suffix' => ' ' . t('pixels'),
//    ];

    // That can be used in case when user not define any width / height but just need to crop onto 4:3 an 16:9 area.
//    $form['aspect_ratio'] = [
//      '#title' => t('Aspect Ratio'),
//      '#type' => 'textfield',
//      '#default_value' => $this->configuration['aspect_ratio'],
//      '#attributes' => ['placeholder' => 'W:H'],
//      '#description' => t('Set an aspect ratio <b>eg: 16:9</b> or leave this empty for arbitrary aspect ratio'),
//    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['width'] = $form_state->getValue('width');
    $this->configuration['height'] = $form_state->getValue('height');
//    $this->configuration['min_width'] = $form_state->getValue('min_width');
//    $this->configuration['min_height'] = $form_state->getValue('min_height');
//    $this->configuration['max_width'] = $form_state->getValue('max_width');
//    $this->configuration['max_height'] = $form_state->getValue('max_height');
//    $this->configuration['aspect_ratio'] = $form_state->getValue('aspect_ratio');
  }

  /**
   * Gets crop coordinates.
   *
   * @param ImageInterface $image
   *   Image object.
   *
   * @return \Drupal\automated_crop\AutomatedCropFactory|FALSE
   *   Crop coordinates onto original image.
   */
  protected function getAutomatedCrop(ImageInterface $image) {
    if (!isset($this->cropCoordinates)) {
      $this->automatedCrop = FALSE;
      if ($crop_coordinates = $this->automatedCropFactory->initCropBox($image, [
        'width' => $this->configuration['width'],
        'height' => $this->configuration['height'],
        'min_width' => $this->configuration['min_width'],
        'min_height' => $this->configuration['min_height']
      ], $this->configuration['aspect_ratio'])
      ) {
        $this->automatedCrop = $crop_coordinates;
      }
    }

    return $this->automatedCrop;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    /** @var \Drupal\Core\Image\Image $image */
    $image = $this->imageFactory->get($uri);

    $sizes = $this->getAutomatedCrop($image)->getCropArea();

    // The new image will have the exact dimensions defined by effect.
    $dimensions['width'] = $sizes['width'];
    $dimensions['height'] = $sizes['height'];
  }

}