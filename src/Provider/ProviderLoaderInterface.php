<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

/**
 * Interface for provider configuration loaders.
 *
 * Implementations of this interface can load provider configurations
 * from various sources (files, arrays, remote APIs, etc.).
 */
interface ProviderLoaderInterface {

	/**
	 * Load provider configurations.
	 *
	 * @return array<array<string, mixed>> Array of provider configuration arrays.
	 */
	public function load(): array;

	/**
	 * Check if the loader can load from the given source.
	 *
	 * @return bool
	 */
	public function canLoad(): bool;

}
