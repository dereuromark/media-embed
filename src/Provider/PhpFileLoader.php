<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

/**
 * Loads provider configurations from a PHP file.
 *
 * The PHP file should return an array of provider configuration arrays.
 */
final class PhpFileLoader implements ProviderLoaderInterface {

	/**
	 * @param string $path Path to the PHP file.
	 */
	public function __construct(
		private readonly string $path,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function load(): array {
		if (!$this->canLoad()) {
			return [];
		}

		$providers = include $this->path;
		if (!is_array($providers)) {
			return [];
		}

		return $providers;
	}

	/**
	 * @inheritDoc
	 */
	public function canLoad(): bool {
		return file_exists($this->path) && is_readable($this->path);
	}

	/**
	 * Get the file path.
	 *
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

}
