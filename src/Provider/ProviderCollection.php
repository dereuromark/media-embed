<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use MediaEmbed\Exception\ProviderNotFoundException;
use Traversable;
use URLify;

/**
 * Collection of provider configurations.
 *
 * This class manages a collection of ProviderConfig objects and provides
 * methods for adding, retrieving, and filtering providers.
 *
 * @implements \IteratorAggregate<string, \MediaEmbed\Provider\ProviderConfig>
 */
final class ProviderCollection implements IteratorAggregate, Countable {

	/**
	 * @var array<string, \MediaEmbed\Provider\ProviderConfig>
	 */
	private array $providers = [];

	/**
	 * Create a collection from an array of provider data.
	 *
	 * @param array<array<string, mixed>> $providers Array of provider configuration arrays.
	 * @return self
	 */
	public static function fromArray(array $providers): self {
		$collection = new self();
		foreach ($providers as $provider) {
			$config = ProviderConfig::fromArray($provider);
			$collection->add($config);
		}

		return $collection;
	}

	/**
	 * Add a provider to the collection.
	 *
	 * @param \MediaEmbed\Provider\ProviderConfig $config Provider configuration.
	 * @param bool $override Whether to override existing provider with same slug.
	 * @return $this
	 */
	public function add(ProviderConfig $config, bool $override = false) {
		$slug = $config->slug ?? $this->generateSlug($config->name);

		if (!$override && isset($this->providers[$slug])) {
			return $this;
		}

		$this->providers[$slug] = $config;

		return $this;
	}

	/**
	 * Get a provider by slug.
	 *
	 * @param string $slug Provider slug.
	 * @return \MediaEmbed\Provider\ProviderConfig|null
	 */
	public function get(string $slug): ?ProviderConfig {
		return $this->providers[$slug] ?? null;
	}

	/**
	 * Get a provider by slug or throw exception if not found.
	 *
	 * @param string $slug Provider slug.
	 * @throws \MediaEmbed\Exception\ProviderNotFoundException
	 * @return \MediaEmbed\Provider\ProviderConfig
	 */
	public function getOrFail(string $slug): ProviderConfig {
		if (!isset($this->providers[$slug])) {
			throw new ProviderNotFoundException($slug);
		}

		return $this->providers[$slug];
	}

	/**
	 * Check if a provider exists.
	 *
	 * @param string $slug Provider slug.
	 * @return bool
	 */
	public function has(string $slug): bool {
		return isset($this->providers[$slug]);
	}

	/**
	 * Remove a provider from the collection.
	 *
	 * @param string $slug Provider slug.
	 * @return $this
	 */
	public function remove(string $slug) {
		unset($this->providers[$slug]);

		return $this;
	}

	/**
	 * Get all provider slugs.
	 *
	 * @return array<string>
	 */
	public function slugs(): array {
		return array_keys($this->providers);
	}

	/**
	 * Filter providers by a callback.
	 *
	 * @param callable(ProviderConfig, string): bool $callback
	 */
	public function filter(callable $callback): self {
		$filtered = new self();
		foreach ($this->providers as $slug => $provider) {
			if ($callback($provider, $slug)) {
				$filtered->providers[$slug] = $provider;
			}
		}

		return $filtered;
	}

	/**
	 * Filter providers by whitelist of slugs.
	 *
	 * @param array<string> $slugs Slugs to include.
	 */
	public function whitelist(array $slugs): self {
		return $this->filter(fn (ProviderConfig $config, string $slug) => in_array($slug, $slugs, true));
	}

	/**
	 * Convert collection to array format.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function toArray(): array {
		$result = [];
		foreach ($this->providers as $slug => $provider) {
			$result[$slug] = $provider->toArray();
		}

		return $result;
	}

	/**
	 * Get providers with iframe support.
	 */
	public function withIframeSupport(): self {
		return $this->filter(fn (ProviderConfig $config) => $config->hasIframeSupport());
	}

	/**
	 * Get providers with thumbnail support.
	 */
	public function withThumbnailSupport(): self {
		return $this->filter(fn (ProviderConfig $config) => $config->hasThumbnailSupport());
	}

	/**
	 * @inheritDoc
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator($this->providers);
	}

	/**
	 * @inheritDoc
	 */
	public function count(): int {
		return count($this->providers);
	}

	/**
	 * Generate a URL-safe slug from a name.
	 *
	 * @param string $name
	 * @return string
	 */
	private function generateSlug(string $name): string {
		return URLify::filter($name);
	}

}
