<?php
namespace Gwa\Image;

use Gwa\Exception\gwCoreException;

/**
 * @brief Class containing methods for editing images.
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
    private $workingcopy;

    const DEFAULT_JPEG_QUALITY = 80;

    /**
     * @brief Constuctor
     *
     * @param string $filepath Path to an existing image
     * @throws InvalidArgumentException if not an image
     */
    public function __construct($filepath)
    {
        // make sure the GD library is installed
        if (!function_exists('gd_info')) {
            trigger_error('Gwa/Util/gwImageEditor: You do not have the GD Library installed.');
        }
        if (!file_exists($filepath)) {
            throw new gwCoreException(gwCoreException::ERR_INVALID_ARGUMENT, 'File does not exist: '.$filepath);
        }
        if (!is_readable($filepath)) {
            throw new gwCoreException(gwCoreException::ERR_INVALID_ARGUMENT, 'File is not readable: '.$filepath);
        }
        $this->filepath = $filepath;
        $this->getOriginalFileData();
        $this->createWorkingCopy();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (is_resource($this->workingcopy)) {
            ImageDestroy($this->workingcopy);
        }
    }

    /* -------- PUBLIC METHODS -------- */

    /**
     * @brief Resizes image to be within a maximum width and a maximum height
     *
     * @param int $maxwidth
     * @param int $maxheight
     * @param bool $aspectratio keep aspect ratio when scaling - image may be cropped
     * @return bool image resized?
     */
    public function resize($maxwidth, $maxheight, $aspectratio = true)
    {
        if ($this->width < $maxwidth && $this->height < $maxheight) {
            return false;
        }
        if ($aspectratio) {
            $ratio = $maxwidth / $this->width;
            if ($this->height*$ratio > $maxheight) {
                $ratio = $maxheight / $this->height;
            }
            $newwidth = round($this->width * $ratio);
            $newheight = round($this->height * $ratio);
        } else {
            $newwidth = $maxwidth;
            $newheight = $maxheight;
        }

        $newimage = $this->createImage($newwidth, $newheight);
        imageCopyResampled(
            $newimage,
            $this->workingcopy,
            0,
            0,
            0,
            0,
            $newwidth,
            $newheight,
            $this->width,
            $this->height
        );
        $this->setWorkingCopy($newimage, $newwidth, $newheight);
        return $this;
    }

    /**
     * @brief Resizes image to an exact width and height, maintaining aspect ratio. Any overhang is cropped.
     *
     * @param int $width
     * @param int $height
     * @return bool resized or not
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
            $this->workingcopy,
            0,
            0,
            0,
            0,
            $newwidth,
            $newheight,
            $this->width,
            $this->height
        );

        $this->setWorkingCopy($newimage, $newwidth, $newheight);

        if ($overhang) {
            // do the crop
            $this->cropFromCenter($width, $height);
        }

        return $this;
    }

    /**
     * @brief Crops the current image
     *
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function crop($x, $y, $width, $height)
    {
        // check that crop is within bounds of image
        //
        if ($x+$width>$this->width || $y+$height>$this->height) {
            return false;
        }

        $newwidth = $width;
        $newheight = $height;
        $newimage = $this->createImage($newwidth, $newheight);
        imageCopy(
            $newimage,
            $this->workingcopy,
            0,
            0,
            $x,
            $y,
            $newwidth,
            $newheight
        );

        $this->setWorkingCopy($newimage, $newwidth, $newheight);

        return $this;
    }

    /**
     * @brief Crops the image from the center
     *
     * @param int $width
     * @param int $height if omitted, same as height
     * @return bool
     */
    public function cropFromCenter($width, $height = 0)
    {
        if (!$height) {
            $height = $width;
        }

        $x = ($this->width/2) - ($width/2);
        $y = ($this->height/2) - ($height/2);

        return $this->crop($x, $y, $width, $height);
    }

    /**
     * @brief Paste another image onto this one.
     * @note Basically a wrapper method for http://www.php.net/manual/en/function.imagecopyresampled.php
     *
     * @param ImageEditor $imageeditor
     */
    public function pasteImage(
        ImageEditor $imageeditor,
        $dst_x = 0,
        $dst_y = 0,
        $src_x = 0,
        $src_y = 0,
        $dst_w = null,
        $dst_h = null,
        $src_w = null,
        $src_h = null
    ) {
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
        imagecopyresampled(
            $this->workingcopy,
            $imageeditor->getWorkingCopy(),
            $dst_x,
            $dst_y,
            $src_x,
            $src_y,
            $dst_w,
            $dst_h,
            $src_w,
            $src_h
        );
    }

    /**
     * @brief Saves the image
     *
     * @param string $filepath
     * @param int $quality 0-100 (only for jpegs)
     */
    public function save($filepath = '', $quality = self::DEFAULT_JPEG_QUALITY)
    {
        if (!$filepath) {
            $filepath = $this->filepath;
        }

        switch ($this->_format) {
            case 'JPG':
                ImageJpeg($this->workingcopy, $filepath, $quality);
                break;

            case 'PNG':
                ImagePng($this->workingcopy, $filepath);
                break;

            case 'GIF':
                ImageGif($this->workingcopy, $filepath);
                break;
        }

        return $this;
    }

    /**
     * @brief Shows the image
     *
     * @param int $quality 0-100 (only for jpegs)
     */
    public function show($quality = self::DEFAULT_JPEG_QUALITY)
    {
        header('Content-type: '.$this->mimetype);
        switch ($this->_format) {
            case 'JPG':
                ImageJpeg($this->workingcopy, null, $quality);
                break;

            case 'PNG':
                ImagePng($this->workingcopy);
                break;

            case 'GIF':
                ImageGif($this->workingcopy);
                break;
        }
    }

    /* -------- PRIVATE METHODS -------- */

    /**
     * @access private
     */
    private function getOriginalFileData()
    {
        if (!$info = getimagesize($this->filepath)) {
            throw new gwCoreException(
                gwCoreException::ERR_INVALID_ARGUMENT,
                'Wrong file type: '.$this->filepath
            );
        }

        // get type
        $this->mimetype = $info['mime'];
        $mt = strtolower($this->mimetype);

        if (stristr($mt, 'gif')) {
            $this->_format = 'GIF';
        } elseif (stristr($mt, 'jpg') || stristr($mt, 'jpeg')) {
            $this->_format = 'JPG';
        } elseif (stristr($mt, 'png')) {
            $this->_format = 'PNG';
        } else {
            throw new gwCoreException(
                gwCoreException::ERR_INVALID_ARGUMENT,
                $this->filepath.' : '.$this->mimetype
            );
        }

        // get dimensions
        $this->width = $info[0];
        $this->height = $info[1];
    }

    /**
     * @access private
     */
    private function createWorkingCopy()
    {
        switch ($this->_format) {
            case 'GIF':
                $this->workingcopy = ImageCreateFromGif($this->filepath);
                break;
            case 'JPG':
                $this->workingcopy = ImageCreateFromJpeg($this->filepath);
                break;
            case 'PNG':
                $this->workingcopy = ImageCreateFromPng($this->filepath);
                break;
        }
    }

    /**
     * @access private
     */
    private function setWorkingCopy($image, $width, $height)
    {
        if (is_resource($this->workingcopy)) {
            imagedestroy($this->workingcopy);
        }
        $this->workingcopy = $image;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @access private
     *
     * @param int $width
     * @param int $height
     */
    private function createImage($width, $height)
    {
        if (function_exists('ImageCreateTrueColor')) {
            return ImageCreateTrueColor($width, $height);
        } else {
            return ImageCreate($width, $height);
        }
    }

    /* -------- GETTER / SETTERS -------- */

    /**
     * @brief Gets format of this image [GIF|JPEG|PNG]
     *
     * @return string [GIF|JPG|PNG]
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * @brief Gets mimetype
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimetype;
    }

    /**
     * @brief Returns the width.
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @brief Returns the height.
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    public function getWorkingCopy()
    {
        return $this->workingcopy;
    }
}
