<?php

declare(strict_types=1);

namespace MediaEmbed\OEmbed;

use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\Http\StreamHttpClient;
use MediaEmbed\Http\UrlSafety;
use Psr\SimpleCache\CacheInterface;
use SimpleXMLElement;

/**
 * Discovers and fetches oEmbed data from URLs.
 *
 * This class can:
 * 1. Auto-discover oEmbed endpoints from HTML link tags
 * 2. Fetch and parse oEmbed JSON responses
 *
 * @see https://oembed.com/
 */
final class OEmbedDiscovery {

	/**
	 * HTTP client for fetching URLs.
	 */
	private HttpClientInterface $httpClient;

	/**
	 * @param \MediaEmbed\Http\HttpClientInterface|null $httpClient HTTP client to use.
	 * @param \Psr\SimpleCache\CacheInterface|null $cache Optional cache for discovered endpoints and responses.
	 * @param int $cacheTtl Default cache TTL in seconds.
	 * @param array<string, string> $endpoints Optional host-to-endpoint templates. Use `{url}` as source URL placeholder.
	 */
	public function __construct(
		?HttpClientInterface $httpClient = null,
		private readonly ?CacheInterface $cache = null,
		private readonly int $cacheTtl = 3600,
		private readonly array $endpoints = [],
	) {
		$this->httpClient = $httpClient ?? new StreamHttpClient();
	}

	/**
	 * Discover and fetch oEmbed data for a URL.
	 *
	 * This will:
	 * 1. Fetch the URL's HTML
	 * 2. Look for oEmbed link tags
	 * 3. Fetch the oEmbed endpoint
	 * 4. Parse and return the response
	 *
	 * @param string $url The URL to discover oEmbed for.
	 * @param int|null $maxWidth Maximum width for the embed.
	 * @param int|null $maxHeight Maximum height for the embed.
	 * @return \MediaEmbed\OEmbed\OEmbedResponse|null Response or null if not found.
	 */
	public function discover(string $url, ?int $maxWidth = null, ?int $maxHeight = null): ?OEmbedResponse {
		$endpointUrl = $this->directEndpoint($url) ?? $this->discoverEndpoint($url);
		if ($endpointUrl === null) {
			return null;
		}

		return $this->fetch($endpointUrl, $maxWidth, $maxHeight);
	}

	/**
	 * Discover the oEmbed endpoint URL from HTML.
	 *
	 * @param string $url The page URL to check.
	 * @return string|null The oEmbed endpoint URL or null if not found.
	 */
	public function discoverEndpoint(string $url): ?string {
		$cacheKey = $this->cacheKey('endpoint', [$url]);
		if ($this->cache !== null) {
			$cached = $this->cache->get($cacheKey);
			if (is_string($cached)) {
				return $cached;
			}
		}

		$html = $this->httpClient->get($url);
		if ($html === null) {
			return null;
		}

		$endpointUrl = $this->parseOEmbedLink($html, $url);
		if ($endpointUrl !== null && $this->cache !== null) {
			$this->cache->set($cacheKey, $endpointUrl, $this->cacheTtl);
		}

		return $endpointUrl;
	}

	/**
	 * Fetch oEmbed data directly from an endpoint URL.
	 *
	 * @param string $endpointUrl The oEmbed endpoint URL.
	 * @param int|null $maxWidth Maximum width for the embed.
	 * @param int|null $maxHeight Maximum height for the embed.
	 * @return \MediaEmbed\OEmbed\OEmbedResponse|null Response or null on failure.
	 */
	public function fetch(string $endpointUrl, ?int $maxWidth = null, ?int $maxHeight = null): ?OEmbedResponse {
		if (!$this->isSafeEndpointUrl($endpointUrl)) {
			return null;
		}

		$params = [];
		if ($maxWidth !== null) {
			$params['maxwidth'] = $maxWidth;
		}
		if ($maxHeight !== null) {
			$params['maxheight'] = $maxHeight;
		}

		if ($params) {
			$separator = str_contains($endpointUrl, '?') ? '&' : '?';
			$endpointUrl .= $separator . http_build_query($params, '', '&');
		}

		$cacheKey = $this->cacheKey('response', [$endpointUrl]);
		if ($this->cache !== null) {
			$cached = $this->cache->get($cacheKey);
			if ($cached instanceof OEmbedResponse) {
				return $cached;
			}
		}

		$content = $this->httpClient->get($endpointUrl);
		if ($content === null) {
			return null;
		}

		$response = $this->parseResponse($content);
		if ($response === null) {
			return null;
		}

		if ($this->cache !== null) {
			$this->cache->set($cacheKey, $response, $response->cacheAge ?? $this->cacheTtl);
		}

		return $response;
	}

	/**
	 * @param string $content Response body.
	 * @return \MediaEmbed\OEmbed\OEmbedResponse|null
	 */
	private function parseResponse(string $content): ?OEmbedResponse {
		$data = json_decode($content, true);
		if (is_array($data)) {
			return OEmbedResponse::fromArray($data);
		}

		$data = $this->parseXmlResponse($content);
		if ($data === null) {
			return null;
		}

		return OEmbedResponse::fromArray($data);
	}

	/**
	 * @param string $content XML response body.
	 * @return array<string, mixed>|null
	 */
	private function parseXmlResponse(string $content): ?array {
		$previous = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($content);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if (!$xml instanceof SimpleXMLElement) {
			return null;
		}

		$data = [];
		foreach ($xml->children() as $key => $value) {
			$data[$this->camelToSnake($key)] = (string)$value;
		}

		return $data;
	}

	/**
	 * @param string $key XML element name.
	 * @return string
	 */
	private function camelToSnake(string $key): string {
		$key = preg_replace('/(?<!^)[A-Z]/', '_$0', $key) ?? $key;

		return strtolower($key);
	}

	/**
	 * @param string $url Source URL.
	 * @return string|null
	 */
	private function directEndpoint(string $url): ?string {
		$parts = parse_url($url);
		$host = is_array($parts) ? strtolower($parts['host'] ?? '') : '';
		if ($host === '') {
			return null;
		}

		foreach ($this->endpoints as $domain => $endpoint) {
			$domain = strtolower($domain);
			if ($host !== $domain && !str_ends_with($host, '.' . $domain)) {
				continue;
			}

			$endpointUrl = str_replace('{url}', rawurlencode($url), $endpoint);
			if ($this->isSafeEndpointUrl($endpointUrl)) {
				return $endpointUrl;
			}
		}

		return null;
	}

	/**
	 * Parse oEmbed link from HTML.
	 *
	 * Looks for: <link rel="alternate" type="application/json+oembed" href="..." />
	 *
	 * @param string $html The HTML to parse.
	 * @param string $baseUrl Source page URL.
	 * @return string|null The oEmbed URL or null if not found.
	 */
	private function parseOEmbedLink(string $html, string $baseUrl): ?string {
		if (!preg_match_all('/<link\\s+[^>]*>/i', $html, $matches)) {
			return null;
		}

		foreach ($matches[0] as $linkTag) {
			$attributes = $this->parseLinkAttributes($linkTag);
			$rel = strtolower($attributes['rel'] ?? '');
			$relTokens = preg_split('/\\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY);

			if (!in_array('alternate', $relTokens ?: [], true)) {
				continue;
			}
			if (strtolower($attributes['type'] ?? '') !== 'application/json+oembed') {
				continue;
			}
			if (empty($attributes['href'])) {
				continue;
			}

			$href = html_entity_decode($attributes['href'], ENT_QUOTES | ENT_HTML5);
			$url = $this->resolveEndpointUrl($href, $baseUrl);
			if (!$this->isSafeEndpointUrl($url)) {
				continue;
			}

			return $url;
		}

		return null;
	}

	/**
	 * Parse quoted attributes from a link tag.
	 *
	 * @param string $linkTag Link tag HTML.
	 * @return array<string, string>
	 */
	private function parseLinkAttributes(string $linkTag): array {
		if (!preg_match_all('/([a-z][a-z0-9:_-]*)\\s*=\\s*([\'"])(.*?)\\2/is', $linkTag, $matches, PREG_SET_ORDER)) {
			return [];
		}

		$attributes = [];
		foreach ($matches as $match) {
			$attributes[strtolower($match[1])] = $match[3];
		}

		return $attributes;
	}

	/**
	 * Resolve oEmbed href values against the source page URL.
	 *
	 * @param string $href Link href from the oEmbed tag.
	 * @param string $baseUrl Source page URL.
	 * @return string
	 */
	private function resolveEndpointUrl(string $href, string $baseUrl): string {
		if (parse_url($href, PHP_URL_SCHEME) !== null) {
			return $href;
		}

		$base = parse_url($baseUrl);
		if (empty($base['scheme']) || empty($base['host'])) {
			return $href;
		}

		$authority = $base['scheme'] . '://' . $base['host'];
		if (!empty($base['port'])) {
			$authority .= ':' . $base['port'];
		}

		if (str_starts_with($href, '//')) {
			return $base['scheme'] . ':' . $href;
		}

		if (str_starts_with($href, '/')) {
			return $authority . $href;
		}

		$path = $base['path'] ?? '/';
		$directory = rtrim(substr($path, 0, (int)strrpos($path, '/') + 1), '/');

		return $authority . $directory . '/' . $href;
	}

	/**
	 * Check if an oEmbed endpoint URL is safe to fetch.
	 *
	 * @param string $url Endpoint URL.
	 * @return bool
	 */
	private function isSafeEndpointUrl(string $url): bool {
		return UrlSafety::isPublicHttpUrl($url);
	}

	/**
	 * @param string $type Cache key type.
	 * @param array<string> $parts Cache key parts.
	 * @return string
	 */
	private function cacheKey(string $type, array $parts): string {
		return 'media_embed_oembed_' . $type . '_' . hash('sha256', implode("\n", $parts));
	}

}
