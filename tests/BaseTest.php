<?php
namespace Box\View\Tests;

use \Mockery as m;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGetRequestHandler()
    {
        $handler = \Box\View\Base::getRequestHandler();
        $this->assertInstanceOf('\Box\View\Request', $handler);
    }

    public function testSetRequestHandler()
    {
        $type              = '\Box\View\RequestFoo';
        $this->requestMock = m::mock($type);
        \Box\View\Base::setRequestHandler($this->requestMock);

        $handler = \Box\View\Base::getRequestHandler();
        $this->assertInstanceOf($type, $handler);
    }
}
