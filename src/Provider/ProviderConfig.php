<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

use MediaEmbed\Exception\ProviderConfigException;
use MediaEmbed\Provider\Enum\Category;
use MediaEmbed\Provider\Enum\Status;

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
	 * @param \MediaEmbed\Provider\Enum\Status $status Provider lifecycle status.
	 * @param \MediaEmbed\Provider\Enum\Category $category Provider content category.
	 * @param string|null $exampleUrl Example URL covered by provider tests.
	 * @param string|null $notes Provider notes for generated docs.
	 * @param string|null $oEmbed oEmbed endpoint URL.
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
		public readonly Status $status = Status::Active,
		public readonly Category $category = Category::Video,
		public readonly ?string $exampleUrl = null,
		public readonly ?string $notes = null,
		public readonly ?string $oEmbed = null,
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
		$iframePlayer = $data['iframe-player'] ?? null;
		$oEmbed = $data['oembed'] ?? null;
		if (($iframePlayer === null || $iframePlayer === '') && ($oEmbed === null || $oEmbed === '')) {
			throw ProviderConfigException::missingField('iframe-player or oembed', $data);
		}
		if ($iframePlayer !== null && !is_string($iframePlayer)) {
			throw ProviderConfigException::invalidField('iframe-player', $data['iframe-player'], 'string', $data);
		}
		if ($oEmbed !== null && !is_string($oEmbed)) {
			throw ProviderConfigException::invalidField('oembed', $data['oembed'], 'string', $data);
		}

		$embedWidth = self::dimensionFromArray($data, 'embed-width');
		$embedHeight = self::dimensionFromArray($data, 'embed-height');

		$knownKeys = [
			'name',
			'website',
			'url-match',
			'embed-width',
			'embed-height',
			'status',
			'category',
			'example-url',
			'notes',
			'slug',
			'iframe-player',
			'oembed',
			'image-src',
			'id',
			'fetch-match',
			'supports-timestamp',
			'timestamp-param',
			'iframe-params',
			// Legacy/removed keys (ignored in 1.0)
			'embed-src',
			'flashvars',
		];

		return new self(
			name: $data['name'],
			website: $data['website'],
			urlMatch: $data['url-match'],
			embedWidth: $embedWidth,
			embedHeight: $embedHeight,
			status: isset($data['status']) && is_string($data['status']) ? (Status::tryFrom($data['status']) ?? Status::Active) : Status::Active,
			category: isset($data['category']) && is_string($data['category']) ? (Category::tryFrom($data['category']) ?? Category::Video) : Category::Video,
			exampleUrl: isset($data['example-url']) && is_string($data['example-url']) ? $data['example-url'] : null,
			notes: isset($data['notes']) && is_string($data['notes']) ? $data['notes'] : null,
			slug: $data['slug'] ?? null,
			iframePlayer: $iframePlayer,
			oEmbed: $oEmbed,
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
	 * @param array<string, mixed> $data Provider configuration array.
	 * @param string $field Dimension field name.
	 * @throws \MediaEmbed\Exception\ProviderConfigException When the dimension is invalid.
	 * @return string|int
	 */
	protected static function dimensionFromArray(array $data, string $field): string|int {
		$value = $data[$field];
		if (is_int($value) || is_string($value)) {
			return $value;
		}

		throw ProviderConfigException::invalidField($field, $value, 'integer or string', $data);
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
			'status' => $this->status->value,
			'category' => $this->category->value,
		];
		$array += $this->extra;

		if ($this->exampleUrl !== null) {
			$array['example-url'] = $this->exampleUrl;
		}
		if ($this->notes !== null) {
			$array['notes'] = $this->notes;
		}
		if ($this->slug !== null) {
			$array['slug'] = $this->slug;
		}
		if ($this->iframePlayer !== null) {
			$array['iframe-player'] = $this->iframePlayer;
		}
		if ($this->oEmbed !== null) {
			$array['oembed'] = $this->oEmbed;
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
	 * Check if this provider has oEmbed support.
	 *
	 * @return bool
	 */
	public function hasOEmbedSupport(): bool {
		return $this->oEmbed !== null;
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
