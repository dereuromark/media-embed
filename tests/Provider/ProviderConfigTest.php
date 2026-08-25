<?php

namespace MediaEmbed\Test\Provider;

use MediaEmbed\Exception\ProviderConfigException;
use MediaEmbed\Provider\Enum\Category;
use MediaEmbed\Provider\Enum\Status;
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
		$this->assertSame('640', $config->embedWidth);
		$this->assertSame('360', $config->embedHeight);
		$this->assertSame('//test.example.com/embed/$2', $config->iframePlayer);
		$this->assertSame(Status::Active, $config->status);
		$this->assertSame(Category::Video, $config->category);
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
			'timestamp-param' => 'start',
			'status' => Status::Legacy->value,
			'category' => Category::Audio->value,
			'example-url' => 'https://full.example.com/watch/abc123',
			'notes' => 'Legacy audio provider.',
			'iframe-params' => [
				'parent' => 'example.com',
			],
		];

		$config = ProviderConfig::fromArray($data);

		$this->assertSame('full-provider', $config->slug);
		$this->assertSame(['pattern1', 'pattern2'], $config->urlMatch);
		$this->assertSame('//full.example.com/thumb/$2.jpg', $config->imageSrc);
		$this->assertSame('$2', $config->id);
		$this->assertSame('data-id="([a-z0-9]+)"', $config->fetchMatch);
		$this->assertTrue($config->supportsTimestamp);
		$this->assertSame('start', $config->timestampParam);
		$this->assertSame(Status::Legacy, $config->status);
		$this->assertSame(Category::Audio, $config->category);
		$this->assertSame('https://full.example.com/watch/abc123', $config->exampleUrl);
		$this->assertSame('Legacy audio provider.', $config->notes);
		$this->assertSame([
			'parent' => 'example.com',
		], $config->iframeParams);
	}

	public function testFromArrayPreservesPercentageDimensions(): void {
		$data = [
			'name' => 'ResponsiveProvider',
			'website' => 'https://responsive.example.com',
			'url-match' => 'responsive\\.example\\.com/([a-z0-9]+)',
			'embed-width' => '100%',
			'embed-height' => '400',
			'iframe-player' => '//responsive.example.com/embed/$2',
		];

		$config = ProviderConfig::fromArray($data);

		$this->assertSame('100%', $config->embedWidth);
		$this->assertSame('400', $config->embedHeight);
	}

	public function testFromArrayPreservesIntegerDimensions(): void {
		$data = [
			'name' => 'IntegerProvider',
			'website' => 'https://integer.example.com',
			'url-match' => 'integer\\.example\\.com/([a-z0-9]+)',
			'embed-width' => 640,
			'embed-height' => 360,
			'iframe-player' => '//integer.example.com/embed/$2',
		];

		$config = ProviderConfig::fromArray($data);

		$this->assertSame(640, $config->embedWidth);
		$this->assertSame(360, $config->embedHeight);
	}

	public function testFromArrayRejectsInvalidDimensionType(): void {
		$this->expectException(ProviderConfigException::class);
		$this->expectExceptionMessage('Provider configuration field "embed-width" has invalid value. Expected integer or string.');

		ProviderConfig::fromArray([
			'name' => 'InvalidDimensionProvider',
			'website' => 'https://invalid-dimension.example.com',
			'url-match' => 'invalid-dimension\\.example\\.com/([a-z0-9]+)',
			'embed-width' => [],
			'embed-height' => 360,
			'iframe-player' => '//invalid-dimension.example.com/embed/$2',
		]);
	}

	public function testFromArrayRequiresIframePlayerOrOEmbed(): void {
		$this->expectException(ProviderConfigException::class);
		$this->expectExceptionMessage('Provider configuration is missing required field: iframe-player or oembed');

		ProviderConfig::fromArray([
			'name' => 'NoIframeProvider',
			'website' => 'https://no-iframe.example.com',
			'url-match' => 'no-iframe\\.example\\.com/([a-z0-9]+)',
			'embed-width' => 640,
			'embed-height' => 360,
		]);
	}

	public function testFromArrayAllowsOEmbedOnlyProvider(): void {
		$config = ProviderConfig::fromArray([
			'name' => 'RichProvider',
			'website' => 'https://rich.example.com',
			'url-match' => 'rich\\.example\\.com/posts/([0-9]+)',
			'embed-width' => 540,
			'embed-height' => 600,
			'oembed' => 'https://rich.example.com/oembed',
		]);

		$this->assertFalse($config->hasIframeSupport());
		$this->assertTrue($config->hasOEmbedSupport());
		$this->assertSame('https://rich.example.com/oembed', $config->toArray()['oembed']);
	}

	public function testFromArrayPreservesExtraProviderMetadata(): void {
		$data = [
			'name' => 'ExtraProvider',
			'website' => 'https://extra.example.com',
			'url-match' => 'extra\\.example\\.com/([a-z0-9]+)',
			'embed-width' => '640',
			'embed-height' => '360',
			'iframe-player' => '//extra.example.com/embed/$2',
			'replace' => [
				'foo' => 'bar',
			],
			'custom-metadata' => 'legacy',
		];

		$config = ProviderConfig::fromArray($data);
		$array = $config->toArray();

		$this->assertSame([
			'foo' => 'bar',
		], $config->extra['replace']);
		$this->assertSame('legacy', $config->extra['custom-metadata']);
		$this->assertSame($data['replace'], $array['replace']);
		$this->assertSame('legacy', $array['custom-metadata']);
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
		$this->assertSame(Status::Active->value, $array['status']);
		$this->assertSame(Category::Video->value, $array['category']);
		$this->assertSame('//test.com/embed/$2', $array['iframe-player']);
		$this->assertArrayNotHasKey('slug', $array);
		$this->assertArrayNotHasKey('supports-timestamp', $array);
		$this->assertArrayNotHasKey('timestamp-param', $array);
	}

	public function testToArrayTypedFieldsWinOverExtraFields(): void {
		$config = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//test.com/embed/$2',
			extra: [
				'name' => 'Wrong',
				'status' => 'legacy',
				'category' => 'audio',
			],
		);

		$array = $config->toArray();

		$this->assertSame('Test', $array['name']);
		$this->assertSame(Status::Active->value, $array['status']);
		$this->assertSame(Category::Video->value, $array['category']);
	}

	public function testToArrayWithMetadata(): void {
		$config = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			status: Status::Legacy,
			category: Category::Audio,
			exampleUrl: 'https://test.com/watch/abc',
			notes: 'Legacy provider.',
			iframePlayer: '//test.com/embed/$2',
		);

		$array = $config->toArray();

		$this->assertSame(Status::Legacy->value, $array['status']);
		$this->assertSame(Category::Audio->value, $array['category']);
		$this->assertSame('https://test.com/watch/abc', $array['example-url']);
		$this->assertSame('Legacy provider.', $array['notes']);
	}

	public function testToArrayWithIframeParams(): void {
		$config = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//test.com/embed/$2',
			iframeParams: [
				'parent' => 'example.com',
			],
		);

		$array = $config->toArray();

		$this->assertSame([
			'parent' => 'example.com',
		], $array['iframe-params']);
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

	public function testHasOEmbedSupport(): void {
		$config = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			oEmbed: 'https://test.com/oembed',
		);

		$this->assertTrue($config->hasOEmbedSupport());
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
