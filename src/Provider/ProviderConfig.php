<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

use MediaEmbed\Exception\ProviderConfigException;

/**
 * Data Transfer Object for provider configuration.
 *
 * This class provides a type-safe way to work with provider configurations.
 * It can be constructed from arrays (backward compatible) and converted back to arrays.
 */
final class ProviderConfig {

	/**
	 * @param string $name Display name of the provider.
	 * @param string $website Provider's website URL.
	 * @param array<string>|string $urlMatch URL matching regex pattern(s).
	 * @param string|int $embedWidth Default embed width.
	 * @param string|int $embedHeight Default embed height.
	 * @param string|null $slug URL-safe identifier (auto-generated from name if not provided).
	 * @param string|null $iframePlayer iframe src template URL.
	 * @param string|null $imageSrc Thumbnail image URL template.
	 * @param string|null $id Custom ID extraction pattern.
	 * @param string|null $fetchMatch Secondary HTTP fetch regex.
	 * @param bool $supportsTimestamp Whether provider supports timestamps.
	 * @param string|null $timestampParam Embed query parameter for timestamp values.
	 * @param array<string, mixed> $iframeParams Default iframe query parameters.
	 * @param array<string, mixed> $extra Extra provider metadata.
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $website,
		public readonly array|string $urlMatch,
		public readonly string|int $embedWidth,
		public readonly string|int $embedHeight,
		public readonly ?string $slug = null,
		public readonly ?string $iframePlayer = null,
		public readonly ?string $imageSrc = null,
		public readonly ?string $id = null,
		public readonly ?string $fetchMatch = null,
		public readonly bool $supportsTimestamp = false,
		public readonly ?string $timestampParam = null,
		public readonly array $iframeParams = [],
		public readonly array $extra = [],
	) {
	}

	/**
	 * Create a ProviderConfig from an array.
	 *
	 * @param array<string, mixed> $data Provider configuration array.
	 * @throws \MediaEmbed\Exception\ProviderConfigException When required fields are missing.
	 * @return self
	 */
	public static function fromArray(array $data): self {
		// Validate required fields
		if (empty($data['name'])) {
			throw ProviderConfigException::missingField('name', $data);
		}
		if (empty($data['website'])) {
			throw ProviderConfigException::missingField('website', $data);
		}
		if (empty($data['url-match'])) {
			throw ProviderConfigException::missingField('url-match', $data);
		}
		if (!isset($data['embed-width'])) {
			throw ProviderConfigException::missingField('embed-width', $data);
		}
		if (!isset($data['embed-height'])) {
			throw ProviderConfigException::missingField('embed-height', $data);
		}

		$knownKeys = [
			'name',
			'website',
			'url-match',
			'embed-width',
			'embed-height',
			'slug',
			'iframe-player',
			'image-src',
			'id',
			'fetch-match',
			'supports-timestamp',
			'timestamp-param',
			'iframe-params',
		];

		return new self(
			name: $data['name'],
			website: $data['website'],
			urlMatch: $data['url-match'],
			embedWidth: (string)$data['embed-width'],
			embedHeight: (string)$data['embed-height'],
			slug: $data['slug'] ?? null,
			iframePlayer: $data['iframe-player'] ?? null,
			imageSrc: $data['image-src'] ?? null,
			id: $data['id'] ?? null,
			fetchMatch: $data['fetch-match'] ?? null,
			supportsTimestamp: !empty($data['supports-timestamp']),
			timestampParam: $data['timestamp-param'] ?? null,
			iframeParams: !empty($data['iframe-params']) && is_array($data['iframe-params']) ? $data['iframe-params'] : [],
			extra: array_diff_key($data, array_flip($knownKeys)),
		);
	}

	/**
	 * Convert to array format.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$array = [
			'name' => $this->name,
			'website' => $this->website,
			'url-match' => $this->urlMatch,
			'embed-width' => $this->embedWidth,
			'embed-height' => $this->embedHeight,
		];
		$array += $this->extra;

		if ($this->slug !== null) {
			$array['slug'] = $this->slug;
		}
		if ($this->iframePlayer !== null) {
			$array['iframe-player'] = $this->iframePlayer;
		}
		if ($this->imageSrc !== null) {
			$array['image-src'] = $this->imageSrc;
		}
		if ($this->id !== null) {
			$array['id'] = $this->id;
		}
		if ($this->fetchMatch !== null) {
			$array['fetch-match'] = $this->fetchMatch;
		}
		if ($this->supportsTimestamp) {
			$array['supports-timestamp'] = true;
		}
		if ($this->timestampParam !== null) {
			$array['timestamp-param'] = $this->timestampParam;
		}
		if ($this->iframeParams) {
			$array['iframe-params'] = $this->iframeParams;
		}

		return $array;
	}

	/**
	 * Get URL match patterns as array.
	 *
	 * @return array<string>
	 */
	public function getUrlMatchPatterns(): array {
		if (is_array($this->urlMatch)) {
			return $this->urlMatch;
		}

		return [$this->urlMatch];
	}

	/**
	 * Check if this provider has iframe support.
	 *
	 * @return bool
	 */
	public function hasIframeSupport(): bool {
		return $this->iframePlayer !== null;
	}

	/**
	 * Check if this provider has thumbnail support.
	 *
	 * @return bool
	 */
	public function hasThumbnailSupport(): bool {
		return $this->imageSrc !== null;
	}

	/**
	 * Check if this provider requires secondary fetch.
	 *
	 * @return bool
	 */
	public function requiresFetch(): bool {
		return $this->fetchMatch !== null;
	}

}
