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

		return $this->parseOEmbedLink($html);
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
		$params = [];
		if ($maxWidth !== null) {
			$params['maxwidth'] = $maxWidth;
		}
		if ($maxHeight !== null) {
			$params['maxheight'] = $maxHeight;
		}

		if ($params) {
			$separator = strpos($endpointUrl, '?') !== false ? '&' : '?';
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
	 * @return string|null The oEmbed URL or null if not found.
	 */
	private function parseOEmbedLink(string $html): ?string {
		// Match link tags with oembed type
		$pattern = '/<link[^>]+type=["\']application\/json\+oembed["\'][^>]*>/i';
		if (!preg_match($pattern, $html, $match)) {
			// Try alternate pattern with type before rel
			$pattern = '/<link[^>]+rel=["\']alternate["\'][^>]+type=["\']application\/json\+oembed["\'][^>]*>/i';
			if (!preg_match($pattern, $html, $match)) {
				return null;
			}
		}

		$linkTag = $match[0];

		// Extract href attribute
		if (!preg_match('/href=["\']([^"\']+)["\']/i', $linkTag, $hrefMatch)) {
			return null;
		}

		$href = $hrefMatch[1];

		// Decode HTML entities
		return html_entity_decode($href, ENT_QUOTES | ENT_HTML5);
	}

}
