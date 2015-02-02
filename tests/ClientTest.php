<?php
namespace Box\View\Tests;

use \Mockery as m;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testGetApiKey()
    {
        $apiKey = \Box\View\Client::getApiKey();
        $this->assertNull($apiKey);
    }

    public function testSetApiKey()
    {
        $apiKey = 'foo';
        \Box\View\Client::setApiKey($apiKey);

        $response = \Box\View\Client::getApiKey();
        $this->assertEquals($apiKey, $response);
    }
}
