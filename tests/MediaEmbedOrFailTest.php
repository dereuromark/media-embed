<?php

namespace MediaEmbed\Test;

use MediaEmbed\Exception\InvalidUrlException;
use MediaEmbed\Exception\ProviderNotFoundException;
use MediaEmbed\MediaEmbed;
use MediaEmbed\Provider\ProviderConfig;
use PHPUnit\Framework\TestCase;

/**
 * Test MediaEmbed OrFail methods
 */
class MediaEmbedOrFailTest extends TestCase {

	protected MediaEmbed $mediaEmbed;

	protected function setUp(): void {
		$this->mediaEmbed = new MediaEmbed();
	}

	public function testParseUrlOrFailSuccess(): void {
		$result = $this->mediaEmbed->parseUrlOrFail('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

		$this->assertSame('dQw4w9WgXcQ', $result->id());
		$this->assertSame('youtube', $result->slug());
	}

	public function testParseUrlOrFailThrowsInvalidUrlException(): void {
		$this->expectException(InvalidUrlException::class);

		$this->mediaEmbed->parseUrlOrFail('https://invalid.nonexistent.example.com/video/123');
	}

	public function testParseIdOrFailSuccess(): void {
		$result = $this->mediaEmbed->parseIdOrFail('dQw4w9WgXcQ', 'youtube');

		$this->assertSame('dQw4w9WgXcQ', $result->id());
	}

	public function testParseIdOrFailThrowsForEmptyId(): void {
		$this->expectException(InvalidUrlException::class);

		$this->mediaEmbed->parseIdOrFail('', 'youtube');
	}

	public function testParseIdOrFailThrowsForEmptyHost(): void {
		$this->expectException(InvalidUrlException::class);

		$this->mediaEmbed->parseIdOrFail('abc123', '');
	}

	public function testParseIdOrFailThrowsProviderNotFound(): void {
		$this->expectException(ProviderNotFoundException::class);

		$this->mediaEmbed->parseIdOrFail('abc123', 'nonexistent-provider');
	}

	public function testGetProviderSuccess(): void {
		$result = $this->mediaEmbed->getProvider('youtube');

		$this->assertInstanceOf(ProviderConfig::class, $result);
		$this->assertSame('YouTube', $result->name);
	}

	public function testGetProviderReturnsNull(): void {
		$result = $this->mediaEmbed->getProvider('nonexistent');

		$this->assertNull($result);
	}

	public function testGetProviderOrFailSuccess(): void {
		$result = $this->mediaEmbed->getProviderOrFail('youtube');

		$this->assertInstanceOf(ProviderConfig::class, $result);
		$this->assertSame('YouTube', $result->name);
	}

	public function testGetProviderOrFailThrows(): void {
		$this->expectException(ProviderNotFoundException::class);

		$this->mediaEmbed->getProviderOrFail('nonexistent');
	}

	public function testAddProviderConfig(): void {
		$config = new ProviderConfig(
			name: 'CustomProvider',
			website: 'https://custom.example.com',
			urlMatch: 'https://custom\\.example\\.com/v/([a-z0-9]+)',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//custom.example.com/embed/$2',
		);

		$this->mediaEmbed->addProviderConfig($config);

		$result = $this->mediaEmbed->getProvider('customprovider');
		$this->assertNotNull($result);
		$this->assertSame('CustomProvider', $result->name);
	}

}
