<?php

declare(strict_types=1);

namespace MediaEmbed\Http;

/**
 * HTTP client using PHP stream functions.
 *
 * This is the default HTTP client that uses file_get_contents with stream context.
 */
class StreamHttpClient implements HttpClientInterface {

	/**
	 * Default timeout in seconds.
	 */
	protected int $timeout;

	/**
	 * @param int $timeout Request timeout in seconds.
	 * @param int $maxBytes Maximum response size in bytes.
	 * @param int $maxRedirects Maximum number of redirects to follow.
	 * @param string $userAgent User-Agent header value.
	 */
	public function __construct(
		int $timeout = 5,
		protected int $maxBytes = 1048576,
		protected int $maxRedirects = 3,
		protected string $userAgent = 'dereuromark/media-embed',
	) {
		$this->timeout = $timeout;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $url, array $options = []): ?string {
		if (!$this->isSafeUrl($url)) {
			return null;
		}

		$timeout = (int)($options['timeout'] ?? $this->timeout);
		$maxBytes = (int)($options['max_bytes'] ?? $this->maxBytes);
		$maxRedirects = (int)($options['max_redirects'] ?? $this->maxRedirects);
		$userAgent = $this->headerValue((string)($options['user_agent'] ?? $this->userAgent));

		for ($redirects = 0; $redirects <= $maxRedirects; $redirects++) {
			$context = stream_context_create([
				'http' => [
					'header' => "Connection: close\r\nUser-Agent: " . $userAgent,
					'timeout' => $timeout,
					'follow_location' => 0,
					'ignore_errors' => true,
				],
			]);

			$stream = @fopen($url, 'rb', false, $context);
			if ($stream === false) {
				return null;
			}

			$metadata = stream_get_meta_data($stream);
			$content = stream_get_contents($stream, $maxBytes + 1);
			fclose($stream);

			if ($content === false || strlen($content) > $maxBytes) {
				return null;
			}

			$redirectUrl = $this->redirectUrl($url, $metadata['wrapper_data'] ?? []);
			if ($redirectUrl === null) {
				if (!$this->isSuccessful($metadata['wrapper_data'] ?? [])) {
					return null;
				}

				return $content;
			}

			if ($redirects === $maxRedirects || !$this->isSafeUrl($redirectUrl)) {
				return null;
			}

			$url = $redirectUrl;
		}

		return null;
	}

	/**
	 * @param string $url Current URL.
	 * @param mixed $headers Response headers from stream metadata.
	 * @return string|null
	 */
	protected function redirectUrl(string $url, mixed $headers): ?string {
		if (!is_array($headers) || !$this->isRedirectResponse($headers)) {
			return null;
		}

		foreach ($headers as $header) {
			if (!is_string($header) || !str_contains($header, ':')) {
				continue;
			}

			[$name, $value] = explode(':', $header, 2);
			if (strtolower(trim($name)) !== 'location') {
				continue;
			}

			return $this->resolveRedirectUrl(trim($value), $url);
		}

		return null;
	}

	/**
	 * @param array<int|string, mixed> $headers
	 * @return bool
	 */
	protected function isRedirectResponse(array $headers): bool {
		foreach ($headers as $header) {
			if (!is_string($header) || !preg_match('/^HTTP\/\S+\s+([0-9]{3})\b/i', $header, $matches)) {
				continue;
			}

			$status = (int)$matches[1];

			return $status >= 300 && $status < 400;
		}

		return false;
	}

	/**
	 * Whether the final (non-redirect) response carries a 2xx status code.
	 *
	 * @param array<int|string, mixed> $headers
	 * @return bool
	 */
	protected function isSuccessful(array $headers): bool {
		$status = null;
		foreach ($headers as $header) {
			if (!is_string($header) || !preg_match('/^HTTP\/\S+\s+([0-9]{3})\b/i', $header, $matches)) {
				continue;
			}

			$status = (int)$matches[1];
		}

		return $status !== null && $status >= 200 && $status < 300;
	}

	/**
	 * @param string $location Redirect location header value.
	 * @param string $baseUrl Current URL.
	 * @return string
	 */
	protected function resolveRedirectUrl(string $location, string $baseUrl): string {
		if (parse_url($location, PHP_URL_SCHEME) !== null) {
			return $location;
		}

		$base = parse_url($baseUrl);
		if (empty($base['scheme']) || empty($base['host'])) {
			return $location;
		}

		$authority = $base['scheme'] . '://' . $base['host'];
		if (!empty($base['port'])) {
			$authority .= ':' . $base['port'];
		}

		if (str_starts_with($location, '//')) {
			return $base['scheme'] . ':' . $location;
		}

		if (str_starts_with($location, '/')) {
			return $authority . $location;
		}

		$path = $base['path'] ?? '/';
		$directory = rtrim(substr($path, 0, (int)strrpos($path, '/') + 1), '/');

		return $authority . $directory . '/' . $location;
	}

	/**
	 * @param string $value Header value.
	 * @return string
	 */
	protected function headerValue(string $value): string {
		return str_replace(["\r", "\n"], '', $value);
	}

	/**
	 * @param string $url URL to validate.
	 * @return bool
	 */
	protected function isSafeUrl(string $url): bool {
		return UrlSafety::isPublicHttpUrl($url);
	}

}
