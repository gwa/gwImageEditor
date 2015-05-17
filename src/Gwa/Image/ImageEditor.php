<?php
namespace Gwa\Image;

/**
 * Class containing methods for editing images.
 */
class ImageEditor
{
    /**
     * @var string
     */
    private $filepath;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @var string
     */
    private $mimetype;

    /**
     * @var resource
     */
    private $resource;

    const DEFAULT_JPEG_QUALITY = 80;

    /**
     * Constuctor
     *
     * @param string $filepath Path to an existing image
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function __construct($filepath)
    {
        // make sure the GD library is installed
        if (!function_exists('gd_info')) {
            trigger_error('You do not have the GD Library installed.');
        }

        if (!file_exists($filepath)) {
            throw new \InvalidArgumentException('File does not exist: ' . $filepath);
        }

        if (!is_readable($filepath)) {
            throw new \Exception('File is not readable: ' . $filepath);
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
            imagedestroy($this->resource);
        }
    }

    /* ---------------- */

    /**
     * Resizes image to be within a maximum width and a maximum height
     *
     * @param int $maxwidth
     * @param int $maxheight
     * @return ImageEditor
     */
    public function resizeToWithin($maxwidth, $maxheight)
    {
        $dimensions = new Dimensions();

        if ($newd = $dimensions->resizeToWithin($this->width, $this->height, $maxwidth, $maxheight)) {
            $this->resizeImage($newd->width, $newd->height);
        }

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
        $dimensions = new Dimensions();

        if ($newd = $dimensions->resizeTo($this->width, $this->height, $width, $height)) {
            $this->resizeImage($newd->width, $newd->height);
            if ($newd->overhang) {
                $this->cropFromCenter($width, $height);
            }
        }

        return $this;
    }

    private function resizeImage($newwidth, $newheight) {
        $newimage = $this->createImage($newwidth, $newheight);
        imagecopyresampled(
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
        if ($x + $width > $this->width || $y + $height > $this->height) {
            throw new \InvalidArgumentException('crop out of bounds');
        }

        $newimage = $this->createImage($width, $height);
        imagecopy(
            $newimage,
            $this->resource,
            0,
            0,
            $x,
            $y,
            $width,
            $height
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
        $x = ($this->width / 2) - ($width / 2);
        $y = ($this->height / 2) - ($height / 2);

        return $this->crop($x, $y, $width, $height);
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

    /* filter -------- */

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
     * @param int $red [0, 255]
     * @param int $green [0, 255]
     * @param int $blue [0, 255]
     * @param int $alpha [0, 127]
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

        if ($src_w === null) {
            $src_w = $imageeditor->getWidth();
        }
        if ($src_h === null) {
            $src_h = $imageeditor->getHeight();
        }
        if ($dst_w === null) {
            $dst_w = $src_w;
        }
        if ($dst_h === null) {
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
        if (!isset($this->filepath)) {
            throw new \LogicException('Use saveTo() to save an unnamed file.');
        }
        return $this->saveAs($this->filepath, $quality);
    }

    /**
     * Saves the image under a path
     *
     * @param string $filepath
     * @param int $type IMAGETYPE constant
     * @param int $quality 0-100 (only for jpegs)
     *
     * @return ImageEditor
     */
    public function saveAs($filepath, $type = null, $quality = self::DEFAULT_JPEG_QUALITY)
    {
        $this->outputImage($filepath, $type, $quality);
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
        header('Content-type: ' . $this->mimetype);
        $this->outputImage(null, $quality);
    }

    /**
     * @param string $filepath
     * @param int $type IMAGETYPE constant
     * @param int $quality 0-100 (only for jpegs)
     */
    private function outputImage($filepath = null, $type = null, $quality = self::DEFAULT_JPEG_QUALITY)
    {
        switch ($type || $this->type) {
            case IMAGETYPE_JPEG:
                imagejpeg($this->resource, $filepath, $quality);
                break;

            case IMAGETYPE_PNG:
                imagepng($this->resource, $filepath);
                break;

            case IMAGETYPE_GIF:
                imagegif($this->resource, $filepath);
                break;
        }
    }

    /* -------- */

    /**
     * Retrieves format.
     */
    private function extractFileData()
    {
        if (!$this->type = exif_imagetype($this->filepath)) {
            throw new \Exception('Wrong file type');
        }

        $mimetypes = array(
            IMAGETYPE_GIF  => 'image/gif',
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG  => 'image/png'
        );

        if (!array_key_exists($this->type, $mimetypes)) {
            throw new \Exception('Unsupported image type');
        }

        $this->mimetype = $mimetypes[$this->type];
    }

    private function createResource()
    {
        switch ($this->type) {
            case IMAGETYPE_GIF:
                $this->setResource(imagecreatefromgif($this->filepath));
                break;
            case IMAGETYPE_JPEG:
                $this->setResource(imagecreatefromjpeg($this->filepath));
                break;
            case IMAGETYPE_PNG:
                $this->setResource(imagecreatefrompng($this->filepath));
                break;
        }
    }

    /**
     * @param resource $resource
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
     * Gets format of this image [IMAGETYPE_GIF|IMAGETYPE_GIF|IMAGETYPE_GIF]
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
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
