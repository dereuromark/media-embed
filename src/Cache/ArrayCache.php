<?php

declare(strict_types=1);

namespace MediaEmbed\Cache;

/**
 * Simple in-memory array cache.
 *
 * This cache stores values in memory for the duration of the request.
 * Use this when you don't need persistent caching.
 */
final class ArrayCache implements CacheInterface {

	/**
	 * @var array<string, mixed>
	 */
	private array $cache = [];

	/**
	 * @inheritDoc
	 */
	public function get(string $key, mixed $default = null): mixed {
		return $this->cache[$key] ?? $default;
	}

	/**
	 * @inheritDoc
	 */
	public function set(string $key, mixed $value, ?int $ttl = null): bool {
		$this->cache[$key] = $value;

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function delete(string $key): bool {
		unset($this->cache[$key]);

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function has(string $key): bool {
		return isset($this->cache[$key]);
	}

	/**
	 * Clear all cached values.
	 *
	 * @return bool
	 */
	public function clear(): bool {
		$this->cache = [];

		return true;
	}

}
