<?php

namespace MediaEmbed\Test\Provider;

use MediaEmbed\Exception\ProviderConfigException;
use MediaEmbed\Provider\ProviderConfig;
use PHPUnit\Framework\TestCase;

class ProviderConfigTest extends TestCase {

	public function testFromArray(): void {
		$data = [
			'name' => 'TestProvider',
			'website' => 'https://test.example.com',
			'url-match' => 'https://test\\.example\\.com/([a-z0-9]+)',
			'embed-width' => '640',
			'embed-height' => '360',
			'iframe-player' => '//test.example.com/embed/$2',
		];

		$config = ProviderConfig::fromArray($data);

		$this->assertSame('TestProvider', $config->name);
		$this->assertSame('https://test.example.com', $config->website);
		$this->assertSame(640, $config->embedWidth);
		$this->assertSame(360, $config->embedHeight);
		$this->assertSame('//test.example.com/embed/$2', $config->iframePlayer);
	}

	public function testFromArrayWithAllFields(): void {
		$data = [
			'name' => 'FullProvider',
			'slug' => 'full-provider',
			'website' => 'https://full.example.com',
			'url-match' => ['pattern1', 'pattern2'],
			'embed-width' => '800',
			'embed-height' => '600',
			'iframe-player' => '//full.example.com/embed/$2',
			'image-src' => '//full.example.com/thumb/$2.jpg',
			'id' => '$2',
			'fetch-match' => 'data-id="([a-z0-9]+)"',
			'supports-timestamp' => true,
		];

		$config = ProviderConfig::fromArray($data);

		$this->assertSame('full-provider', $config->slug);
		$this->assertSame(['pattern1', 'pattern2'], $config->urlMatch);
		$this->assertSame('//full.example.com/thumb/$2.jpg', $config->imageSrc);
		$this->assertSame('$2', $config->id);
		$this->assertSame('data-id="([a-z0-9]+)"', $config->fetchMatch);
		$this->assertTrue($config->supportsTimestamp);
	}

	public function testFromArrayMissingName(): void {
		$this->expectException(ProviderConfigException::class);

		ProviderConfig::fromArray([
			'website' => 'https://example.com',
			'url-match' => 'test',
			'embed-width' => '640',
			'embed-height' => '360',
		]);
	}

	public function testFromArrayMissingWebsite(): void {
		$this->expectException(ProviderConfigException::class);

		ProviderConfig::fromArray([
			'name' => 'Test',
			'url-match' => 'test',
			'embed-width' => '640',
			'embed-height' => '360',
		]);
	}

	public function testFromArrayMissingUrlMatch(): void {
		$this->expectException(ProviderConfigException::class);

		ProviderConfig::fromArray([
			'name' => 'Test',
			'website' => 'https://example.com',
			'embed-width' => '640',
			'embed-height' => '360',
		]);
	}

	public function testToArray(): void {
		$config = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//test.com/embed/$2',
		);

		$array = $config->toArray();

		$this->assertSame('Test', $array['name']);
		$this->assertSame('https://test.com', $array['website']);
		$this->assertSame('pattern', $array['url-match']);
		$this->assertSame(640, $array['embed-width']);
		$this->assertSame(360, $array['embed-height']);
		$this->assertSame('//test.com/embed/$2', $array['iframe-player']);
		$this->assertArrayNotHasKey('slug', $array);
		$this->assertArrayNotHasKey('supports-timestamp', $array);
	}

	public function testGetUrlMatchPatterns(): void {
		$configSingle = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'single-pattern',
			embedWidth: 640,
			embedHeight: 360,
		);

		$configMultiple = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: ['pattern1', 'pattern2'],
			embedWidth: 640,
			embedHeight: 360,
		);

		$this->assertSame(['single-pattern'], $configSingle->getUrlMatchPatterns());
		$this->assertSame(['pattern1', 'pattern2'], $configMultiple->getUrlMatchPatterns());
	}

	public function testHasIframeSupport(): void {
		$withIframe = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//test.com/embed/$2',
		);

		$withoutIframe = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
		);

		$this->assertTrue($withIframe->hasIframeSupport());
		$this->assertFalse($withoutIframe->hasIframeSupport());
	}

	public function testHasThumbnailSupport(): void {
		$withThumb = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			imageSrc: '//test.com/thumb/$2.jpg',
		);

		$withoutThumb = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
		);

		$this->assertTrue($withThumb->hasThumbnailSupport());
		$this->assertFalse($withoutThumb->hasThumbnailSupport());
	}

	public function testRequiresFetch(): void {
		$withFetch = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			fetchMatch: 'data-id="([a-z]+)"',
		);

		$withoutFetch = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
		);

		$this->assertTrue($withFetch->requiresFetch());
		$this->assertFalse($withoutFetch->requiresFetch());
	}

}
