# Automated Crop module [![Build Status](https://travis-ci.org/woprrr/automated_crop.svg?branch=8.x-1.x)](https://travis-ci.org/drupal-media/crop) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/woprrr/automated_crop/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/woprrr/automated_crop/?branch=8.x-1.x) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0cde56f-c807-4714-b6cf-38970e2985f9/mini.png)](https://insight.sensiolabs.com/projects/d0cde56f-c807-4714-b6cf-38970e2985f9)

Provides an API for automatic cropping tools integration.

## Requirements

* Latest release of Drupal 8.x.

## Configuration

* Go to (`admin/config/media/image-styles/manage`).
* Edit an image style or create it.
* Select a new effect `Automated Crop`.
* Configure the effect as you need.
* Upload an image with your image style used.
* Your picture are automatically cropped as you want (in configuration). 

## Technical details

Initial discussion can be found on [automated crop integration].

[automated crop integration]: https://www.drupal.org/node/2830768

## Uses case coverage

### Case 1 

All sizes of crop box are defined (Width AND Height), and do not take into account the aspect ratio of image.
That similar to Scale & crop but that only applie an crop with specific sizes by center of image. I ve already think about the possibility to define another points to attach crop area but i think that is another case.

### Case 2

Only one size are completed (Width OR Height), the algorithm do calculate the missing value with respect, of aspect ratio.
It's important to notice that, if user have define any aspect ratio then the aspect ratio are original image aspect ratio but if user have define an enforced aspect ratio that is used to calculation of missing value to respect new aspect ratio.

### Case 3

Any sizes values are defined, the algorythm are based on the maximum widht of original image and calculate height with
respect of aspect ratio (in this case too, if user enforce original aspect ratio that take prior).

That is the three principal cases of crop functional in this module ATM. 

Other usecases "combo" are available by example :

Scale & crop + Automated crop
That can be used to resize an image onto specific size and after automated crop an other specific area onto image resized.

All other effects are compatible with this effect because Automated crop use the $image statement and the position of
effect can affect the state of image by example if we apply an effect black & white after automated crop your crop zone are in black & white.
