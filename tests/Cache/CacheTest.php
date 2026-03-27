<?php

namespace MediaEmbed\Test\Cache;

use MediaEmbed\Cache\ArrayCache;
use MediaEmbed\Cache\CacheInterface;
use MediaEmbed\Matcher\UrlMatcher;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase {

	public function testArrayCacheImplementsInterface(): void {
		$cache = new ArrayCache();

		$this->assertInstanceOf(CacheInterface::class, $cache);
	}

	public function testArrayCacheSetAndGet(): void {
		$cache = new ArrayCache();

		$this->assertFalse($cache->has('test_key'));
		$this->assertNull($cache->get('test_key'));
		$this->assertSame('default', $cache->get('test_key', 'default'));

		$cache->set('test_key', ['foo' => 'bar']);

		$this->assertTrue($cache->has('test_key'));
		$this->assertSame(['foo' => 'bar'], $cache->get('test_key'));
	}

	public function testArrayCacheDelete(): void {
		$cache = new ArrayCache();

		$cache->set('key1', 'value1');
		$cache->set('key2', 'value2');

		$this->assertTrue($cache->has('key1'));
		$this->assertTrue($cache->has('key2'));

		$cache->delete('key1');

		$this->assertFalse($cache->has('key1'));
		$this->assertTrue($cache->has('key2'));
	}

	public function testArrayCacheClear(): void {
		$cache = new ArrayCache();

		$cache->set('key1', 'value1');
		$cache->set('key2', 'value2');

		$cache->clear();

		$this->assertFalse($cache->has('key1'));
		$this->assertFalse($cache->has('key2'));
	}

	public function testUrlMatcherWithCache(): void {
		$cache = new ArrayCache();

		$providers = [
			'test' => [
				'name' => 'Test Provider',
				'url-match' => ['test\\.example\\.com/video/([0-9]+)'],
			],
		];

		$matcher = new UrlMatcher($providers, $cache);

		// First match - should build and cache the index
		$result = $matcher->match('https://test.example.com/video/123');
		$this->assertNotNull($result);
		$this->assertSame('test', $result->providerSlug);

		// Verify cache has the index
		$this->assertTrue($cache->has('media_embed_domain_index'));

		// Create new matcher with same cache - should use cached index
		$matcher2 = new UrlMatcher($providers, $cache);
		$result2 = $matcher2->match('https://test.example.com/video/456');
		$this->assertNotNull($result2);
		$this->assertSame('test', $result2->providerSlug);
	}

	public function testUrlMatcherCacheInvalidation(): void {
		$cache = new ArrayCache();

		$providers = [
			'test' => [
				'name' => 'Test Provider',
				'url-match' => ['test\\.example\\.com/video/([0-9]+)'],
			],
		];

		$matcher = new UrlMatcher($providers, $cache);
		$matcher->match('https://test.example.com/video/123');

		$this->assertTrue($cache->has('media_embed_domain_index'));

		// Setting new providers should invalidate cache
		$matcher->setProviders([
			'other' => [
				'name' => 'Other Provider',
				'url-match' => ['other\\.example\\.com/v/([a-z]+)'],
			],
		]);

		$this->assertFalse($cache->has('media_embed_domain_index'));
	}

}
