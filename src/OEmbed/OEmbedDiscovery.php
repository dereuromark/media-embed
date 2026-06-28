<?php

declare(strict_types=1);

namespace MediaEmbed\OEmbed;

use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\Http\StreamHttpClient;

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
	 */
	public function __construct(?HttpClientInterface $httpClient = null) {
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
		$endpointUrl = $this->discoverEndpoint($url);
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
		$html = $this->httpClient->get($url);
		if ($html === null) {
			return null;
		}

		return $this->parseOEmbedLink($html, $url);
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
			$endpointUrl .= $separator . http_build_query($params);
		}

		$json = $this->httpClient->get($endpointUrl);
		if ($json === null) {
			return null;
		}

		$data = json_decode($json, true);
		if (!is_array($data)) {
			return null;
		}

		return OEmbedResponse::fromArray($data);
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
		$parts = parse_url($url);
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return false;
		}

		if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
			return false;
		}

		$host = strtolower($parts['host']);
		if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
			return false;
		}

		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
		}

		return true;
	}

}
