<?php
use Gwa\Image\ImageEditor;

use Gwa\Exception\gwCoreException;

class ImageEditorTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertInstanceOf('\Gwa\Image\ImageEditor', $editor);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotExistException()
    {
        $editor = new ImageEditor(__DIR__.'/assets/notexist.jpeg');
    }

    /**
     * @expectedException \Exception
     */
    public function testWrongTypeException()
    {
        $editor = new ImageEditor(__DIR__.'/assets/dummy.txt');
    }

    public function testFormatJPEG()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertEquals(ImageEditor::FORMAT_JPEG, $editor->getFormat());
        $this->assertEquals('image/jpeg', $editor->getMimeType());
    }

    public function testFormatPNG()
    {
        // Yosemite doesn't have PNG support out of the box!
        if (!function_exists('imagecreatefrompng')) {
            $this->markTestSkipped(
                'imagecreatefrompng() is not available.'
            );
            return;
        }
        $editor = new ImageEditor(__DIR__.'/assets/awesome.png');
        $this->assertEquals(ImageEditor::FORMAT_PNG, $editor->getFormat());
        $this->assertEquals('image/png', $editor->getMimeType());
    }

    public function testFormatGIF()
    {
        $editor = new ImageEditor(__DIR__.'/assets/test.gif');
        $this->assertEquals(ImageEditor::FORMAT_GIF, $editor->getFormat());
        $this->assertEquals('image/gif', $editor->getMimeType());
    }

    public function testDimensions()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertEquals(256, $editor->getWidth());
        $this->assertEquals(256, $editor->getHeight());
    }

    public function testResizeToWithinAlreadyWithin()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeToWithin(300, 300);
        $this->assertEquals(256, $editor->getWidth());
        $this->assertEquals(256, $editor->getHeight());
    }

    public function testResizeToWithinAlreadyExactDimensions()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeToWithin(256, 256);
        $this->assertEquals(256, $editor->getWidth());
        $this->assertEquals(256, $editor->getHeight());
    }

    public function testResizeToWithin()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeToWithin(100, 100);
        $this->assertEquals(100, $editor->getWidth());
        $this->assertEquals(100, $editor->getHeight());
    }

    public function testResizeToWithinPNG()
    {
        $editor = new ImageEditor(__DIR__.'/assets/awesome.png');
        $editor->resizeToWithin(100, 100)
            ->saveAs(__DIR__.'/output/awesome_100.png');

        $editor = new ImageEditor(__DIR__.'/output/awesome_100.png');
        $this->assertEquals(100, $editor->getWidth());
        $this->assertEquals(100, $editor->getHeight());
    }

    public function testResizeToWithinLimitToHeight()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeToWithin(100, 50);
        $this->assertEquals(50, $editor->getWidth());
        $this->assertEquals(50, $editor->getHeight());
    }

    public function testResizeToAlreadyExactDimensions()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeTo(256, 256);
        $this->assertEquals(256, $editor->getWidth());
        $this->assertEquals(256, $editor->getHeight());
    }

    public function testResizeToNoOverhang()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeTo(100, 100);
        $this->assertEquals(100, $editor->getWidth());
        $this->assertEquals(100, $editor->getHeight());
    }

    public function testResizeToWidthOverhang()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeTo(50, 100);
        $this->assertEquals(50, $editor->getWidth());
        $this->assertEquals(100, $editor->getHeight());
    }

    public function testResizeToHeightOverhang()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resizeTo(100, 50);
        $this->assertEquals(100, $editor->getWidth());
        $this->assertEquals(50, $editor->getHeight());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCropOutOfBounds()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->crop(100, 100, 200, 200);
    }

    public function testCrop()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->crop(0, 0, 128, 128)
            ->saveAs(__DIR__.'/output/octopus_crop.jpeg');

        $editor = new ImageEditor(__DIR__.'/output/octopus_crop.jpeg');
        $this->assertEquals(128, $editor->getWidth());
        $this->assertEquals(128, $editor->getHeight());
    }

    public function testCropFromCenter()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->cropFromCenter(128, 128)
            ->saveAs(__DIR__.'/output/octopus_cropcenter.jpeg');

        $editor = new ImageEditor(__DIR__.'/output/octopus_cropcenter.jpeg');
        $this->assertEquals(128, $editor->getWidth());
        $this->assertEquals(128, $editor->getHeight());
    }

    public function testRotateClockwise()
    {
        $editor = new ImageEditor(__DIR__.'/assets/adium.png');
        $editor->rotateClockwise()
            ->saveAs(__DIR__.'/output/adium_cw.png');

        $editor = new ImageEditor(__DIR__.'/output/adium_cw.png');
        $this->assertEquals(512, $editor->getWidth());
        $this->assertEquals(360, $editor->getHeight());
    }

    public function testRotateCounterClockwise()
    {
        $editor = new ImageEditor(__DIR__.'/assets/adium.png');
        $editor->rotateCounterClockwise()
            ->saveAs(__DIR__.'/output/adium_ccw.png');

        $editor = new ImageEditor(__DIR__.'/output/adium_ccw.png');
        $this->assertEquals(512, $editor->getWidth());
        $this->assertEquals(360, $editor->getHeight());
    }

    public function testRotate180()
    {
        $editor = new ImageEditor(__DIR__.'/assets/adium.png');
        $editor->rotate180()
            ->saveAs(__DIR__.'/output/adium_180.png');
    }

    public function testPasteImage()
    {
        $editor = new ImageEditor(__DIR__.'/assets/adium.png');
        $editor->pasteImage(__DIR__.'/assets/awesome.png')
            ->saveAs(__DIR__.'/output/adium_awesome.png');

        $editor = new ImageEditor(__DIR__.'/assets/adium.png');
        $editor->pasteImage(__DIR__.'/assets/awesome.png', 10, 10, 100, 100)
            ->saveAs(__DIR__.'/output/adium_awesome_scaled.png');
    }

    /**
     * @expectedException \LogicException
     */
    public function testExceptionWhenSavingDuplicatedFile()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->duplicate()->save();
    }
}
