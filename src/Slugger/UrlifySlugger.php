<?php

declare(strict_types=1);

namespace MediaEmbed\Slugger;

use URLify;

/**
 * Default slugger.
 *
 * Uses jbroadway/urlify when it is installed (best transliteration of non-ASCII
 * names) and otherwise falls back to a built-in ASCII slugger, so the dependency
 * is optional.
 */
final class UrlifySlugger implements SluggerInterface {

	/**
	 * @inheritDoc
	 */
	public function slug(string $value): string {
		if (class_exists(URLify::class)) {
			return URLify::filter($value);
		}

		$slug = strtolower($value);
		$slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

		return trim($slug, '-');
	}

}
