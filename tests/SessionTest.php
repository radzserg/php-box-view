<?php
namespace Box\View\Tests;

use \Mockery as m;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $apiKey       = 'abc123';
        $this->client = new \Box\View\Client($apiKey);

        $this->requestMock = m::mock('\Box\View\Request');
        $this->client->setRequestHandler($this->requestMock);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testConstructor()
    {
        $rawSession = $this->getRawTestSession();
        $session    = $this->getTestSession();
        $response   = new \Box\View\Session($this->client, $rawSession);

        $this->assertEquals($session, $response);
    }

    public function testDelete()
    {
        $session = $this->getTestSession();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/sessions/' . $session->id(), null, null, [
                   'httpMethod'  => 'DELETE',
                   'rawResponse' => true,
               ])
             ->andReturnNull();

        $deleted = $session->delete();
        $this->assertTrue($deleted);
    }

    public function testDeleteWithNonExistantSession()
    {
        $session = new \Box\View\Session($this->client, ['id' => 123]);

        $this->requestMock
             ->shouldReceive('send')
             ->with('/sessions/' . $session->id(), null, null, [
                   'httpMethod'  => 'DELETE',
                   'rawResponse' => true,
               ])
             ->andThrow('Box\View\BoxViewException');

        $failed   = false;

        try {
            $deleted = $session->delete();
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);
            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testDocument()
    {
        $rawSession = $this->getRawTestSession();
        $session    = $this->getTestSession();
        $document   = new \Box\View\Document(
            $this->client,
            $rawSession['document']
        );

        $response = $session->document();

        $this->assertEquals($document, $response);
    }

    public function testExpiresAt()
    {
        $rawSession = $this->getRawTestSession();
        $expiresAt  = date('c', strtotime($rawSession['expires_at']));
        $session    = $this->getTestSession();

        $this->assertEquals($expiresAt, $session->expiresAt());
    }

    public function testId()
    {
        $rawSession = $this->getRawTestSession();
        $session    = $this->getTestSession();

        $this->assertEquals($rawSession['id'], $session->id());
    }

    public function testDefaultCreate()
    {
        $rawSession = $this->getRawTestSession();
        $session    = $this->getTestSession();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/sessions', null, [
                   'document_id' => $session->id(),
               ], null)
             ->andReturn($rawSession);

        $response = \Box\View\Session::create($this->client, $session->id());

        $this->assertEquals($session, $response);
        $this->assertEquals(
            $session->document()->id(),
            $response->document()->id()
        );
    }

    public function testCreateWithNonExistantFile()
    {
        $id = 123;

        $this->requestMock
             ->shouldReceive('send')
             ->with('/sessions', null, ['document_id' => $id ], null)
             ->andThrow('Box\View\BoxViewException');

        $failed = false;

        try {
            $session = \Box\View\Session::create($this->client, $id);
        } catch (\Box\View\BoxViewException $e) {
            $this->assertInstanceOf('Box\View\BoxViewException', $e);
            $failed = true;
        }

        $this->assertTrue($failed);
    }

    public function testCreateWithOptions()
    {
        $rawSession = $this->getRawTestSession();
        $session    = $this->getTestSession();
        $date       = date('r', strtotime('+10 min'));

        $this->requestMock
             ->shouldReceive('send')
             ->with('/sessions', null, [
                   'document_id'        => $session->document()->id(),
                   'duration'           => 10,
                   'expires_at'         => date('c', strtotime($date)),
                   'is_downloadable'    => true,
                   'is_text_selectable' => false,
               ], null)
             ->andReturn($rawSession);

        $docId = $session->document()->id();
        $response = \Box\View\Session::create($this->client, $docId, [
            'duration'         => 10,
            'expiresAt'        => $date,
            'isDownloadable'   => true,
            'isTextSelectable' => false,
        ]);

        $this->assertEquals($session, $response);
        $this->assertEquals(
            $session->document()->id(),
            $response->document()->id()
        );
    }

    private function getTestSession()
    {
        $session = $this->getRawTestSession();
        return new \Box\View\Session($this->client, $session);
    }

    private function getRawTestSession()
    {
        return [
            'type'       => 'session',
            'id'         => 'c3d082985d08425faacb744aa28a8ba3',
            'document'   => [
                'type'       => 'document',
                'id'         => 'f5f342c440b84dcfa4104eaae49cdead',
                'status'     => 'done',
                'name'       => 'Updated Name',
                'created_at' => '2015-02-02T09:16:19Z',
            ],
            'expires_at' => '2015-02-02T10:16:39.876Z',
            'urls'       => [
                'view'     => 'https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/view',
                'assets'   => 'https://view-api.box.com/1/sessions/c3d082985d08425faacb744aa28a8ba3/assets/',
                'realtime' => 'https://view-api.box.com/sse/c3d082985d08425faacb744aa28a8ba3',
            ],
        ];
    }
}
