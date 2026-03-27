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

	public function testGetReturnsNullForInvalidUrl(): void {
		$client = new StreamHttpClient(1);

		$result = $client->get('https://invalid.nonexistent.example.com/page');

		$this->assertNull($result);
	}

}
