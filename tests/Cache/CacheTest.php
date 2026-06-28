<?php

namespace MediaEmbed\Test\Cache;

use DateInterval;
use MediaEmbed\Cache\ArrayCache;
use MediaEmbed\Cache\CacheInterface;
use MediaEmbed\Matcher\UrlMatcher;
use MediaEmbed\MediaEmbed;
use MediaEmbed\Provider\ProviderConfig;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

class CacheTest extends TestCase {

	public function testArrayCacheImplementsInterface(): void {
		$cache = new ArrayCache();

		$this->assertInstanceOf(CacheInterface::class, $cache);
		$this->assertInstanceOf(PsrCacheInterface::class, $cache);
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

	public function testArrayCacheMultipleOperations(): void {
		$cache = new ArrayCache();

		$cache->setMultiple([
			'key1' => 'value1',
			'key2' => 'value2',
		]);

		$this->assertSame([
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'default',
		], $cache->getMultiple(['key1', 'key2', 'key3'], 'default'));

		$cache->deleteMultiple(['key1', 'key2']);

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

	public function testUrlMatcherAcceptsPsrCache(): void {
		$cache = new class () implements PsrCacheInterface {
			/**
			 * @var array<string, mixed>
			 */
			private array $cache = [];

			public function get(string $key, mixed $default = null): mixed {
				return $this->cache[$key] ?? $default;
			}

			public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool {
				$this->cache[$key] = $value;

				return true;
			}

			public function delete(string $key): bool {
				unset($this->cache[$key]);

				return true;
			}

			public function clear(): bool {
				$this->cache = [];

				return true;
			}

			public function getMultiple(iterable $keys, mixed $default = null): iterable {
				$values = [];
				foreach ($keys as $key) {
					$values[$key] = $this->get($key, $default);
				}

				return $values;
			}

			public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool {
				foreach ($values as $key => $value) {
					$this->set($key, $value, $ttl);
				}

				return true;
			}

			public function deleteMultiple(iterable $keys): bool {
				foreach ($keys as $key) {
					$this->delete($key);
				}

				return true;
			}

			public function has(string $key): bool {
				return isset($this->cache[$key]);
			}
		};

		$matcher = new UrlMatcher([
			'test' => [
				'name' => 'Test Provider',
				'url-match' => ['test\\.example\\.com/video/([0-9]+)'],
			],
		], $cache);

		$result = $matcher->match('https://test.example.com/video/123');

		$this->assertNotNull($result);
		$this->assertTrue($cache->has('media_embed_domain_index'));
	}

	public function testMediaEmbedUsesConstructorCache(): void {
		$cache = new ArrayCache();
		$mediaEmbed = new MediaEmbed(cache: $cache);

		$result = $mediaEmbed->parseUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

		$this->assertNotNull($result);
		$this->assertTrue($cache->has('media_embed_domain_index'));
	}

	public function testMediaEmbedCacheSurvivesProviderChanges(): void {
		$cache = new ArrayCache();
		$mediaEmbed = new MediaEmbed();
		$mediaEmbed->setCache($cache);

		$mediaEmbed->addProviderConfig(new ProviderConfig(
			name: 'CacheProvider',
			website: 'https://cache.example.com',
			urlMatch: 'https://cache\\.example\\.com/video/([0-9]+)',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//cache.example.com/embed/$2',
		));

		$result = $mediaEmbed->parseUrl('https://cache.example.com/video/123');

		$this->assertNotNull($result);
		$this->assertTrue($cache->has('media_embed_domain_index'));
	}

}
