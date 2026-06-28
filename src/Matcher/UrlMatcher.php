<?php

declare(strict_types=1);

namespace MediaEmbed\Matcher;

use Psr\SimpleCache\CacheInterface;

/**
 * URL matcher with optional domain-based caching for faster lookups.
 *
 * This class provides optimized URL matching by building a domain index
 * to reduce the number of regex patterns that need to be tested.
 */
final class UrlMatcher {

	/**
	 * Cache key for the domain index.
	 *
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
	 * Provider slugs whose URL patterns do not expose a literal domain.
	 *
	 * @var array<string>
	 */
	private array $unindexedSlugs = [];

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
	 * @param \Psr\SimpleCache\CacheInterface|null $cache Optional cache for persisting domain index.
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
		$this->unindexedSlugs = [];

		// Clear cached index when providers change
		if ($this->cache !== null) {
			$this->cache->delete(self::CACHE_KEY);
		}

		return $this;
	}

	/**
	 * Set the cache implementation.
	 *
	 * @param \Psr\SimpleCache\CacheInterface|null $cache Cache implementation.
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
		$url = $this->normalizeUrl($url);
		if ($url === null) {
			return null;
		}

		$this->buildIndexIfNeeded();

		// Try fast path first using domain index
		$domain = $this->extractDomain($url);
		if ($domain === null) {
			return null;
		}

		$slugs = $this->slugsForDomain($domain);
		$slugs = array_merge($slugs, $this->unindexedSlugs);
		if ($slugs) {
			$result = $this->matchAgainstProviders($url, $domain, $slugs);
			if ($result !== null) {
				return $result;
			}
		}

		// Fall back to checking all providers, but reject matches from different hosts.
		return $this->matchAgainstProviders($url, $domain, array_keys($this->providers));
	}

	/**
	 * Normalize and validate a URL before matching.
	 *
	 * @param string $url The URL to normalize.
	 * @return string|null
	 */
	private function normalizeUrl(string $url): ?string {
		$url = trim($url);
		if ($url === '' || preg_match('/\\s/', $url)) {
			return null;
		}

		$parsed = parse_url($url);
		if (!is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
			return null;
		}

		$scheme = strtolower($parsed['scheme']);
		if ($scheme !== 'http' && $scheme !== 'https') {
			return null;
		}

		return $url;
	}

	/**
	 * Normalize and validate a URL before matching.
	 *
	 * @param string $url The URL to normalize.
	 * @return string|null
	 */
	private function normalizeUrl(string $url): ?string {
		$url = trim($url);
		if ($url === '' || preg_match('/\\s/', $url)) {
			return null;
		}

		$parsed = parse_url($url);
		if (!is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
			return null;
		}

		$scheme = strtolower($parsed['scheme']);
		if ($scheme !== 'http' && $scheme !== 'https') {
			return null;
		}

		return $url;
	}

	/**
	 * Match URL against a specific list of provider slugs.
	 *
	 * @param string $url The URL to match.
	 * @param string $domain Normalized input URL domain.
	 * @param array<string> $slugs Provider slugs to check.
	 * @return \MediaEmbed\Matcher\MatchResult|null
	 */
	private function matchAgainstProviders(string $url, string $domain, array $slugs): ?MatchResult {
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
					if (!$this->matchBelongsToDomain($matches[0], $domain)) {
						continue;
					}

					return new MatchResult($slug, $matches, $provider);
				}
			}
		}

		return null;
	}

	/**
	 * Get indexed provider slugs that can match a normalized domain.
	 *
	 * @param string $domain Normalized input URL domain.
	 * @return array<string>
	 */
	private function slugsForDomain(string $domain): array {
		$slugs = [];

		foreach ($this->domainIndex as $indexedDomain => $indexedSlugs) {
			if ($domain !== $indexedDomain && !str_ends_with($domain, '.' . $indexedDomain)) {
				continue;
			}

			$slugs = array_merge($slugs, $indexedSlugs);
		}

		return array_values(array_unique($slugs));
	}

	/**
	 * Check that a regex match belongs to the original input URL domain.
	 *
	 * @param string $match Matched URL substring.
	 * @param string $domain Normalized input URL domain.
	 * @return bool
	 */
	private function matchBelongsToDomain(string $match, string $domain): bool {
		$matchDomain = $this->extractDomain($match);
		if ($matchDomain === null) {
			return true;
		}

		return $matchDomain === $domain;
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
			$cached = $this->cache->get(self::CACHE_KEY);
			if (is_array($cached)) {
				$this->domainIndex = $cached;
				$this->unindexedSlugs = $this->extractUnindexedSlugs();
				$this->indexBuilt = true;

				return;
			}
		}

		$this->domainIndex = [];
		$this->unindexedSlugs = [];

		foreach ($this->providers as $slug => $provider) {
			$patterns = (array)($provider['url-match'] ?? []);
			$hasIndexedDomain = false;

			foreach ($patterns as $pattern) {
				$domains = $this->extractDomainsFromPattern($pattern);
				if (!$domains) {
					$this->unindexedSlugs[] = $slug;

					continue;
				}

				foreach ($domains as $domain) {
					$hasIndexedDomain = true;
					if (!isset($this->domainIndex[$domain])) {
						$this->domainIndex[$domain] = [];
					}
					if (!in_array($slug, $this->domainIndex[$domain], true)) {
						$this->domainIndex[$domain][] = $slug;
					}
				}
			}

			if (!$hasIndexedDomain) {
				$this->unindexedSlugs[] = $slug;
			}
		}

		$this->unindexedSlugs = array_values(array_unique($this->unindexedSlugs));

		// Store in cache
		if ($this->cache !== null) {
			$this->cache->set(self::CACHE_KEY, $this->domainIndex, $this->cacheTtl);
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

		return $this->normalizeHost($parsed['host']);
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
				$domains[] = $this->normalizeHost($domain);
			}
		}

		return array_unique($domains);
	}

	/**
	 * Normalize a hostname by lowercasing and removing www. prefix.
	 *
	 * @param string $host
	 * @return string
	 */
	private function normalizeHost(string $host): string {
		$host = strtolower($host);

		if (str_starts_with($host, 'www.')) {
			return substr($host, 4);
		}

		return $host;
	}

	/**
	 * Extract provider slugs whose URL patterns do not expose a literal domain.
	 *
	 * @return array<string>
	 */
	private function extractUnindexedSlugs(): array {
		$slugs = [];

		foreach ($this->providers as $slug => $provider) {
			$patterns = (array)($provider['url-match'] ?? []);
			foreach ($patterns as $pattern) {
				if ($this->extractDomainsFromPattern($pattern)) {
					continue 2;
				}
			}

			$slugs[] = $slug;
		}

		return $slugs;
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
