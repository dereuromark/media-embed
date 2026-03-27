<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

/**
 * Loads provider configurations from an array.
 *
 * This loader is useful for testing or programmatic configuration.
 */
final class ArrayLoader implements ProviderLoaderInterface {

	/**
	 * @param array<array<string, mixed>> $providers Array of provider configurations.
	 */
	public function __construct(
		private readonly array $providers,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function load(): array {
		return $this->providers;
	}

	/**
	 * @inheritDoc
	 */
	public function canLoad(): bool {
		return true;
	}

}
