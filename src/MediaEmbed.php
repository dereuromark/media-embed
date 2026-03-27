<?php

namespace MediaEmbed;

use MediaEmbed\Exception\FetchException;
use MediaEmbed\Exception\InvalidUrlException;
use MediaEmbed\Exception\ProviderNotFoundException;
use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\Http\StreamHttpClient;
use MediaEmbed\Matcher\UrlMatcher;
use MediaEmbed\Object\MediaObject;
use MediaEmbed\Provider\PhpFileLoader;
use MediaEmbed\Provider\ProviderCollection;
use MediaEmbed\Provider\ProviderConfig;
use MediaEmbed\Provider\ProviderLoaderInterface;
use URLify;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 * A utility that generates HTML embed tags for audio or video located on a given URL.
 * It also parses and validates given media URLs.
 *
 * @author MarkScherer
 * @license MIT
 */
class MediaEmbed {

	/**
	 * @var array<string>
	 */
	protected ?array $_match = null;

	/**
	 * @var array<string, array<string, mixed>>
	 */
	protected array $_hosts = [];

	/**
	 * See MediaObject for details
	 *
	 * @var array<string, mixed>
	 */
	public array $config = [];

	/**
	 * HTTP client for fetching remote content.
	 */
	protected HttpClientInterface $httpClient;

	/**
	 * URL matcher with domain-based caching.
	 */
	protected ?UrlMatcher $urlMatcher = null;

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
	 */
	public function __construct(
		array $config = [],
		?string $stubsPath = null,
		?HttpClientInterface $httpClient = null,
		?ProviderLoaderInterface $providerLoader = null,
	) {
		$this->httpClient = $httpClient ?? new StreamHttpClient();

		// Use provided loader or default to PhpFileLoader
		if ($providerLoader !== null) {
			$stubs = $providerLoader->load();
		} else {
			if ($stubsPath === null) {
				$stubsPath = dirname(__DIR__) . DS . 'data' . DS . 'stubs.php';
			}
			$loader = new PhpFileLoader($stubsPath);
			$stubs = $loader->load();
		}
		$this->setHosts($stubs);
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

		if (empty($this->_hosts[$host])) {
			return null;
		}
		$stub = $this->_hosts[$host];
		$config += $this->config;

		$stub['id'] = $id;
		$stub['reverse'] = true;

		return $this->object($stub, $config);
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

		if (empty($this->_hosts[$host])) {
			throw new ProviderNotFoundException($host);
		}
		$stub = $this->_hosts[$host];
		$config += $this->config;

		$stub['id'] = $id;
		$stub['reverse'] = true;

		$object = $this->object($stub, $config);
		if ($object === null) {
			throw new ProviderNotFoundException($host);
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
		$this->_match = $result->matches;

		if (!empty($stub['fetch-match'])) {
			if (!$this->_parseLink($url, $stub['fetch-match'])) {
				return null;
			}
		}

		$stub['match'] = $this->_match;

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
		$this->_match = $result->matches;

		if (!empty($stub['fetch-match'])) {
			if (!$this->_parseLink($url, $stub['fetch-match'])) {
				throw new FetchException($url, sprintf('Failed to fetch and match content from URL: %s', $url));
			}
		}

		$stub['match'] = $this->_match;

		$object = $this->object($stub, $config + $this->config);
		if ($object === null) {
			throw new InvalidUrlException($url);
		}

		return $object;
	}

	/**
	 * Get the URL matcher (lazy initialization).
	 *
	 * @return \MediaEmbed\Matcher\UrlMatcher
	 */
	public function getUrlMatcher(): UrlMatcher {
		if ($this->urlMatcher === null) {
			$this->urlMatcher = new UrlMatcher($this->_hosts);
		}

		return $this->urlMatcher;
	}

	/**
	 * Attempt to parse the embed id from a given URL
	 *
	 * @param string $url
	 * @param string $regex
	 * @return bool
	 */
	protected function _parseLink(string $url, string $regex): bool {
		$content = $this->httpClient->get($url);
		if (!$content) {
			return false;
		}

		$source = preg_replace('/[^(\x20-\x7F)]*/', '', $content);
		if (!$source) {
			return false;
		}

		if (preg_match('~' . $regex . '~imu', $source, $match)) {
			$this->_match = $match;

			return true;
		}

		return false;
	}

	/**
	 * Set custom stubs overwriting the default ones.
	 *
	 * @param array<string, array<string, mixed>> $stubs Same format as in the stubs.php file.
	 * @param bool $reset If default ones should be resetted/removed.
	 * @return $this
	 */
	public function setHosts(array $stubs, bool $reset = false) {
		if ($reset) {
			$this->_hosts = [];
		}
		foreach ($stubs as $stub) {
			$slug = $this->_slug($stub['name']);
			$this->_hosts[$slug] = $stub;
		}

		$this->urlMatcher = null; // Reset matcher when hosts change

		return $this;
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
		$this->setHosts($providers, $reset);

		return $this;
	}

	/**
	 * @param array<string> $whitelist (alias/keys)
	 * @return array<string, array<string, mixed>> Host info
	 */
	public function getHosts(array $whitelist = []): array {
		if ($whitelist) {
			$res = [];
			foreach ($this->_hosts as $slug => $host) {
				if (!in_array($slug, $whitelist, true)) {
					continue;
				}
				$res[$slug] = $host;
			}

			return $res;
		}

		return $this->_hosts;
	}

	/**
	 * Get a provider configuration by alias.
	 *
	 * @param string $alias Provider slug/alias.
	 * @return \MediaEmbed\Provider\ProviderConfig|null Provider config or null if not found.
	 */
	public function getProvider(string $alias): ?ProviderConfig {
		if (empty($this->_hosts[$alias])) {
			return null;
		}

		return ProviderConfig::fromArray($this->_hosts[$alias]);
	}

	/**
	 * Get a provider configuration by alias or throw exception.
	 *
	 * @param string $alias Provider slug/alias.
	 * @throws \MediaEmbed\Exception\ProviderNotFoundException When provider is not found.
	 * @return \MediaEmbed\Provider\ProviderConfig Provider config.
	 */
	public function getProviderOrFail(string $alias): ProviderConfig {
		if (empty($this->_hosts[$alias])) {
			throw new ProviderNotFoundException($alias);
		}

		return ProviderConfig::fromArray($this->_hosts[$alias]);
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
		$slug = $config->slug ?? $this->_slug($config->name);

		if (!$override && isset($this->_hosts[$slug])) {
			return $this;
		}

		$array = $config->toArray();
		if (!isset($array['slug'])) {
			$array['slug'] = $slug;
		}

		$this->_hosts[$slug] = $array;
		$this->urlMatcher = null; // Reset matcher when providers change

		return $this;
	}

	/**
	 * @param array<string, mixed>|string $stub
	 * @param array<string, mixed> $config
	 *
	 * @return \MediaEmbed\Object\MediaObject|null
	 */
	public function object($stub, array $config = []): ?MediaObject {
		if (!is_array($stub)) {
			if (empty($this->_hosts[$stub])) {
				return null;
			}
			$stub = $this->_hosts[$stub];
		}
		if (!isset($stub['slug']) && !empty($stub['name'])) {
			$stub['slug'] = $this->_slug($stub['name']);
		}

		return new MediaObject($stub, $config);
	}

	/**
	 * Slugify a string.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function _slug(string $text): string {
		return URLify::filter($text);
	}

}
