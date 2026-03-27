<?php

namespace MediaEmbed\Test\Provider;

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
		);

		$collection->add($config);

		$this->assertTrue($collection->has('test-provider'));
		$this->assertSame($config, $collection->get('test-provider'));
	}

	public function testFilter(): void {
		$data = [
			[
				'name' => 'WithIframe',
				'website' => 'https://iframe.example.com',
				'url-match' => 'pattern1',
				'embed-width' => '640',
				'embed-height' => '360',
				'iframe-player' => '//iframe.example.com/embed/$2',
			],
			[
				'name' => 'WithoutIframe',
				'website' => 'https://no-iframe.example.com',
				'url-match' => 'pattern2',
				'embed-width' => '640',
				'embed-height' => '360',
			],
		];

		$collection = ProviderCollection::fromArray($data);
		$filtered = $collection->withIframeSupport();

		$this->assertCount(1, $filtered);
		$this->assertTrue($filtered->has('withiframe'));
		$this->assertFalse($filtered->has('withoutiframe'));
	}

	public function testWhitelist(): void {
		$data = [
			[
				'name' => 'Keep',
				'website' => 'https://keep.example.com',
				'url-match' => 'pattern1',
				'embed-width' => '640',
				'embed-height' => '360',
			],
			[
				'name' => 'Remove',
				'website' => 'https://remove.example.com',
				'url-match' => 'pattern2',
				'embed-width' => '640',
				'embed-height' => '360',
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
			],
			[
				'name' => 'Beta',
				'website' => 'https://beta.example.com',
				'url-match' => 'pattern',
				'embed-width' => '640',
				'embed-height' => '360',
			],
		];

		$collection = ProviderCollection::fromArray($data);
		$slugs = $collection->slugs();

		$this->assertSame(['alpha', 'beta'], $slugs);
	}

}
