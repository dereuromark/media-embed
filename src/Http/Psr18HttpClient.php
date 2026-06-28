<?php

declare(strict_types=1);

namespace MediaEmbed\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Adapts any PSR-18 HTTP client to the internal HttpClientInterface.
 *
 * This lets applications plug in their existing PSR-18 client (Guzzle, Symfony
 * HttpClient, etc.) while the bundled StreamHttpClient remains the zero-config default.
 */
final class Psr18HttpClient implements HttpClientInterface {

	/**
	 * @param \Psr\Http\Client\ClientInterface $client PSR-18 client.
	 * @param \Psr\Http\Message\RequestFactoryInterface $requestFactory PSR-17 request factory.
	 */
	public function __construct(
		private readonly ClientInterface $client,
		private readonly RequestFactoryInterface $requestFactory,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $url, array $options = []): ?string {
		if (!UrlSafety::isPublicHttpUrl($url)) {
			return null;
		}

		$request = $this->requestFactory->createRequest('GET', $url);
		if (isset($options['user_agent'])) {
			$request = $request->withHeader('User-Agent', (string)$options['user_agent']);
		}

		try {
			$response = $this->client->sendRequest($request);
		} catch (ClientExceptionInterface) {
			return null;
		}

		$status = $response->getStatusCode();
		if ($status < 200 || $status >= 300) {
			return null;
		}

		return (string)$response->getBody();
	}

}
