<?php

namespace MediaEmbed\Test\Provider;

use MediaEmbed\Enum\ProviderCategory;
use MediaEmbed\Enum\ProviderStatus;
use MediaEmbed\Provider\ProviderCollection;
use MediaEmbed\Provider\ProviderConfig;
use PHPUnit\Framework\TestCase;

class ProviderCollectionTest extends TestCase {

	public function testFromArray(): void {
		$data = [
			[
				'name' => 'Provider1',
				'website' => 'https://one.example.com',
				'url-match' => 'one\\.example\\.com/([a-z]+)',
				'embed-width' => '640',
				'embed-height' => '360',
				'iframe-player' => '//one.example.com/embed/$2',
			],
			[
				'name' => 'Provider2',
				'website' => 'https://two.example.com',
				'url-match' => 'two\\.example\\.com/([a-z]+)',
				'embed-width' => '800',
				'embed-height' => '600',
				'iframe-player' => '//two.example.com/embed/$2',
			],
		];

		$collection = ProviderCollection::fromArray($data);

		$this->assertCount(2, $collection);
		$this->assertTrue($collection->has('provider1'));
		$this->assertTrue($collection->has('provider2'));
	}

	public function testAddAndGet(): void {
		$collection = new ProviderCollection();
		$config = new ProviderConfig(
			name: 'Test',
			website: 'https://test.com',
			urlMatch: 'pattern',
			embedWidth: 640,
			embedHeight: 360,
			slug: 'test-provider',
			iframePlayer: '//test.com/embed/$2',
		);

		$collection->add($config);

		$this->assertTrue($collection->has('test-provider'));
		$this->assertSame($config, $collection->get('test-provider'));
	}

	public function testFilter(): void {
		$collection = new ProviderCollection();
		$collection->add(new ProviderConfig(
			name: 'WithIframe',
			website: 'https://iframe.example.com',
			urlMatch: 'pattern1',
			embedWidth: '640',
			embedHeight: '360',
			iframePlayer: '//iframe.example.com/embed/$2',
		));
		$collection->add(new ProviderConfig(
			name: 'WithoutIframe',
			website: 'https://no-iframe.example.com',
			urlMatch: 'pattern2',
			embedWidth: '640',
			embedHeight: '360',
		));

		$filtered = $collection->withIframeSupport();

		$this->assertCount(1, $filtered);
		$this->assertTrue($filtered->has('withiframe'));
		$this->assertFalse($filtered->has('withoutiframe'));
	}

	public function testFilterByStatusAndCategory(): void {
		$collection = new ProviderCollection();
		$collection->add(new ProviderConfig(
			name: 'LegacyAudio',
			website: 'https://legacy-audio.example.com',
			urlMatch: 'pattern1',
			embedWidth: '640',
			embedHeight: '360',
			slug: 'legacy-audio',
			iframePlayer: '//legacy-audio.example.com/embed/$2',
			status: ProviderStatus::Legacy,
			category: ProviderCategory::Audio,
		));
		$collection->add(new ProviderConfig(
			name: 'ActiveVideo',
			website: 'https://active-video.example.com',
			urlMatch: 'pattern2',
			embedWidth: '640',
			embedHeight: '360',
			slug: 'active-video',
			iframePlayer: '//active-video.example.com/embed/$2',
			status: ProviderStatus::Active,
			category: ProviderCategory::Video,
		));

		$legacy = $collection->withStatus(ProviderStatus::Legacy);
		$audio = $collection->withCategory(ProviderCategory::Audio);

		$this->assertCount(1, $legacy);
		$this->assertTrue($legacy->has('legacy-audio'));
		$this->assertCount(1, $audio);
		$this->assertTrue($audio->has('legacy-audio'));
	}

	public function testWhitelist(): void {
		$data = [
			[
				'name' => 'Keep',
				'website' => 'https://keep.example.com',
				'url-match' => 'pattern1',
				'embed-width' => '640',
				'embed-height' => '360',
				'iframe-player' => '//keep.example.com/embed/$2',
			],
			[
				'name' => 'Remove',
				'website' => 'https://remove.example.com',
				'url-match' => 'pattern2',
				'embed-width' => '640',
				'embed-height' => '360',
				'iframe-player' => '//remove.example.com/embed/$2',
			],
		];

		$collection = ProviderCollection::fromArray($data);
		$filtered = $collection->whitelist(['keep']);

		$this->assertCount(1, $filtered);
		$this->assertTrue($filtered->has('keep'));
	}

	public function testIterable(): void {
		$data = [
			[
				'name' => 'One',
				'website' => 'https://one.example.com',
				'url-match' => 'pattern',
				'embed-width' => '640',
				'embed-height' => '360',
				'iframe-player' => '//one.example.com/embed/$2',
			],
		];

		$collection = ProviderCollection::fromArray($data);
		$count = 0;

		foreach ($collection as $slug => $config) {
			$this->assertSame('one', $slug);
			$this->assertInstanceOf(ProviderConfig::class, $config);
			$count++;
		}

		$this->assertSame(1, $count);
	}

	public function testSlugs(): void {
		$data = [
			[
				'name' => 'Alpha',
				'website' => 'https://alpha.example.com',
				'url-match' => 'pattern',
				'embed-width' => '640',
				'embed-height' => '360',
				'iframe-player' => '//alpha.example.com/embed/$2',
			],
			[
				'name' => 'Beta',
				'website' => 'https://beta.example.com',
				'url-match' => 'pattern',
				'embed-width' => '640',
				'embed-height' => '360',
				'iframe-player' => '//beta.example.com/embed/$2',
			],
		];

		$collection = ProviderCollection::fromArray($data);
		$slugs = $collection->slugs();

		$this->assertSame(['alpha', 'beta'], $slugs);
	}

}
