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
	 */
	public function __construct(int $timeout = 5, protected int $maxBytes = 1048576) {
		$this->timeout = $timeout;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $url, array $options = []): ?string {
		if (!$this->isSafeUrl($url)) {
			return null;
		}

		$timeout = $options['timeout'] ?? $this->timeout;
		$maxBytes = $options['max_bytes'] ?? $this->maxBytes;

		$context = stream_context_create([
			'http' => [
				'header' => 'Connection: close',
				'timeout' => $timeout,
				'follow_location' => 0,
				'ignore_errors' => false,
			],
		]);

		$stream = @fopen($url, 'rb', false, $context);
		if ($stream === false) {
			return null;
		}

		$content = stream_get_contents($stream, $maxBytes + 1);
		fclose($stream);
		if ($content === false || strlen($content) > $maxBytes) {
			return null;
		}

		return $content;
	}

	/**
	 * @param string $url URL to validate.
	 * @return bool
	 */
	protected function isSafeUrl(string $url): bool {
		return UrlSafety::isPublicHttpUrl($url);
	}

}
