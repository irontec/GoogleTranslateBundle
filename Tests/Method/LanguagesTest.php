<?php

namespace Eko\GoogleTranslateBundle\Tests\Method;

use Eko\GoogleTranslateBundle\Translate\Method\Languages;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Languages class test.
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class LanguagesTest extends TestCase
{
    /**
     * @var Languages Languages service
     */
    protected $languages;

    /**
     * @var \GuzzleHttp\Message\Response mock
     */
    protected $responseMock;

    /**
     * Set up methods services.
     */
    protected function setUp(): void
    {
        $this->languages = new Languages('fakeapikey', $this->getClientMock());
    }

    /**
     * Test simple get method.
     */
    public function testSimpleGet()
    {
        // Given
        $this->responseMock->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['languages' => [['language' => 'en'], ['language' => 'fr']]]],
            JSON_UNESCAPED_UNICODE
        )));

        // When
        $values = $this->languages->get();

        // Then
        $this->assertCount(2, $values, 'Should return 2 values');

        foreach ($values as $value) {
            $this->assertArrayHasKey('language', $value, 'Should have an array key "language"');
            $this->assertTrue(in_array($value['language'], ['fr', 'en'], 'Language should be "fr" or "en"'));
        }
    }

    /**
     * Test get method with a target parameter.
     */
    public function testGetWithTarget()
    {
        // Given
        $this->responseMock->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['languages' => [
                ['language' => 'en', 'name' => 'Anglais'],
                ['language' => 'fr', 'name' => 'Français'],
            ]]],
            JSON_UNESCAPED_UNICODE
        )));

        // When
        $values = $this->languages->get('fr');

        // Then
        $this->assertCount(2, $values, 'Should return 2 values');

        foreach ($values as $value) {
            $this->assertArrayHasKey('language', $value, 'Should have an array key "language"');
            $this->assertArrayHasKey('name', $value, 'Should have an array key "name"');

            $this->assertTrue(in_array($value['language'], ['fr', 'en'], 'Language should be "fr" or "en"'));
            $this->assertTrue(in_array($value['name'], ['Français', 'Anglais'], 'Language should be "Français" or "Anglais"'));
        }
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
