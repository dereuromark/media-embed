<?php

declare(strict_types=1);

namespace MediaEmbed;

use InvalidArgumentException;
use MediaEmbed\Exception\FetchException;
use MediaEmbed\Exception\InvalidUrlException;
use MediaEmbed\Exception\ProviderConfigException;
use MediaEmbed\Exception\ProviderNotFoundException;
use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\Http\Psr18HttpClient;
use MediaEmbed\Http\StreamHttpClient;
use MediaEmbed\Matcher\MatchResult;
use MediaEmbed\Matcher\UrlMatcher;
use MediaEmbed\Object\MediaObject;
use MediaEmbed\OEmbed\OEmbedDiscovery;
use MediaEmbed\OEmbed\OEmbedResponse;
use MediaEmbed\Provider\PhpFileLoader;
use MediaEmbed\Provider\ProviderCollection;
use MediaEmbed\Provider\ProviderConfig;
use MediaEmbed\Provider\ProviderLoaderInterface;
use MediaEmbed\Slugger\SluggerInterface;
use MediaEmbed\Slugger\UrlifySlugger;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * A utility that generates HTML embed tags for audio or video located on a given URL.
 * It also parses and validates given media URLs.
 *
 * @author MarkScherer
 * @license MIT
 */
class MediaEmbed {

	/**
	 * Last URL match result.
	 *
	 * @var array<string>|null
	 */
	protected ?array $lastMatch = null;

	/**
	 * Registered provider configurations.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $providers = [];

	/**
	 * Configuration options passed to MediaObject.
	 *
	 * @var array<string, mixed>
	 */
	protected array $config = [];

	/**
	 * HTTP client for fetching remote content.
	 */
	protected HttpClientInterface $httpClient;

	/**
	 * Slugger used to derive provider slugs from names.
	 */
	protected SluggerInterface $slugger;

	/**
	 * URL matcher with domain-based caching.
	 */
	protected ?UrlMatcher $urlMatcher = null;

	/**
	 * Optional cache for the URL matcher domain index.
	 */
	protected ?CacheInterface $cache = null;

	/**
	 * URL matcher cache TTL in seconds.
	 */
	protected int $cacheTtl = 3600;

	/**
	 * Get the HTTP client.
	 *
	 * @return \MediaEmbed\Http\HttpClientInterface
	 */
	public function getHttpClient(): HttpClientInterface {
		return $this->httpClient;
	}

	/**
	 * Set the HTTP client.
	 *
	 * @param \MediaEmbed\Http\HttpClientInterface $httpClient
	 * @return $this
	 */
	public function setHttpClient(HttpClientInterface $httpClient) {
		$this->httpClient = $httpClient;

		return $this;
	}

	/**
	 * Loads stubs
	 *
	 * @param array<string, mixed> $config
	 * @param string|null $stubsPath
	 * @param \MediaEmbed\Http\HttpClientInterface|null $httpClient
	 * @param \MediaEmbed\Provider\ProviderLoaderInterface|null $providerLoader
	 * @param \Psr\SimpleCache\CacheInterface|null $cache
	 * @param int $cacheTtl
	 * @param \MediaEmbed\Slugger\SluggerInterface|null $slugger
	 * @param \Psr\Http\Client\ClientInterface|null $psrHttpClient Optional PSR-18 client (used when no $httpClient is given).
	 * @param \Psr\Http\Message\RequestFactoryInterface|null $requestFactory PSR-17 request factory (required with $psrHttpClient).
	 */
	public function __construct(
		array $config = [],
		?string $stubsPath = null,
		?HttpClientInterface $httpClient = null,
		?ProviderLoaderInterface $providerLoader = null,
		?CacheInterface $cache = null,
		int $cacheTtl = 3600,
		?SluggerInterface $slugger = null,
		?ClientInterface $psrHttpClient = null,
		?RequestFactoryInterface $requestFactory = null,
	) {
		if (($psrHttpClient !== null) !== ($requestFactory !== null)) {
			throw new InvalidArgumentException('A PSR-18 client and a PSR-17 request factory must be provided together.');
		}
		if ($httpClient === null && $psrHttpClient !== null && $requestFactory !== null) {
			$httpClient = new Psr18HttpClient($psrHttpClient, $requestFactory);
		}
		$this->httpClient = $httpClient ?? new StreamHttpClient();
		$this->slugger = $slugger ?? new UrlifySlugger();
		$this->cache = $cache;
		$this->cacheTtl = $cacheTtl;

		// Use provided loader or default to PhpFileLoader
		if ($providerLoader !== null) {
			$stubs = $providerLoader->load();
		} else {
			if ($stubsPath === null) {
				$stubsPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'stubs.php';
			}
			$loader = new PhpFileLoader($stubsPath);
			$stubs = $loader->load();
		}
		$this->registerProviders($stubs);
		$this->config = $config + $this->config;

		// Load custom providers from config file if specified
		if (!empty($config['providers_config'])) {
			$this->loadProvidersFromFile($config['providers_config']);
		}

		// Add custom providers from config array
		if (!empty($config['custom_providers']) && is_array($config['custom_providers'])) {
			foreach ($config['custom_providers'] as $provider) {
				$this->addProviderConfig(ProviderConfig::fromArray($provider));
			}
		}
	}

	/**
	 * Prepare embed video from different video hosts.
	 *
	 * @param string $id
	 * @param string $host
	 * @param array<string, mixed> $config
	 *
	 * @return \MediaEmbed\Object\MediaObject|null
	 */
	public function parseId(string $id, string $host, array $config = []): ?MediaObject {
		if (!$id || !$host) {
			return null;
		}

		if (empty($this->providers[$host])) {
			return null;
		}
		$stub = $this->providers[$host];
		$config += $this->config;

		$stub['id-template'] = $stub['id'] ?? null;
		$stub['id'] = $id;
		$stub['reverse'] = true;

		$object = $this->object($stub, $config);
		if (!$object->isSourceResolved()) {
			return null;
		}

		return $object;
	}

	/**
	 * Prepare embed video from different video hosts or throw exception on failure.
	 *
	 * @param string $id
	 * @param string $host
	 * @param array<string, mixed> $config
	 *
	 * @throws \MediaEmbed\Exception\InvalidUrlException When ID or host is empty.
	 * @throws \MediaEmbed\Exception\ProviderNotFoundException When host is not found.
	 * @return \MediaEmbed\Object\MediaObject
	 */
	public function parseIdOrFail(string $id, string $host, array $config = []): MediaObject {
		if (!$id || !$host) {
			throw new InvalidUrlException('', 'ID and host are required.');
		}

		if (empty($this->providers[$host])) {
			throw new ProviderNotFoundException($host);
		}
		$stub = $this->providers[$host];
		$config += $this->config;

		$stub['id-template'] = $stub['id'] ?? null;
		$stub['id'] = $id;
		$stub['reverse'] = true;

		$object = $this->object($stub, $config);
		if (!$object->isSourceResolved()) {
			throw new InvalidUrlException($id, 'Could not resolve embed source for the given ID.');
		}

		return $object;
	}

	/**
	 * Parse given URL.
	 *
	 * It will return an object if the url contains valid/supported video.
	 *
	 * @param string $url Href to check for embedded video
	 * @param array<string, mixed> $config
	 * @return \MediaEmbed\Object\MediaObject|null
	 */
	public function parseUrl(string $url, array $config = []): ?MediaObject {
		$result = $this->getUrlMatcher()->match($url);
		if ($result === null) {
			return null;
		}

		$stub = $result->providerStub;
		$this->lastMatch = $result->matches;

		if (!empty($stub['fetch-match'])) {
			if (!$this->parseLink($url, $stub['fetch-match'])) {
				return null;
			}
		}

		$stub['match'] = $this->lastMatch;

		return $this->object($stub, $config + $this->config);
	}

	/**
	 * Parse given URL or throw exception on failure.
	 *
	 * @param string $url Href to check for embedded video
	 * @param array<string, mixed> $config
	 * @throws \MediaEmbed\Exception\InvalidUrlException When URL is not supported.
	 * @throws \MediaEmbed\Exception\FetchException When fetch-match fails.
	 * @return \MediaEmbed\Object\MediaObject
	 */
	public function parseUrlOrFail(string $url, array $config = []): MediaObject {
		$result = $this->getUrlMatcher()->match($url);
		if ($result === null) {
			throw new InvalidUrlException($url);
		}

		$stub = $result->providerStub;
		$this->lastMatch = $result->matches;

		if (!empty($stub['fetch-match'])) {
			if (!$this->parseLink($url, $stub['fetch-match'])) {
				throw new FetchException($url, sprintf('Failed to fetch and match content from URL: %s', $url));
			}
		}

		$stub['match'] = $this->lastMatch;

		return $this->object($stub, $config + $this->config);
	}

	/**
	 * Match a URL against the registered providers without creating a media object.
	 *
	 * @param string $url Href to check for embedded video
	 * @return \MediaEmbed\Matcher\MatchResult|null
	 */
	public function matchUrl(string $url): ?MatchResult {
		return $this->getUrlMatcher()->match($url);
	}

	/**
	 * Check whether a URL is supported by any registered provider.
	 *
	 * @param string $url Href to check for embedded video
	 * @return bool
	 */
	public function supportsUrl(string $url): bool {
		return $this->matchUrl($url) !== null;
	}

	/**
	 * Get the provider configuration that matches a URL.
	 *
	 * @param string $url Href to check for embedded video
	 * @return \MediaEmbed\Provider\ProviderConfig|null
	 */
	public function getProviderForUrl(string $url): ?ProviderConfig {
		$result = $this->matchUrl($url);
		if ($result === null) {
			return null;
		}

		return ProviderConfig::fromArray($result->providerStub);
	}

	/**
	 * Get the URL matcher (lazy initialization).
	 *
	 * @return \MediaEmbed\Matcher\UrlMatcher
	 */
	public function getUrlMatcher(): UrlMatcher {
		if ($this->urlMatcher === null) {
			$this->urlMatcher = new UrlMatcher($this->providers, $this->cache, $this->cacheTtl);
		}

		return $this->urlMatcher;
	}

	/**
	 * Set the cache used by the URL matcher.
	 *
	 * @param \Psr\SimpleCache\CacheInterface|null $cache Cache implementation.
	 * @param int $ttl Cache TTL in seconds.
	 * @return $this
	 */
	public function setCache(?CacheInterface $cache, int $ttl = 3600) {
		$this->cache = $cache;
		$this->cacheTtl = $ttl;
		$this->urlMatcher = null;

		return $this;
	}

	/**
	 * Attempt to parse the embed id from a given URL
	 *
	 * @param string $url
	 * @param string $regex
	 * @return bool
	 */
	protected function parseLink(string $url, string $regex): bool {
		$content = $this->httpClient->get($url);
		if (!$content) {
			return false;
		}

		$source = preg_replace('/[^(\x20-\x7F)]*/', '', $content);
		if (!$source) {
			return false;
		}

		if (preg_match('~' . $regex . '~imu', $source, $match)) {
			$this->lastMatch = $match;

			return true;
		}

		return false;
	}

	/**
	 * Register multiple provider configurations.
	 *
	 * @param array<array<string, mixed>> $stubs Provider configuration arrays.
	 * @param bool $reset Whether to reset existing providers.
	 * @return void
	 */
	protected function registerProviders(array $stubs, bool $reset = false): void {
		if ($reset) {
			$this->providers = [];
		}
		foreach ($stubs as $stub) {
			$config = ProviderConfig::fromArray($stub);
			$slug = $config->slug ?? $this->slug($config->name);
			$array = $config->toArray();
			if (!isset($array['slug'])) {
				$array['slug'] = $slug;
			}
			$this->providers[$slug] = $array;
		}

		$this->urlMatcher = null; // Reset matcher when hosts change
	}

	/**
	 * Load providers from a configuration file.
	 *
	 * Supports PHP, JSON, or serialized array formats.
	 *
	 * @param string $path Path to configuration file.
	 * @return $this
	 */
	public function loadProvidersFromFile(string $path) {
		if (!file_exists($path)) {
			return $this;
		}

		$providers = [];
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		if ($extension === 'php') {
			$providers = include $path;
		} elseif ($extension === 'json') {
			$content = file_get_contents($path);
			if ($content) {
				$providers = json_decode($content, true);
			}
		}

		if (is_array($providers)) {
			foreach ($providers as $provider) {
				if (is_array($provider)) {
					$this->addProviderConfig(ProviderConfig::fromArray($provider));
				}
			}
		}

		return $this;
	}

	/**
	 * Load providers using a ProviderLoaderInterface.
	 *
	 * @param \MediaEmbed\Provider\ProviderLoaderInterface $loader The provider loader.
	 * @param bool $reset Whether to reset existing providers before loading.
	 * @return $this
	 */
	public function loadProvidersFromLoader(ProviderLoaderInterface $loader, bool $reset = false) {
		if (!$loader->canLoad()) {
			return $this;
		}

		$providers = $loader->load();
		$this->registerProviders($providers, $reset);

		return $this;
	}

	/**
	 * Fetch oEmbed data for a parsed media object using the provider's registered endpoint.
	 *
	 * Returns null when the provider has no `oembed` endpoint, the object has no source URL
	 * (e.g. created via parseId()), or the request/parse fails.
	 *
	 * @param \MediaEmbed\Object\MediaObject $object Parsed media object.
	 * @param int|null $maxWidth Maximum embed width.
	 * @param int|null $maxHeight Maximum embed height.
	 * @return \MediaEmbed\OEmbed\OEmbedResponse|null
	 */
	public function oEmbed(MediaObject $object, ?int $maxWidth = null, ?int $maxHeight = null): ?OEmbedResponse {
		$endpoint = $object->oEmbedEndpoint();
		if ($endpoint === null) {
			return null;
		}

		return $this->oEmbedDiscovery()->fetch($endpoint, $maxWidth, $maxHeight);
	}

	/**
	 * Fetch raw embed HTML from a provider's oEmbed response.
	 *
	 * The returned HTML is controlled by the remote provider. Consumers must only
	 * render it for trusted providers, or sanitize it according to their policy.
	 *
	 * @param \MediaEmbed\Object\MediaObject $object Parsed media object.
	 * @param int|null $maxWidth Maximum embed width.
	 * @param int|null $maxHeight Maximum embed height.
	 * @return string|null
	 */
	public function oEmbedHtml(MediaObject $object, ?int $maxWidth = null, ?int $maxHeight = null): ?string {
		$response = $this->oEmbed($object, $maxWidth, $maxHeight);
		if ($response === null || !$response->hasHtml()) {
			return null;
		}

		return $response->html;
	}

	/**
	 * Resolve a thumbnail URL for a parsed media object.
	 *
	 * Prefers the provider's static thumbnail (image-src) and falls back to the oEmbed
	 * `thumbnail_url` when the provider exposes an oEmbed endpoint.
	 *
	 * @param \MediaEmbed\Object\MediaObject $object Parsed media object.
	 * @return string|null
	 */
	public function thumbnail(MediaObject $object): ?string {
		// getImageSrc() resolves image-src for both URL- and ID-parsed objects.
		$static = $object->getImageSrc();
		if ($static !== null && $static !== '') {
			return $static;
		}

		return $this->oEmbed($object)?->thumbnailUrl;
	}

	/**
	 * @return \MediaEmbed\OEmbed\OEmbedDiscovery
	 */
	protected function oEmbedDiscovery(): OEmbedDiscovery {
		return new OEmbedDiscovery($this->httpClient, $this->cache, $this->cacheTtl);
	}

	/**
	 * @param array<string> $whitelist (alias/keys)
	 * @return array<string, array<string, mixed>> Host info
	 */
	public function getHosts(array $whitelist = []): array {
		if ($whitelist) {
			$res = [];
			foreach ($this->providers as $slug => $host) {
				if (!in_array($slug, $whitelist, true)) {
					continue;
				}
				$res[$slug] = $host;
			}

			return $res;
		}

		return $this->providers;
	}

	/**
	 * Get a provider configuration by alias.
	 *
	 * @param string $alias Provider slug/alias.
	 * @return \MediaEmbed\Provider\ProviderConfig|null Provider config or null if not found.
	 */
	public function getProvider(string $alias): ?ProviderConfig {
		if (empty($this->providers[$alias])) {
			return null;
		}

		return ProviderConfig::fromArray($this->providers[$alias]);
	}

	/**
	 * Get a provider configuration by alias or throw exception.
	 *
	 * @param string $alias Provider slug/alias.
	 * @throws \MediaEmbed\Exception\ProviderNotFoundException When provider is not found.
	 * @return \MediaEmbed\Provider\ProviderConfig Provider config.
	 */
	public function getProviderOrFail(string $alias): ProviderConfig {
		if (empty($this->providers[$alias])) {
			throw new ProviderNotFoundException($alias);
		}

		return ProviderConfig::fromArray($this->providers[$alias]);
	}

	/**
	 * Get all providers as a collection.
	 *
	 * @param array<string> $whitelist Optional list of slugs to include.
	 * @return \MediaEmbed\Provider\ProviderCollection
	 */
	public function getProviders(array $whitelist = []): ProviderCollection {
		$hosts = $this->getHosts($whitelist);

		return ProviderCollection::fromArray(array_values($hosts));
	}

	/**
	 * Add a provider using ProviderConfig DTO.
	 *
	 * @param \MediaEmbed\Provider\ProviderConfig $config Provider configuration.
	 * @param bool $override Whether to override existing provider with same slug.
	 * @return $this
	 */
	public function addProviderConfig(ProviderConfig $config, bool $override = false) {
		$slug = $config->slug ?? $this->slug($config->name);
		if (!$config->hasIframeSupport() && !$config->hasOEmbedSupport()) {
			throw ProviderConfigException::missingField('iframe-player or oembed', $config->toArray());
		}

		if (!$override && isset($this->providers[$slug])) {
			return $this;
		}

		$array = $config->toArray();
		if (!isset($array['slug'])) {
			$array['slug'] = $slug;
		}

		$this->providers[$slug] = $array;
		$this->urlMatcher = null; // Reset matcher when providers change

		return $this;
	}

	/**
	 * Create a MediaObject from provider stub data.
	 *
	 * @param array<string, mixed> $stub Provider configuration array.
	 * @param array<string, mixed> $config Additional configuration.
	 * @return \MediaEmbed\Object\MediaObject
	 */
	protected function object(array $stub, array $config = []): MediaObject {
		if (!isset($stub['slug']) && !empty($stub['name'])) {
			$stub['slug'] = $this->slug($stub['name']);
		}

		return new MediaObject($stub, $config);
	}

	/**
	 * Slugify a string.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function slug(string $text): string {
		return $this->slugger->slug($text);
	}

}
