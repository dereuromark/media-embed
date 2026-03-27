<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

/**
 * Loads provider configurations from a JSON file.
 */
final class JsonFileLoader implements ProviderLoaderInterface {

	/**
	 * @param string $path Path to the JSON file.
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

		$content = file_get_contents($this->path);
		if ($content === false) {
			return [];
		}

		$providers = json_decode($content, true);
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
