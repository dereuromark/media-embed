<?php

declare(strict_types=1);

namespace MediaEmbed\Exception;

/**
 * Exception thrown when a URL cannot be parsed or is not supported.
 */
class InvalidUrlException extends MediaEmbedException {

	protected string $url;

	/**
	 * @param string $url The URL that could not be parsed.
	 * @param string $message Optional custom message.
	 */
	public function __construct(string $url, string $message = '') {
		$this->url = $url;
		if ($message === '') {
			$message = sprintf('URL "%s" is not supported or could not be parsed.', $url);
		}
		parent::__construct($message);
	}

	/**
	 * Get the URL that caused the exception.
	 *
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}

}
