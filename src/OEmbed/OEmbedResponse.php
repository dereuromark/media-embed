<?php

declare(strict_types=1);

namespace MediaEmbed\OEmbed;

/**
 * Represents an oEmbed response.
 *
 * @see https://oembed.com/
 */
final class OEmbedResponse {

	/**
	 * @param string $type The resource type (video, photo, link, rich).
	 * @param string $version The oEmbed version number.
	 * @param string|null $title A text title.
	 * @param string|null $authorName The name of the author/owner.
	 * @param string|null $authorUrl A URL for the author/owner.
	 * @param string|null $providerName The name of the resource provider.
	 * @param string|null $providerUrl The URL of the resource provider.
	 * @param int|null $cacheAge The suggested cache lifetime in seconds.
	 * @param string|null $thumbnailUrl A URL to a thumbnail image.
	 * @param int|null $thumbnailWidth Width of the thumbnail.
	 * @param int|null $thumbnailHeight Height of the thumbnail.
	 * @param string|null $html The HTML required to embed (for video/rich).
	 * @param int|null $width Width of the HTML element.
	 * @param int|null $height Height of the HTML element.
	 * @param string|null $url Source URL of the image (for photo type).
	 */
	public function __construct(
		public readonly string $type,
		public readonly string $version,
		public readonly ?string $title = null,
		public readonly ?string $authorName = null,
		public readonly ?string $authorUrl = null,
		public readonly ?string $providerName = null,
		public readonly ?string $providerUrl = null,
		public readonly ?int $cacheAge = null,
		public readonly ?string $thumbnailUrl = null,
		public readonly ?int $thumbnailWidth = null,
		public readonly ?int $thumbnailHeight = null,
		public readonly ?string $html = null,
		public readonly ?int $width = null,
		public readonly ?int $height = null,
		public readonly ?string $url = null,
	) {
	}

	/**
	 * Create from JSON response data.
	 *
	 * @param array<string, mixed> $data
	 * @return self
	 */
	public static function fromArray(array $data): self {
		return new self(
			type: (string)($data['type'] ?? 'link'),
			version: (string)($data['version'] ?? '1.0'),
			title: isset($data['title']) ? (string)$data['title'] : null,
			authorName: isset($data['author_name']) ? (string)$data['author_name'] : null,
			authorUrl: isset($data['author_url']) ? (string)$data['author_url'] : null,
			providerName: isset($data['provider_name']) ? (string)$data['provider_name'] : null,
			providerUrl: isset($data['provider_url']) ? (string)$data['provider_url'] : null,
			cacheAge: isset($data['cache_age']) ? (int)$data['cache_age'] : null,
			thumbnailUrl: isset($data['thumbnail_url']) ? (string)$data['thumbnail_url'] : null,
			thumbnailWidth: isset($data['thumbnail_width']) ? (int)$data['thumbnail_width'] : null,
			thumbnailHeight: isset($data['thumbnail_height']) ? (int)$data['thumbnail_height'] : null,
			html: isset($data['html']) ? (string)$data['html'] : null,
			width: isset($data['width']) ? (int)$data['width'] : null,
			height: isset($data['height']) ? (int)$data['height'] : null,
			url: isset($data['url']) ? (string)$data['url'] : null,
		);
	}

	/**
	 * Check if this is a video type.
	 *
	 * @return bool
	 */
	public function isVideo(): bool {
		return $this->type === 'video';
	}

	/**
	 * Check if this is a photo type.
	 *
	 * @return bool
	 */
	public function isPhoto(): bool {
		return $this->type === 'photo';
	}

	/**
	 * Check if this is a rich type.
	 *
	 * @return bool
	 */
	public function isRich(): bool {
		return $this->type === 'rich';
	}

	/**
	 * Check if this response has embeddable HTML.
	 *
	 * @return bool
	 */
	public function hasHtml(): bool {
		return $this->html !== null && $this->html !== '';
	}

	/**
	 * Check if this response has a thumbnail.
	 *
	 * @return bool
	 */
	public function hasThumbnail(): bool {
		return $this->thumbnailUrl !== null && $this->thumbnailUrl !== '';
	}

}
