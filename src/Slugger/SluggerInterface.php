<?php

declare(strict_types=1);

namespace MediaEmbed\Slugger;

/**
 * Converts a provider name into a URL-safe slug.
 */
interface SluggerInterface {

	/**
	 * @param string $value The value to slug.
	 * @return string The slugified value (lowercase, alphanumeric and dashes).
	 */
	public function slug(string $value): string;

}
