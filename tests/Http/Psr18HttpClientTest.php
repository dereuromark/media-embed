<?php

declare(strict_types=1);

namespace MediaEmbed\Test\Http;

use InvalidArgumentException;
use MediaEmbed\Http\Psr18HttpClient;
use MediaEmbed\MediaEmbed;
use MediaEmbed\Object\MediaObject;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Psr18HttpClientTest extends TestCase {

	public function testReturnsBodyForSuccessfulResponse(): void {
		$client = $this->clientReturning(new Response(200, [], 'hello body'));
		$httpClient = new Psr18HttpClient($client, new Psr17Factory());

		$this->assertSame('hello body', $httpClient->get('https://example.com/x'));
	}

	public function testReturnsNullForErrorStatus(): void {
		$client = $this->clientReturning(new Response(404, [], 'nope'));
		$httpClient = new Psr18HttpClient($client, new Psr17Factory());

		$this->assertNull($httpClient->get('https://example.com/x'));
	}

	public function testReturnsNullWhenClientThrows(): void {
		$client = new class implements ClientInterface {

			public function sendRequest(RequestInterface $request): ResponseInterface {
				throw new class ('boom') extends RuntimeException implements ClientExceptionInterface {
				};
			}

		};
		$httpClient = new Psr18HttpClient($client, new Psr17Factory());

		$this->assertNull($httpClient->get('https://example.com/x'));
	}

	public function testRejectsNonPublicUrlWithoutSendingRequest(): void {
		$client = new class implements ClientInterface {

			public function sendRequest(RequestInterface $request): ResponseInterface {
				throw new RuntimeException('must not be called for unsafe URLs');
			}

		};
		$httpClient = new Psr18HttpClient($client, new Psr17Factory());

		$this->assertNull($httpClient->get('http://127.0.0.1/internal'));
	}

	public function testMediaEmbedRejectsPsrClientWithoutRequestFactory(): void {
		$this->expectException(InvalidArgumentException::class);
		new MediaEmbed(psrHttpClient: $this->clientReturning(new Response(200, [], '')));
	}

	public function testInjectedIntoMediaEmbedResolvesFetchMatchProvider(): void {
		$factory = new Psr17Factory();
		$response = new Response(200, [], '<iframe src="https://www.screencast.com/users/CamtasiaTraining/folders/Camtasia/media/1d44810a-01f4-4c60-a862-6d114bed50c7/embed"></iframe>');
		$client = $this->clientReturning($response);

		$mediaEmbed = new MediaEmbed(psrHttpClient: $client, requestFactory: $factory);
		$object = $mediaEmbed->parseUrl('https://www.screencast.com/t/Hh4ulI0M');

		$this->assertInstanceOf(MediaObject::class, $object);
		$this->assertSame('1d44810a-01f4-4c60-a862-6d114bed50c7', $object->id());
	}

	private function clientReturning(ResponseInterface $response): ClientInterface {
		return new class ($response) implements ClientInterface {

			public function __construct(
				private readonly ResponseInterface $response,
			) {
			}

			public function sendRequest(RequestInterface $request): ResponseInterface {
				return $this->response;
			}

		};
	}

}
