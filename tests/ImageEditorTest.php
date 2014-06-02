<?php
require_once __DIR__.'/../src/Gwa/Util/ImageEditor.php';

use \Gwa\Util\ImageEditor;

class ImageEditorTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertInstanceOf('\Gwa\Util\ImageEditor', $editor);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNotExistException()
    {
        $editor = new ImageEditor(__DIR__.'/assets/notexist.jpeg');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrongTypeException()
    {
        $editor = new ImageEditor(__DIR__.'/assets/dummy.txt');
    }

    public function testFormat()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertEquals('JPG', $editor->getFormat());
        $this->assertEquals('image/jpeg', $editor->getMimeType());
    }

    public function testDimensions()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertEquals(256, $editor->getWidth());
        $this->assertEquals(256, $editor->getHeight());
    }

    public function testResize()
    {
        $editor = new ImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resize(100, 100);
        $this->assertEquals(100, $editor->getWidth());
        $this->assertEquals(100, $editor->getHeight());
    }
}
