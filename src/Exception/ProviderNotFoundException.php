<?php

declare(strict_types=1);

namespace MediaEmbed\Exception;

/**
 * Exception thrown when a provider cannot be found.
 */
class ProviderNotFoundException extends MediaEmbedException {

	protected string $providerSlug;

	/**
	 * @param string $slug The provider slug that was not found.
	 */
	public function __construct(string $slug) {
		$this->providerSlug = $slug;
		parent::__construct(sprintf('Provider "%s" not found.', $slug));
	}

	/**
	 * Get the provider slug that was not found.
	 *
	 * @return string
	 */
	public function getProviderSlug(): string {
		return $this->providerSlug;
	}

}
