<?php

declare(strict_types=1);

namespace MediaEmbed\Http;

/**
 * Validates URLs before network fetches.
 *
 * @internal
 */
final class UrlSafety {

	/**
	 * @param string $url URL to validate.
	 * @return bool
	 */
	public static function isPublicHttpUrl(string $url): bool {
		$parts = parse_url($url);
		if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
			return false;
		}

		if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
			return false;
		}

		return self::isPublicHost($parts['host']);
	}

	/**
	 * @param string $host URL host.
	 * @return bool
	 */
	private static function isPublicHost(string $host): bool {
		$host = strtolower(trim($host, '[]'));
		if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost')) {
			return false;
		}

		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
		}

		if (preg_match('/^(?:0x[0-9a-f]+|[0-9.]+)$/i', $host)) {
			return false;
		}

		return true;
	}

}
