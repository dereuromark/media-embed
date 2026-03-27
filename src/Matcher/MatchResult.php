<?php

declare(strict_types=1);

namespace MediaEmbed\Matcher;

/**
 * Result of a URL match operation.
 */
final class MatchResult {

	/**
	 * @param string $providerSlug The matched provider's slug.
	 * @param array<string> $matches Regex capture groups from the match.
	 * @param array<string, mixed> $providerStub The matched provider configuration.
	 */
	public function __construct(
		public readonly string $providerSlug,
		public readonly array $matches,
		public readonly array $providerStub,
	) {
	}

	/**
	 * Check if a match was successful.
	 *
	 * @return bool
	 */
	public function isSuccessful(): bool {
		return !empty($this->matches);
	}

	/**
	 * Get a specific match by index.
	 *
	 * @param int $index Match index (0 = full match, 1+ = capture groups).
	 * @return string|null
	 */
	public function getMatch(int $index): ?string {
		return $this->matches[$index] ?? null;
	}

	/**
	 * Get the extracted ID (typically match index 1 or 2).
	 *
	 * @return string
	 */
	public function getId(): string {
		// Try $2 first (most common), then $1
		return $this->matches[1] ?? $this->matches[0] ?? '';
	}

}
