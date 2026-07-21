<?php

namespace Eko\GoogleTranslateBundle\Tests\Method;

use Eko\GoogleTranslateBundle\Translate\Method\Detector;
use Eko\GoogleTranslateBundle\Translate\Method\Translator;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Translator class test.
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class TranslatorTest extends TestCase
{
    /**
     * @var Translator Translator service
     */
    protected $translator;

    /**
     * @var \GuzzleHttp\Message\Response mock
     */
    protected $responseMock;

    /**
     * Set up methods services.
     */
    protected function setUp(): void
    {
        $this->translator = new Translator('fakeapikey', $this->getClientMock(), $this->getDetectorMock());
    }

    /**
     * Test simple translate method.
     */
    public function testSimpleTranslate()
    {
        // Given
        $this->responseMock->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['translations' => [['translatedText' => 'salut']]]],
            JSON_UNESCAPED_UNICODE
        )));

        // When
        $value = $this->translator->translate('hi', 'fr', 'en');

        // Then
        $this->assertEquals('salut', $value, 'Should return "salut"');
    }

    /**
     * Test plain text option of the simple translate method.
     */
    public function testPlainTextTranslate()
    {
        // Given
        $this->responseMock->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['translations' => [['translatedText' => "J'ai"]]]],
            JSON_UNESCAPED_UNICODE
        )));

        // When
        $value = $this->translator->translate('I have', 'fr', 'en', true);

        // Then
        $this->assertEquals("J'ai", $value, 'Should return "J\'ai"');
    }

    /**
     * Test multiple translate method using an array.
     */
    public function testMultipleTranslate()
    {
        // Given
        $this->responseMock->method('getBody')->willReturnOnConsecutiveCalls(
            Utils::streamFor(json_encode(['data' => ['translations' => [['translatedText' => 'salut']]]], JSON_UNESCAPED_UNICODE)),
            Utils::streamFor(json_encode(['data' => ['translations' => [['translatedText' => 'salut']]]], JSON_UNESCAPED_UNICODE))
        );

        // When
        $values = $this->translator->translate(['hi', 'hi'], 'fr', 'en');

        // Then
        $this->assertCount(2, $values, 'Should return an array with 2 elements');

        foreach ($values as $value) {
            $this->assertEquals('salut', $value, 'Should return "salut"');
        }
    }

    /**
     * Test multiple translate method using an array and the economic mode.
     */
    public function testMultipleEconomicTranslate()
    {
        // Given
        $this->responseMock->expects($this->once())->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['translations' => [['translatedText' => 'salut # salut']]]],
            JSON_UNESCAPED_UNICODE
        )));

        // When
        $values = $this->translator->translate(['hi', 'hi'], 'fr', 'en', true);

        // Then
        $this->assertCount(2, $values, 'Should return an array with 2 elements');

        foreach ($values as $value) {
            $this->assertEquals('salut', $value, 'Should return "salut"');
        }
    }

    /**
     * Test translate using detector method.
     */
    public function testTranslateUsingDetector()
    {
        // Given
        $this->responseMock->method('getBody')->willReturn(Utils::streamFor(json_encode(
            ['data' => ['translations' => [['translatedText' => 'comment allez-vous ?']]]],
            JSON_UNESCAPED_UNICODE
        )));

        // When
        $value = $this->translator->translate('how are you?', 'fr');

        // Then
        $this->assertEquals('comment allez-vous ?', $value, 'Should return "comment allez-vous ?"');
    }

    /**
     * Test translate method with a text that is too long for a single request.
     */
    public function testLongTranslate()
    {
        // Build a long input text, so that the translate method will split it up in two.
        $text = 'hi. ';
        $multiplier = (int) (1.5 * Translator::MAXIMUM_TEXT_SIZE / strlen($text));
        $textInEn = str_repeat('hi. ', $multiplier);
        $textInFr = str_repeat('salut. ', $multiplier);
        $textInFrPart1 = substr($textInFr, 0, (int) (strlen($textInFr) / 2));
        $textInFrPart2 = substr($textInFr, strlen($textInFrPart1));

        // Given
        $this->responseMock->method('getBody')->willReturnOnConsecutiveCalls(
            Utils::streamFor(json_encode(['data' => ['translations' => [['translatedText' => $textInFrPart1]]]], JSON_UNESCAPED_UNICODE)),
            Utils::streamFor(json_encode(['data' => ['translations' => [['translatedText' => $textInFrPart2]]]], JSON_UNESCAPED_UNICODE))
        );

        // When
        $value = $this->translator->translate($textInEn, 'en');

        // Then
        $this->assertEquals($textInFr, $value, 'Should return "'.$textInFr.'"');
    }

    /**
     * Returns detector service mock.
     *
     * @return Detector
     */
    public function getDetectorMock()
    {
        $detectorMock = $this->getMockBuilder(Detector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $detectorMock->method('detect')->willReturn('en');

        return $detectorMock;
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
