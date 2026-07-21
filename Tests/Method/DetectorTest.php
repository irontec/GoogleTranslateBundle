<?php

namespace Eko\GoogleTranslateBundle\Tests\Method;

use Eko\GoogleTranslateBundle\Translate\Method\Detector;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Detector class test.
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class DetectorTest extends TestCase
{
    /**
     * @var Detector Detector service
     */
    protected $detector;

    /**
     * @var \GuzzleHttp\Message\Response mock
     */
    protected $responseMock;

    /**
     * Set up methods services.
     */
    protected function setUp(): void
    {
        $this->detector = new Detector('fakeapikey', $this->getClientMock());
    }

    /**
     * Test simple detect method.
     */
    public function testSimpleDetect()
    {
        // Given
        $this->responseMock->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['detections' => [[['language' => 'en']]]]],
            JSON_UNESCAPED_UNICODE
        )));

        // When
        $language = $this->detector->detect('hi');

        // Then
        $this->assertEquals('en', $language, 'Should return language "en"');
    }

    /**
     * Test exception detect method.
     */
    public function testExceptionDetect()
    {
        $this->responseMock->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['detections' => [[['language' => Detector::UNDEFINED_LANGUAGE]]]]],
            JSON_UNESCAPED_UNICODE
        )));

        $this->expectException('Eko\GoogleTranslateBundle\Exception\UnableToDetectException');

        $this->detector->detect('undefined');
    }

    /**
     * Returns Guzzle HTTP client mock and sets response mock property.
     *
     * @return ClientInterface
     */
    protected function getClientMock()
    {
        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->responseMock = $this->createMock(ResponseInterface::class);

        $clientMock->method('get')->willReturn($this->responseMock);

        return $clientMock;
    }
}
