<?php
namespace Box\View\Tests;

use \Mockery as m;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->requestMock = m::mock('\Box\View\Request');
        \Box\View\Session::setRequestHandler($this->requestMock);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testDefaultCreate()
    {
        $id = 123;

        $session = $this->_getTestSession();

        $this->requestMock
             ->shouldReceive('send')
             ->with(null, null, [
                'document_id' => $id,
               ], null)
             ->andReturn($session);

        $response = \Box\View\Session::create($id);
        $this->assertSame($session, $response);
        $this->assertEquals(
            $session['document']['id'],
            $response['document']['id']
        );
    }

    public function testCreateWithNonExistantFile()
    {
        $id = 123;

        $this->requestMock
             ->shouldReceive('send')
             ->with(null, null, [
                'document_id' => $id,
               ], null)
             ->andThrow('Box\View\Exception');

        try {
            $session = \Box\View\Session::create($id);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
        }
    }

    public function testCreateWithOptions()
    {
        $id = 123;
        $date = date('r', strtotime('+10 min'));

        $session = $this->_getTestSession();

        $this->requestMock
             ->shouldReceive('send')
             ->with(null, null, [
                'document_id' => $id,
                'duration' => 10,
                'expires_at' => date('c', strtotime($date)),
                'is_downloadable' => true,
                'is_text_selectable' => false,
               ], null)
             ->andReturn($session);

        $response = \Box\View\Session::create($id, [
            'duration' => 10,
            'expiresAt' => $date,
            'isDownloadable' => true,
            'isTextSelectable' => false,
        ]);
        $this->assertSame($session, $response);
        $this->assertEquals(
            $session['document']['id'],
            $response['document']['id']
        );
    }

    public function testDelete()
    {
        $id = 123;

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id, null, null, [
                'httpMethod' => 'DELETE',
                'rawResponse' => true,
               ])
             ->andReturnNull();

        $deleted = \Box\View\Session::delete($id);
        $this->assertTrue($deleted);
    }

    public function testDeleteWithNonExistantSession()
    {
        $id = 123;

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id, null, null, [
                'httpMethod' => 'DELETE',
                'rawResponse' => true,
               ])
             ->andThrow('Box\View\Exception');

        try {
            $deleted = \Box\View\Session::delete($id);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
        }
    }

    private function _getTestSession()
    {
        return [
            'type' => 'session',
            'id' => 'c3d082985d08425faacb744aa28a8ba3',
            'document' => [
                'type' => 'document',
                'id' => 'f5f342c440b84dcfa4104eaae49cdead',
                'status' => 'done',
                'name' => 'Updated Name',
                'created_at' => '2015-02-02T09:16:19Z',
            ],
            'expires_at' => '2015-02-02T10:16:39.876Z',
            'urls' => [
                'view' => 'https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/view',
                'assets' => 'https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/assets/',
                'realtime' => 'https://view-api.box.com/sse/c3d082985d08425faacb744aa28a8ba3',
            ],
        ];
    }
}
