<?php

namespace Drupal\automated_crop\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for Automated Crop.
 *
 * @group crop
 */
class AutomatedCropFunctionalTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['automated_crop', 'file'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Test image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $testStyle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer image styles']);

    // Create test image style.
    $this->testStyle = $this->container->get('entity.manager')->getStorage('image_style')->create([
      'name' => 'test',
      'label' => 'Test image style',
      'effects' => [],
    ]);
    $this->testStyle->save();
  }

  /**
   * Initialization of TESTS ...
   */
  public function testNothing() {
    return $this->assertTrue(TRUE, t('ok useless but necessary :P'));
  }

}
