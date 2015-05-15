<?php
namespace Gwa\Image;

/**
 * Class containing methods for editing images.
 */
class ImageEditor
{
    /**
     * @access private
     */
    private $filepath;

    /**
     * @access private
     */
    private $format;

    /**
     * @access private
     */
    private $width;

    /**
     * @access private
     */
    private $height;

    /**
     * @access private
     */
    private $mimetype;

    /**
     * @access private
     */
    private $resource;

    const DEFAULT_JPEG_QUALITY = 80;

    const FORMAT_JPEG = 'JPEG';
    const FORMAT_GIF  = 'GIF';
    const FORMAT_PNG  = 'PNG';

    /**
     * Constuctor
     *
     * @param string $filepath Path to an existing image
     * @throws InvalidArgumentException
     */
    public function __construct($filepath)
    {
        // make sure the GD library is installed
        if (!function_exists('gd_info')) {
            trigger_error('You do not have the GD Library installed.');
        }

        if (!file_exists($filepath)) {
            throw new \InvalidArgumentException('File does not exist: '.$filepath);
        }

        if (!is_readable($filepath)) {
            throw new \Exception('File is not readable: '.$filepath);
        }

        $this->filepath = $filepath;
        $this->extractFileData();
        $this->createResource();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (is_resource($this->resource)) {
            ImageDestroy($this->resource);
        }
    }

    /* -------- PUBLIC METHODS -------- */

    /**
     * Resizes image to be within a maximum width and a maximum height
     *
     * @param int $maxwidth
     * @param int $maxheight
     * @return ImageEditor
     */
    public function resizeToWithin($maxwidth, $maxheight)
    {
        if ($this->width <= $maxwidth && $this->height <= $maxheight) {
            return $this;
        }

        // calculate ratio based on widths
        $ratio = $maxwidth / $this->width;
        if ($this->height*$ratio > $maxheight) {
            // new height greater than maximum
            $ratio = $maxheight / $this->height;
        }

        $newwidth = round($this->width * $ratio);
        $newheight = round($this->height * $ratio);

        $newimage = $this->createImage($newwidth, $newheight);

        imageCopyResampled(
            $newimage,
            $this->resource,
            0,
            0,
            0,
            0,
            $newwidth,
            $newheight,
            $this->width,
            $this->height
        );

        $this->setResource($newimage);

        return $this;
    }

    /**
     * Resizes image to an exact width and height, maintaining aspect ratio. Any overhang is cropped.
     *
     * @param int $width
     * @param int $height
     * @return ImageEditor
     */
    public function resizeTo($width, $height)
    {
        if ($this->width === $width && $this->height === $height) {
            return false;
        }

        $ratio = $width / $this->width;
        $overhang = false;

        if ($this->height*$ratio < $height) {
            // - height is too small
            // - resize to height, and crop horizontal overhang
            $ratio = $height / $this->height;
            $overhang = true;
            $newwidth = round($this->width * $ratio);
            $newheight = $height;
        } elseif ($this->height*$ratio > $height) {
            // - height is too large
            // - resize to width, and crop vertical overhang
            $overhang = true;
            $newwidth = $width;
            $newheight = round($this->height * $ratio);
        } else {
            // proportions are correct
            $newwidth = $width;
            $newheight = $height;
        }

        $newimage = $this->createImage($newwidth, $newheight);
        imageCopyResampled(
            $newimage,
            $this->resource,
            0,
            0,
            0,
            0,
            $newwidth,
            $newheight,
            $this->width,
            $this->height
        );

        $this->setResource($newimage);

        if ($overhang) {
            // do the crop
            $this->cropFromCenter($width, $height);
        }

        return $this;
    }

    /* rotation -------- */

    /**
     * @return ImageEditor
     */
    public function rotateClockwise()
    {
        return $this->rotate(270);
    }

    /**
     * @return ImageEditor
     */
    public function rotateCounterClockwise()
    {
        return $this->rotate(90);
    }

    /**
     * @return ImageEditor
     */
    public function rotate180()
    {
        return $this->rotate(180);
    }

    private function rotate($deg)
    {
        imagealphablending($this->resource, false);
        $this->setResource(imagerotate($this->resource, $deg, 0));
        return $this;
    }

    /* crop -------- */

    /**
     * Crops the current image
     *
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     *
     * @return ImageEditor
     */
    public function crop($x, $y, $width, $height)
    {
        // check that crop is within bounds of image
        if ($x+$width > $this->width || $y+$height > $this->height) {
            throw new \InvalidArgumentException('crop out of bounds');
        }

        $newwidth = $width;
        $newheight = $height;
        $newimage = $this->createImage($newwidth, $newheight);

        imagecopy(
            $newimage,
            $this->resource,
            0,
            0,
            $x,
            $y,
            $newwidth,
            $newheight
        );

        $this->setResource($newimage);

        return $this;
    }

    /**
     * Crops the image from the center
     *
     * @param int $width
     * @param int $height
     *
     * @return ImageEditor
     */
    public function cropFromCenter($width, $height)
    {
        $x = ($this->width/2) - ($width/2);
        $y = ($this->height/2) - ($height/2);

        return $this->crop($x, $y, $width, $height);
    }

    /**
     * @return ImageEditor
     */
    public function greyscale()
    {
        return $this->grayscale();
    }

    /**
     * @return ImageEditor
     */
    public function grayscale()
    {
        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * @param int $value [-255, +255]
     *
     * @return ImageEditor
     */
    public function brightness($value)
    {
        imagefilter($this->resource, IMG_FILTER_BRIGHTNESS, $value);
        return $this;
    }

    /**
     * @param int $value [0, 255]
     * @param int $value [0, 255]
     * @param int $value [0, 255]
     * @param int $value [0, 127]
     *
     * @return ImageEditor
     */
    public function colorize($red, $green, $blue, $alpha = 0)
    {
        imagefilter($this->resource, IMG_FILTER_COLORIZE, $red, $green, $blue, $alpha);
        return $this;
    }

    /**
     * Paste another image onto this one.
     * @note Basically a wrapper method for http://www.php.net/manual/en/function.imagecopyresampled.php
     *
     * @param ImageEditor|string $imageeditor
     * @param int $dst_x
     * @param int $dst_y
     * @param int $dst_w
     * @param int $dst_h
     * @param int $src_x
     * @param int $src_y
     * @param int $src_w
     * @param int $src_h
     */
    public function pasteImage(
        $imageeditor,
        $dst_x = 0,
        $dst_y = 0,
        $dst_w = null,
        $dst_h = null,
        $src_x = null,
        $src_y = null,
        $src_w = null,
        $src_h = null
    ) {
        if (is_string($imageeditor)) {
            $imageeditor = new ImageEditor($imageeditor);
        }

        if (!$src_w) {
            $src_w = $imageeditor->getWidth();
        }
        if (!$src_h) {
            $src_h = $imageeditor->getHeight();
        }
        if (!$dst_w) {
            $dst_w = $src_w;
        }
        if (!$dst_h) {
            $dst_h = $src_h;
        }

        imagealphablending($this->resource, true);

        imagecopyresampled(
            $this->resource,
            $imageeditor->getResource(),
            $dst_x,
            $dst_y,
            $src_x,
            $src_y,
            $dst_w,
            $dst_h,
            $src_w,
            $src_h
        );

        imagealphablending($this->resource, false);

        return $this;
    }

    /* -------- */

    /**
     * "Duplicates" the image.
     * Basically unsets the filepath, so we have to use `saveAs()` and not `save()`.
     *
     * @return ImageEditor
     */
    public function duplicate()
    {
        $this->filepath = null;
        return $this;
    }

    /**
     * Saves the image
     *
     * @param int $quality 0-100 (only for jpegs)
     *
     * @return ImageEditor
     */
    public function save($quality = self::DEFAULT_JPEG_QUALITY)
    {
        if (!$this->filepath) {
            throw new \LogicException('Use saveTo() to save an unnamed file.');
        }
        return $this->saveAs($this->filepath, $quality);
    }

    /**
     * Saves the image under a path
     *
     * @param string $filepath
     * @param int $quality 0-100 (only for jpegs)
     *
     * @return ImageEditor
     */
    public function saveAs($filepath, $quality = self::DEFAULT_JPEG_QUALITY)
    {
        switch ($this->format) {
            case self::FORMAT_JPEG:
                ImageJpeg($this->resource, $filepath, $quality);
                break;

            case self::FORMAT_PNG:
                ImagePng($this->resource, $filepath);
                break;

            case self::FORMAT_GIF:
                ImageGif($this->resource, $filepath);
                break;
        }

        $this->filepath = $filepath;

        return $this;
    }

    /**
     * Outputs the image with the correct header.
     *
     * @param int $quality 0-100 (only for jpegs)
     */
    public function output($quality = self::DEFAULT_JPEG_QUALITY)
    {
        header('Content-type: '.$this->mimetype);
        switch ($this->format) {
            case self::FORMAT_JPEG:
                ImageJpeg($this->resource, null, $quality);
                break;

            case self::FORMAT_PNG:
                ImagePng($this->resource);
                break;

            case self::FORMAT_GIF:
                ImageGif($this->resource);
                break;
        }
    }

    /* -------- */

    /**
     * Retrieves format, width and height.
     */
    private function extractFileData()
    {
        if (!$info = getimagesize($this->filepath)) {
            throw new \Exception('Wrong file type');
        }

        // get type
        $this->mimetype = $info['mime'];
        $mt = strtolower($this->mimetype);

        if (stristr($mt, 'gif')) {
            $this->format = self::FORMAT_GIF;
        } elseif (stristr($mt, 'jpg') || stristr($mt, 'jpeg')) {
            $this->format = self::FORMAT_JPEG;
        } elseif (stristr($mt, 'png')) {
            $this->format = self::FORMAT_PNG;
        } else {
            throw new \Exception('Wrong file type');
        }

        // get dimensions
        $this->width  = $info[0];
        $this->height = $info[1];
    }

    private function createResource()
    {
        switch ($this->format) {
            case self::FORMAT_GIF:
                $this->setResource(imagecreatefromgif($this->filepath));
                break;
            case self::FORMAT_JPEG:
                $this->setResource(imagecreatefromjpeg($this->filepath));
                break;
            case self::FORMAT_PNG:
                $this->setResource(imagecreatefrompng($this->filepath));
                break;
        }
    }

    /**
     * @param resource $resource
     * @param int $width
     * @param int $height
     */
    private function setResource($resource)
    {
        if (is_resource($this->resource)) {
            imagedestroy($this->resource);
        }

        $this->width = imagesx($resource);
        $this->height = imagesy($resource);

        imagealphablending($resource, false);
        imagesavealpha($resource, true);

        $this->resource = $resource;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return resource
     */
    private function createImage($width, $height)
    {
        $resource = imagecreatetruecolor($width, $height);

        imagealphablending($resource, false);
        imagesavealpha($resource, true);

        return $resource;
    }

    /* -------- GETTER / SETTERS -------- */

    /**
     * Gets format of this image [GIF|JPEG|PNG]
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Gets mimetype
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimetype;
    }

    /**
     * Returns the width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Returns the height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }
}
