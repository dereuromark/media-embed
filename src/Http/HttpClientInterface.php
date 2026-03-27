<?php

declare(strict_types=1);

namespace MediaEmbed\Http;

/**
 * Interface for HTTP client implementations.
 *
 * This interface allows for dependency injection of HTTP clients,
 * enabling easy mocking in tests and alternative implementations.
 */
interface HttpClientInterface {

	/**
	 * Perform a GET request.
	 *
	 * @param string $url The URL to fetch.
	 * @param array<string, mixed> $options Request options (implementation-specific).
	 * @return string|null The response body or null on failure.
	 */
	public function get(string $url, array $options = []): ?string;

}
