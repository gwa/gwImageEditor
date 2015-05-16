gwImageEditor
=============

A PHP image editor library that uses the bundled GD library.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gwa/gwImageEditor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gwa/gwImageEditor/?branch=master) [![Build Status](https://api.travis-ci.org/gwa/gwImageEditor.svg?branch=master)](https://travis-ci.org/gwa/gwImageEditor)

Provides a simple and intuitive interface for typical editing, cropping, resizing and filtering actions on existing images.

Typical use cases:

* Scaling and cropping uploaded images to within constraints
* Creating different sized versions of an image
* Adding watermarks

## Installation

Install through composer:

`composer require gwa/gw-image-editor`

## Usage

~~~php
use Gwa\Image\ImageEditor;

// create an editor using an existing image
$editor = new ImageEditor('path/to/image.png');

// resize image to within 640 x 480 px, maintaining aspect ratio
$editor->resizeToWithin(640, 480)
    ->save();

// resize image to exactly 640 x 480 px, cropping any overhang
$editor->resizeTo(640, 480)
    ->save();

// crop the image: x, y, width, height
$editor->crop(10, 10, 100, 100)
    ->save();

// methods can be chained
// - create and save medium image with max width and height of 500px
// - create and save thumbnail image with exact dimensions of 100px x 100px
$editor = new ImageEditor('path/to/original.jpg');
$editor->resizeToWithin(500, 500)
    ->saveAs('path/to/medium.jpg')
    ->resizeTo(100, 100)
    ->saveAs('path/to/thumbnail.jpg');

// another image can be "pasted" in (e.g. a watermark)
// - paste watermark into bottom right corner
$editor = new ImageEditor('path/to/original.jpg');
$iw = $editor->getWidth();
$ih = $editor->getHeight();

$watermark = new ImageEditor('path/to/watermark.png');
$ww = $watermark->getWidth();
$wh = $watermark->getHeight();

$padding = 10;

$editor->pasteImage($watermark, $iw - $ww - $padding, $ih - $wh - $padding)
    ->saveAs('path/to/watermarked.jpg');

// there are also filter methods available
$editor = new ImageEditor('path/to/original.jpg');
$editor->grayscale()
    ->brightness(127) // -255 to +255
    ->colorize(255, 0, 0, 0) // rgba
~~~

## Tests

PHPUnit

```
$ ./vendor/bin/phpunit -c tests/phpunit.xml --coverage-html=tests/_report tests
```

Code style (PSR-2)

```
$ ./vendor/bin/phpcs --standard=PSR2 src
```

