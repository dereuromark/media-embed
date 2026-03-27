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
	 */
	public function __construct(int $timeout = 5) {
		$this->timeout = $timeout;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $url, array $options = []): ?string {
		$timeout = $options['timeout'] ?? $this->timeout;

		$context = stream_context_create([
			'http' => [
				'header' => 'Connection: close',
				'timeout' => $timeout,
			],
		]);

		$content = @file_get_contents($url, false, $context);
		if ($content === false) {
			return null;
		}

		return $content;
	}

}
