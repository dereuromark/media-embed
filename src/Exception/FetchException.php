<?php

declare(strict_types=1);

namespace MediaEmbed\Exception;

/**
 * Exception thrown when an HTTP fetch operation fails.
 */
class FetchException extends MediaEmbedException {

	protected string $url;

	/**
	 * @param string $url The URL that failed to fetch.
	 * @param string $message Optional custom message.
	 */
	public function __construct(string $url, string $message = '') {
		$this->url = $url;
		if ($message === '') {
			$message = sprintf('Failed to fetch content from URL: %s', $url);
		}
		parent::__construct($message);
	}

	/**
	 * Get the URL that failed to fetch.
	 *
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}

}
