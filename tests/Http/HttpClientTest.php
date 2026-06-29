<?php

namespace MediaEmbed\Test\Http;

use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\Http\StreamHttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase {

	public function testStreamHttpClientImplementsInterface(): void {
		$client = new StreamHttpClient();

		$this->assertInstanceOf(HttpClientInterface::class, $client);
	}

	public function testStreamHttpClientWithCustomTimeout(): void {
		$client = new StreamHttpClient(10);

		$this->assertInstanceOf(StreamHttpClient::class, $client);
	}

	public function testStreamHttpClientWithCustomMaxBytes(): void {
		$client = new StreamHttpClient(10, 1024);

		$this->assertInstanceOf(StreamHttpClient::class, $client);
	}

	public function testStreamHttpClientWithRedirectAndUserAgentOptions(): void {
		$client = new StreamHttpClient(10, 1024, 2, 'media-embed-test');

		$this->assertInstanceOf(StreamHttpClient::class, $client);
	}

	public function testRedirectUrlResolvesAbsoluteLocation(): void {
		$result = $this->redirectUrl('https://example.com/video', [
			'HTTP/1.1 302 Found',
			'Location: https://cdn.example.com/oembed',
		]);

		$this->assertSame('https://cdn.example.com/oembed', $result);
	}

	public function testRedirectUrlResolvesRootRelativeLocation(): void {
		$result = $this->redirectUrl('https://example.com/videos/123', [
			'HTTP/1.1 301 Moved Permanently',
			'Location: /oembed',
		]);

		$this->assertSame('https://example.com/oembed', $result);
	}

	public function testRedirectUrlResolvesPathRelativeLocation(): void {
		$result = $this->redirectUrl('https://example.com/videos/123', [
			'HTTP/1.1 307 Temporary Redirect',
			'Location: oembed',
		]);

		$this->assertSame('https://example.com/videos/oembed', $result);
	}

	public function testRedirectUrlReturnsNullForNonRedirectResponse(): void {
		$result = $this->redirectUrl('https://example.com/videos/123', [
			'HTTP/1.1 200 OK',
			'Location: https://example.com/oembed',
		]);

		$this->assertNull($result);
	}

	public function testHeaderValueStripsLineBreaks(): void {
		$client = new class () extends StreamHttpClient {

			public function testHeaderValue(string $value): string {
				return $this->headerValue($value);
			}

		};

		$this->assertSame('media-embedInjected: header', $client->testHeaderValue("media-embed\r\nInjected: header"));
	}

	public function testGetReturnsNullForUnsafeUrls(): void {
		$client = new StreamHttpClient(1);

		$this->assertNull($client->get('file:///tmp/test.txt'));
		$this->assertNull($client->get('javascript:alert(1)'));
		$this->assertNull($client->get('http://localhost/test'));
		$this->assertNull($client->get('http://127.0.0.1/test'));
		$this->assertNull($client->get('http://10.0.0.1/test'));
		$this->assertNull($client->get('http://169.254.169.254/test'));
		$this->assertNull($client->get('http://[::1]/test'));
		$this->assertNull($client->get('http://[fd00::1]/test'));
		$this->assertNull($client->get('http://2130706433/test'));
		$this->assertNull($client->get('http://0177.0.0.1/test'));
		$this->assertNull($client->get('http://0x7f000001/test'));
	}

	public function testGetReturnsNullForInvalidUrl(): void {
		$client = new StreamHttpClient(1);

		$result = $client->get('https://invalid.nonexistent.example.com/page');

		$this->assertNull($result);
	}

	/**
	 * @param string $url
	 * @param array<int, string> $headers
	 */
	private function redirectUrl(string $url, array $headers): ?string {
		$client = new class () extends StreamHttpClient {

			/**
			 * @param string $url
			 * @param array<int, string> $headers
			 */
			public function testRedirectUrl(string $url, array $headers): ?string {
				return $this->redirectUrl($url, $headers);
			}

		};

		return $client->testRedirectUrl($url, $headers);
	}

}
