<?php

namespace MediaEmbed\Test\OEmbed;

use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\OEmbed\OEmbedDiscovery;
use MediaEmbed\OEmbed\OEmbedResponse;
use PHPUnit\Framework\TestCase;

class OEmbedTest extends TestCase {

	public function testOEmbedResponseFromArray(): void {
		$data = [
			'type' => 'video',
			'version' => '1.0',
			'title' => 'Test Video',
			'author_name' => 'Test Author',
			'provider_name' => 'TestTube',
			'provider_url' => 'https://testtube.example.com',
			'thumbnail_url' => 'https://testtube.example.com/thumb.jpg',
			'thumbnail_width' => 480,
			'thumbnail_height' => 360,
			'html' => '<iframe src="..."></iframe>',
			'width' => 640,
			'height' => 480,
		];

		$response = OEmbedResponse::fromArray($data);

		$this->assertSame('video', $response->type);
		$this->assertSame('1.0', $response->version);
		$this->assertSame('Test Video', $response->title);
		$this->assertSame('Test Author', $response->authorName);
		$this->assertSame('TestTube', $response->providerName);
		$this->assertSame('https://testtube.example.com', $response->providerUrl);
		$this->assertSame(640, $response->width);
		$this->assertSame(480, $response->height);
		$this->assertTrue($response->isVideo());
		$this->assertFalse($response->isPhoto());
		$this->assertTrue($response->hasHtml());
		$this->assertTrue($response->hasThumbnail());
	}

	public function testOEmbedResponsePhotoType(): void {
		$data = [
			'type' => 'photo',
			'version' => '1.0',
			'url' => 'https://example.com/image.jpg',
			'width' => 800,
			'height' => 600,
		];

		$response = OEmbedResponse::fromArray($data);

		$this->assertTrue($response->isPhoto());
		$this->assertFalse($response->isVideo());
		$this->assertFalse($response->hasHtml());
		$this->assertSame('https://example.com/image.jpg', $response->url);
	}

	public function testOEmbedResponseRichType(): void {
		$data = [
			'type' => 'rich',
			'version' => '1.0',
			'html' => '<div>Rich content</div>',
		];

		$response = OEmbedResponse::fromArray($data);

		$this->assertTrue($response->isRich());
		$this->assertTrue($response->hasHtml());
	}

	public function testOEmbedDiscoveryParseEndpoint(): void {
		$mockClient = $this->createMock(HttpClientInterface::class);
		$mockClient->method('get')
			->willReturn('<html><head><link rel="alternate" type="application/json+oembed" href="https://example.com/oembed?url=test" /></head></html>');

		$discovery = new OEmbedDiscovery($mockClient);
		$endpoint = $discovery->discoverEndpoint('https://example.com/video/123');

		$this->assertSame('https://example.com/oembed?url=test', $endpoint);
	}

	public function testOEmbedDiscoveryNoEndpoint(): void {
		$mockClient = $this->createMock(HttpClientInterface::class);
		$mockClient->method('get')
			->willReturn('<html><head><title>No oEmbed</title></head></html>');

		$discovery = new OEmbedDiscovery($mockClient);
		$endpoint = $discovery->discoverEndpoint('https://example.com/page');

		$this->assertNull($endpoint);
	}

	public function testOEmbedDiscoveryFetch(): void {
		$mockClient = $this->createMock(HttpClientInterface::class);
		$mockClient->method('get')
			->willReturn(json_encode([
				'type' => 'video',
				'version' => '1.0',
				'title' => 'Mock Video',
				'html' => '<iframe></iframe>',
			]));

		$discovery = new OEmbedDiscovery($mockClient);
		$response = $discovery->fetch('https://example.com/oembed?url=test');

		$this->assertNotNull($response);
		$this->assertSame('video', $response->type);
		$this->assertSame('Mock Video', $response->title);
	}

	public function testOEmbedDiscoveryFetchWithDimensions(): void {
		$mockClient = $this->createMock(HttpClientInterface::class);
		$mockClient->expects($this->once())
			->method('get')
			->with($this->stringContains('maxwidth=640'))
			->willReturn(json_encode([
				'type' => 'video',
				'version' => '1.0',
			]));

		$discovery = new OEmbedDiscovery($mockClient);
		$discovery->fetch('https://example.com/oembed?url=test', 640, 480);
	}

}
