<?php

declare(strict_types=1);

namespace MediaEmbed\Exception;

/**
 * Exception thrown when a provider has no static iframe renderer.
 */
class EmbedCodeUnavailableException extends MediaEmbedException {

	/**
	 * @param string $providerSlug Provider slug.
	 */
	public function __construct(string $providerSlug) {
		parent::__construct(sprintf(
			'Provider "%s" has no iframe embed. Use MediaEmbed::oEmbedHtml() for oEmbed-only providers.',
			$providerSlug,
		));
	}

}
