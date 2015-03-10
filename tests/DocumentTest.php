<?php
namespace Box\View\Tests;

use \Mockery as m;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->requestMock = m::mock('\Box\View\Request');
        \Box\View\Document::setRequestHandler($this->requestMock);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testDelete()
    {
        $id = 123;

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id, null, null, [
                   'httpMethod'  => 'DELETE',
                   'rawResponse' => true,
               ])
             ->andReturnNull();

        $deleted = \Box\View\Document::delete($id);
        $this->assertTrue($deleted);
    }

    public function testDeleteWithNonExistantFile()
    {
        $id = 123;

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id, null, null, [
                   'httpMethod'  => 'DELETE',
                   'rawResponse' => true,
               ])
             ->andThrow('Box\View\Exception');

        try {
            $deleted = \Box\View\Document::delete($id);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
        }
    }

    public function testDefaultDownload()
    {
        $id      = 123;
        $content = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id . '/content', null, null, [
                   'rawResponse' => true,
               ])
             ->andReturn('123456789');

        $response = \Box\View\Document::download($id);
        $this->assertEquals($content, $response);
    }

    public function testDownloadWithExtension()
    {
        $id      = 123;
        $content = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id . '/content.pdf', null, null, [
                   'rawResponse' => true,
               ])
             ->andReturn('123456789');

        $response = \Box\View\Document::download($id, 'pdf');
        $this->assertEquals($content, $response);
    }

    public function testDownloadWithWrongExtension()
    {
        $id       = 123;
        $expected = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id . '/content.pdf2', null, null, [
                   'rawResponse' => true,
               ])
             ->andThrow('Box\View\Exception');

        try {
            $response = \Box\View\Document::download($id, 'pdf2');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
        }
    }

    public function testDefaultList()
    {
        $documents = $this->_getTestDocuments();

        $this->requestMock
             ->shouldReceive('send')
             ->with('', null, null, null)
             ->andReturn($documents);

        $response = \Box\View\Document::listDocuments();
        $this->assertSame($documents, $response);
    }

    public function testListWithLimit()
    {
        $limit     = 1;
        $documents = $this->_getTestDocuments($limit);

        $this->requestMock
             ->shouldReceive('send')
             ->with('', [
                   'limit' => $limit,
               ], null, null)
             ->andReturn($documents);

        $response = \Box\View\Document::listDocuments([
            'limit' => $limit,
        ]);
        $this->assertSame($documents, $response);
        $this->assertCount($limit, $response);
    }

   public function testListWithOptions()
    {
        $limit         = 1;
        $createdAfter  = date('r', strtotime('-2 weeks'));
        $createdBefore = date('r', strtotime('-1 week'));
        $documents     = $this->_getTestDocuments($limit);

        $this->requestMock
             ->shouldReceive('send')
             ->with('', [
                   'limit'          => $limit,
                   'created_before' => \Box\View\Document::date($createdBefore),
                   'created_after'  => \Box\View\Document::date($createdAfter),
               ], null, null)
             ->andReturn($documents);

        $response = \Box\View\Document::listDocuments([
            'limit'          => 1,
             'createdAfter'  => $createdAfter,
             'createdBefore' => $createdBefore,
        ]);
        $this->assertSame($documents, $response);
        $this->assertCount($limit, $response);
    }

    public function testMetadata()
    {
        $id       = '8db7bd32e40d48adac24b3c955f49e23';
        $document = $this->_getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id, [
                   'fields' => 'id,type,status,name,created_at',
               ], null, null)
             ->andReturn($document);

        $response = \Box\View\Document::metadata($id, [
            'id',
            'type',
            'status',
            'name',
            'created_at',
        ]);
        $this->assertEquals($id, $response['id']);
        $this->assertSame($document, $response);
    }

    public function testMetadataWithNonExistantFile()
    {
        $id       = '123';
        $document = $this->_getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with('/' . $id, [
                   'fields' => 'id,type,status,name,created_at',
               ], null, null)
             ->andThrow('Box\View\Exception');

        try {
            $response = \Box\View\Document::metadata($id, [
                'id',
                'type',
                'status',
                'name',
                'created_at',
            ]);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
        }
    }

    public function testThumbnail()
    {
        $id      = 123;
        $content = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   '/' . $id . '/thumbnail',
                   [
                       'height' => 100,
                       'width'  => 100,
                   ],
                   null,
                   [
                       'rawResponse' => true,
                   ]
               )
             ->andReturn('123456789');

        $response = \Box\View\Document::thumbnail($id, 100, 100);
        $this->assertEquals($content, $response);
    }

    public function testThumbnailNonExistantFile()
    {
        $id      = 123;
        $content = '123456789';

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   '/' . $id . '/thumbnail',
                   [
                       'height' => 100,
                       'width'  => 100,
                   ],
                   null,
                   [
                       'rawResponse' => true,
                   ]
               )
             ->andThrow('Box\View\Exception');

        try {
            $response = \Box\View\Document::thumbnail($id, 100, 100);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
        }
    }

    public function testUpdate()
    {
        $id      = 123;
        $newName = 'Updated Name';

        $document         = $this->_getTestDocument();
        $document['name'] = $newName;

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   '/' . $id,
                   null,
                   [
                       'name' => $newName,
                   ],
                   [
                       'httpMethod' => 'PUT',
                   ]
               )
             ->andReturn($document);

        $response = \Box\View\Document::update($id, [
            'name' => $newName,
        ]);
        $this->assertEquals($newName, $response['name']);
        $this->assertSame($document, $response);
    }

    public function testDefaultUploadFile()
    {
        $filename = __DIR__ . '/../examples/files/sample.doc';
        $handle   = fopen($filename, 'r');
        $document = $this->_getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with(null, null, null, [
                   'file' => $handle,
                   'host' => 'upload.view-api.box.com',
               ])
             ->andReturn($document);

        $response = \Box\View\Document::uploadFile($handle);
        $this->assertSame($document, $response);
    }

    public function testUploadFileWithInvalidFile()
    {
        $handle = null;

        try {
            $response = \Box\View\Document::uploadFile($handle);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
            $this->assertEquals('invalid_file', $e->errorCode);

            $message = '$file is not a valid file resource.';
            $this->assertEquals($message, $e->getMessage());
        }
    }

    public function testUploadFileWithParams()
    {
        $filename = __DIR__ . '/../examples/files/sample.doc';
        $handle   = fopen($filename, 'r');
        $newName  = 'Updated Name';

        $document         = $this->_getTestDocument();
        $document['name'] = $newName;

        $this->requestMock
             ->shouldReceive('send')
             ->with(
                   null,
                   null,
                   [
                       'name' => $newName,
                   ],
                   [
                       'file' => $handle,
                       'host' => 'upload.view-api.box.com',
                   ]
               )
             ->andReturn($document);

        $response = \Box\View\Document::uploadFile($handle, [
            'name' => $newName,
        ]);
        $this->assertSame($document, $response);
        $this->assertEquals($newName, $response['name']);
    }

    public function testDefaultUploadUrl()
    {
        $url      = 'http://crocodoc.github.io/php-box-view/examples/files/sample.doc';
        $document = $this->_getTestDocument();

        $this->requestMock
             ->shouldReceive('send')
             ->with(null, null, [
                   'url' => $url,
               ], null)
             ->andReturn($document);

        $response = \Box\View\Document::uploadUrl($url);
        $this->assertSame($document, $response);
    }

    public function testUploadUrlWithInvalidFile()
    {
        $url = 'http://foo.bar';

        $this->requestMock
             ->shouldReceive('send')
             ->with(null, null, [
                   'url' => $url,
               ], null)
             ->andThrow('Box\View\Exception');

        try {
            $response = \Box\View\Document::uploadUrl($url);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Box\View\Exception', $e);
        }
    }

    public function testUploadUrlWithParams()
    {
        $url     = 'http://crocodoc.github.io/php-box-view/examples/files/sample.doc';
        $newName = 'Updated Name';

        $document         = $this->_getTestDocument();
        $document['name'] = $newName;

        $this->requestMock
             ->shouldReceive('send')
             ->with(null, null, [
                   'name' => $newName,
                   'url'  => $url,
               ], null)
             ->andReturn($document);

        $response = \Box\View\Document::uploadUrl($url, [
            'name' => $newName,
        ]);
        $this->assertSame($document, $response);
        $this->assertEquals($newName, $response['name']);
    }

    private function _getTestDocument()
    {
        $documents = $this->_getTestDocuments(1);
        return $documents['document_collection']['entries'][0];
    }

    private function _getTestDocuments($limit = null)
    {
        $documents = [
            'document_collection' => [
                'total_count' => 2,
                'entries'     => [
                    [
                        'type'       => 'document',
                        'id'         => '8db7bd32e40d48adac24b3c955f49e23',
                        'status'     => 'processing',
                        'name'       => 'Sample File #2',
                        'created_at' => '2015-02-02T09:13:20Z',
                    ],
                    [
                        'type'       => 'document',
                        'id'         => 'ee7ae7e2ff8d44fca84471d42d74006e',
                        'status'     => 'done',
                        'name'       => 'Sample File',
                        'created_at' => '2015-02-02T09:13:19Z',
                    ],
                ],
            ],
        ];

        if ($limit) {
            $documents = array_slice($documents, 0, $limit);
        }

        return $documents;
    }
}
