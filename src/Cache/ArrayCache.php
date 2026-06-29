<?php

declare(strict_types=1);

namespace MediaEmbed\Cache;

use DateInterval;
use DateTimeImmutable;

/**
 * Simple in-memory array cache.
 *
 * This cache stores values in memory for the duration of the request.
 * Use this when you don't need persistent caching.
 */
final class ArrayCache implements CacheInterface {

	/**
	 * @var array<string, array{value: mixed, expires: float|null}>
	 */
	private array $cache = [];

	/**
	 * @inheritDoc
	 */
	public function get(string $key, mixed $default = null): mixed {
		if (!$this->has($key)) {
			return $default;
		}

		return $this->cache[$key]['value'];
	}

	/**
	 * @inheritDoc
	 */
	public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool {
		$this->cache[$key] = [
			'value' => $value,
			'expires' => $this->expiresAt($ttl),
		];

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
		if (!array_key_exists($key, $this->cache)) {
			return false;
		}

		$expires = $this->cache[$key]['expires'];
		if ($expires !== null && $expires <= microtime(true)) {
			unset($this->cache[$key]);

			return false;
		}

		return true;
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

	/**
	 * @inheritDoc
	 *
	 * @param iterable<string> $keys
	 */
	public function getMultiple(iterable $keys, mixed $default = null): iterable {
		$values = [];
		foreach ($keys as $key) {
			$values[$key] = $this->get($key, $default);
		}

		return $values;
	}

	/**
	 * @inheritDoc
	 *
	 * @param iterable<string, mixed> $values
	 */
	public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool {
		foreach ($values as $key => $value) {
			$this->set($key, $value, $ttl);
		}

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param iterable<string> $keys
	 */
	public function deleteMultiple(iterable $keys): bool {
		foreach ($keys as $key) {
			$this->delete($key);
		}

		return true;
	}

	/**
	 * @param \DateInterval|int|null $ttl
	 * @return float|null
	 */
	private function expiresAt(DateInterval|int|null $ttl): ?float {
		if ($ttl === null) {
			return null;
		}

		if ($ttl instanceof DateInterval) {
			return (float)(new DateTimeImmutable())->add($ttl)->format('U.u');
		}

		return microtime(true) + $ttl;
	}

}
