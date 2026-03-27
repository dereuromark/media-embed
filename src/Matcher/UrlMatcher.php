<?php

declare(strict_types=1);

namespace MediaEmbed\Matcher;

use MediaEmbed\Cache\CacheInterface;

/**
 * URL matcher with optional domain-based caching for faster lookups.
 *
 * This class provides optimized URL matching by building a domain index
 * to reduce the number of regex patterns that need to be tested.
 */
final class UrlMatcher {

	/**
	 * Cache key for the domain index.
     * @var string
	 */
	private const CACHE_KEY = 'media_embed_domain_index';

	/**
	 * Domain-to-providers index for fast path matching.
	 *
	 * @var array<string, array<string>>
	 */
	private array $domainIndex = [];

	/**
	 * All providers keyed by slug.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $providers = [];

	/**
	 * Whether the domain index has been built.
	 */
	private bool $indexBuilt = false;

	/**
	 * Optional cache for persisting the domain index.
	 */
	private ?CacheInterface $cache = null;

	/**
	 * Cache TTL in seconds (default: 1 hour).
	 */
	private int $cacheTtl = 3600;

	/**
	 * @param array<string, array<string, mixed>> $providers Providers keyed by slug.
	 * @param \MediaEmbed\Cache\CacheInterface|null $cache Optional cache for persisting domain index.
	 * @param int $cacheTtl Cache TTL in seconds.
	 */
	public function __construct(array $providers = [], ?CacheInterface $cache = null, int $cacheTtl = 3600) {
		$this->providers = $providers;
		$this->cache = $cache;
		$this->cacheTtl = $cacheTtl;
	}

	/**
	 * Set providers and reset the index.
	 *
	 * @param array<string, array<string, mixed>> $providers Providers keyed by slug.
	 * @return $this
	 */
	public function setProviders(array $providers) {
		$this->providers = $providers;
		$this->indexBuilt = false;
		$this->domainIndex = [];

		// Clear cached index when providers change
		if ($this->cache !== null) {
			$this->cache->delete(static::CACHE_KEY);
		}

		return $this;
	}

	/**
	 * Set the cache implementation.
	 *
	 * @param \MediaEmbed\Cache\CacheInterface|null $cache Cache implementation.
	 * @param int $ttl Cache TTL in seconds.
	 * @return $this
	 */
	public function setCache(?CacheInterface $cache, int $ttl = 3600) {
		$this->cache = $cache;
		$this->cacheTtl = $ttl;

		return $this;
	}

	/**
	 * Match a URL against all providers.
	 *
	 * @param string $url The URL to match.
	 * @return \MediaEmbed\Matcher\MatchResult|null Match result or null if no match.
	 */
	public function match(string $url): ?MatchResult {
		$this->buildIndexIfNeeded();

		// Try fast path first using domain index
		$domain = $this->extractDomain($url);
		if ($domain !== null && isset($this->domainIndex[$domain])) {
			$result = $this->matchAgainstProviders($url, $this->domainIndex[$domain]);
			if ($result !== null) {
				return $result;
			}
		}

		// Fall back to checking all providers
		return $this->matchAgainstProviders($url, array_keys($this->providers));
	}

	/**
	 * Match URL against a specific list of provider slugs.
	 *
	 * @param string $url The URL to match.
	 * @param array<string> $slugs Provider slugs to check.
	 * @return \MediaEmbed\Matcher\MatchResult|null
	 */
	private function matchAgainstProviders(string $url, array $slugs): ?MatchResult {
		$checkedSlugs = [];

		foreach ($slugs as $slug) {
			// Avoid checking the same provider twice
			if (isset($checkedSlugs[$slug])) {
				continue;
			}
			$checkedSlugs[$slug] = true;

			if (!isset($this->providers[$slug])) {
				continue;
			}

			$provider = $this->providers[$slug];
			$patterns = (array)($provider['url-match'] ?? []);

			foreach ($patterns as $pattern) {
				if (preg_match('~' . $pattern . '~imu', $url, $matches)) {
					return new MatchResult($slug, $matches, $provider);
				}
			}
		}

		return null;
	}

	/**
	 * Build the domain index for fast lookups.
	 *
	 * @return void
	 */
	private function buildIndexIfNeeded(): void {
		if ($this->indexBuilt) {
			return;
		}

		// Try to load from cache first
		if ($this->cache !== null) {
			$cached = $this->cache->get(static::CACHE_KEY);
			if (is_array($cached)) {
				$this->domainIndex = $cached;
				$this->indexBuilt = true;

				return;
			}
		}

		$this->domainIndex = [];

		foreach ($this->providers as $slug => $provider) {
			$patterns = (array)($provider['url-match'] ?? []);

			foreach ($patterns as $pattern) {
				$domains = $this->extractDomainsFromPattern($pattern);
				foreach ($domains as $domain) {
					if (!isset($this->domainIndex[$domain])) {
						$this->domainIndex[$domain] = [];
					}
					if (!in_array($slug, $this->domainIndex[$domain], true)) {
						$this->domainIndex[$domain][] = $slug;
					}
				}
			}
		}

		// Store in cache
		if ($this->cache !== null) {
			$this->cache->set(static::CACHE_KEY, $this->domainIndex, $this->cacheTtl);
		}

		$this->indexBuilt = true;
	}

	/**
	 * Extract domain from a URL.
	 *
	 * @param string $url
	 * @return string|null
	 */
	private function extractDomain(string $url): ?string {
		$parsed = parse_url($url);
		if (!isset($parsed['host'])) {
			return null;
		}

		$host = $parsed['host'];

		// Remove www. prefix for matching
		if (str_starts_with($host, 'www.')) {
			$host = substr($host, 4);
		}

		return strtolower($host);
	}

	/**
	 * Extract likely domains from a regex pattern.
	 *
	 * @param string $pattern
	 * @return array<string>
	 */
	private function extractDomainsFromPattern(string $pattern): array {
		$domains = [];

		// Try to extract literal domains from patterns
		// Look for common patterns like "youtube\.com" or "youtu\.be"
		if (preg_match_all('/([a-z0-9-]+(?:\\\\\.[a-z0-9-]+)+)/i', $pattern, $matches)) {
			foreach ($matches[1] as $match) {
				// Unescape the domain
				$domain = str_replace('\\.', '.', $match);
				$domain = strtolower($domain);

				// Remove www. prefix
				if (str_starts_with($domain, 'www.')) {
					$domain = substr($domain, 4);
				}

				$domains[] = $domain;
			}
		}

		return array_unique($domains);
	}

	/**
	 * Get the domain index (for debugging/testing).
	 *
	 * @return array<string, array<string>>
	 */
	public function getDomainIndex(): array {
		$this->buildIndexIfNeeded();

		return $this->domainIndex;
	}

	/**
	 * Get the number of indexed domains.
	 *
	 * @return int
	 */
	public function getIndexedDomainCount(): int {
		$this->buildIndexIfNeeded();

		return count($this->domainIndex);
	}

}
