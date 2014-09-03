<?php
use Gwa\Image\gwImageEditor;

use Gwa\Exception\gwCoreException;

class gwImageEditorTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $editor = new gwImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertInstanceOf('\Gwa\Image\gwImageEditor', $editor);
    }

    /**
     * @expectedException Gwa\Exception\gwCoreException
     */
    public function testNotExistException()
    {
        $editor = new gwImageEditor(__DIR__.'/assets/notexist.jpeg');
    }

    /**
     * @expectedException Gwa\Exception\gwCoreException
     */
    public function testWrongTypeException()
    {
        $editor = new gwImageEditor(__DIR__.'/assets/dummy.txt');
    }

    public function testFormat()
    {
        $editor = new gwImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertEquals('JPG', $editor->getFormat());
        $this->assertEquals('image/jpeg', $editor->getMimeType());
    }

    public function testDimensions()
    {
        $editor = new gwImageEditor(__DIR__.'/assets/octopus.jpeg');
        $this->assertEquals(256, $editor->getWidth());
        $this->assertEquals(256, $editor->getHeight());
    }

    public function testResize()
    {
        $editor = new gwImageEditor(__DIR__.'/assets/octopus.jpeg');
        $editor->resize(100, 100);
        $this->assertEquals(100, $editor->getWidth());
        $this->assertEquals(100, $editor->getHeight());
    }
}
