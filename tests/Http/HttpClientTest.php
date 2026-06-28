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

	public function testGetReturnsNullForUnsafeUrls(): void {
		$client = new StreamHttpClient(1);

		$this->assertNull($client->get('file:///tmp/test.txt'));
		$this->assertNull($client->get('javascript:alert(1)'));
		$this->assertNull($client->get('http://localhost/test'));
		$this->assertNull($client->get('http://127.0.0.1/test'));
		$this->assertNull($client->get('http://10.0.0.1/test'));
		$this->assertNull($client->get('http://169.254.169.254/test'));
	}

	public function testGetReturnsNullForInvalidUrl(): void {
		$client = new StreamHttpClient(1);

		$result = $client->get('https://invalid.nonexistent.example.com/page');

		$this->assertNull($result);
	}

}
