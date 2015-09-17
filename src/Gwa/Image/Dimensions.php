<?php
namespace Gwa\Image;

/**
 * Calculates new image dimensions
 */
class Dimensions
{
    /**
     * @param int $maxwidth
     * @param int $maxheight
     * @return \stdClass
     */
    public function resizeToWithin($width, $height, $maxwidth, $maxheight)
    {
        if ($width <= $maxwidth && $height <= $maxheight) {
            return null;
        }

        // calculate ratio based on widths
        $ratio = $maxwidth / $width;
        if ($height * $ratio > $maxheight) {
            // new height greater than maximum
            $ratio = $maxheight / $height;
        }

        $dimensions = new \stdClass;
        $dimensions->width = max(1, round($width * $ratio));
        $dimensions->height = max(1, round($height * $ratio));

        return $dimensions;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int|null $newwidth
     * @param int|null $newheight
     * @return \stdClass|null
     */
    public function resizeTo($width, $height, $newwidth, $newheight)
    {
        if ($width === $newwidth && $height === $newheight) {
            return null;
        }

        if (!$newheight) {
            return $this->resizeToWidth($width, $height, $newwidth);
        }

        if (!$newwidth) {
            return $this->resizeToHeight($width, $height, $newheight);
        }

        $ratio = $newwidth / $width;

        $dimensions = new \stdClass;
        $dimensions->overhang = false;
        $dimensions->width = $newwidth;
        $dimensions->height = $newheight;

        if ($height * $ratio < $newheight) {
            // - height is too small
            // - resize to height, and crop horizontal overhang
            $ratio = $newheight / $height;
            $dimensions->width = max(1, round($width * $ratio));
            $dimensions->height = $newheight;
            $dimensions->overhang = true;
        } elseif ($height * $ratio > $newheight) {
            // - height is too large
            // - resize to width, and crop vertical overhang
            $dimensions->width = $newwidth;
            $dimensions->height = max(1, round($height * $ratio));
            $dimensions->overhang = true;
        }

        return $dimensions;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $newwidth
     * @return \stdClass|null
     */
    private function resizeToWidth($width, $height, $newwidth)
    {
        if ($width === $newwidth) {
            return null;
        }

        $ratio = $newwidth / $width;

        $dimensions = new \stdClass;
        $dimensions->width = $newwidth;
        $dimensions->height = max(1, round($height * $ratio));
        $dimensions->overhang = false;

        return $dimensions;
    }

    /**
     * @param int $width
     * @param int $height
     * @param int $newheight
     * @return \stdClass
     */
    private function resizeToHeight($width, $height, $newheight)
    {
        if ($height === $newheight) {
            return null;
        }

        $ratio = $newheight / $height;

        $dimensions = new \stdClass;
        $dimensions->width = max(1, round($width * $ratio));
        $dimensions->height = $newheight;
        $dimensions->overhang = false;

        return $dimensions;
    }
}
