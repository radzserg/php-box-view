<?php
namespace Box\View\Tests;

use \Mockery as m;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testSendRequest()
    {
        $request = new \Box\View\Request('foo', 'bar');
        // TODO: figure out how to mock Guzzle so we can test this
    }
}
