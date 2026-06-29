<?php

declare(strict_types=1);

namespace MediaEmbed\Object;

/**
 * ObjectInterface must be implemented by classes that are a specific type of media.
 *
 * @method string getPlaceholderEmbedCode(array<string, mixed> $options = []) Returns a GDPR-friendly
 *   "click-to-load" consent placeholder. The real iframe is held in a `<template>` and only loaded on
 *   user click via the shared snippet from `MediaObject::placeholderScript()`. Implemented on
 *   {@see \MediaEmbed\Object\MediaObject}; declared here as `@method` (not abstract) to avoid a BC
 *   break for external implementers on the current minor line. Promote to an abstract method in the
 *   next major. All `MediaEmbed::parse*()` calls return `MediaObject`, so callers can use it directly.
 */
interface ObjectInterface {

	/**
	 * Returns the unique id of a media resource.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Returns the host as slugged string.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function slug(): string;

	/**
	 * Returns the name of this media host type.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function name(): string;

	/**
	 * Returns the website URL of this media host type.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function website(): string;

	/**
	 * Returns the final HTML code for display.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedCode(): string;

	/**
	 * Returns the embed wrapped in a fluid aspect-ratio container.
	 *
	 * @api
	 *
	 * @param string $ratio Aspect ratio as "width:height" (e.g. "16:9").
	 * @return string
	 */
	public function getResponsiveEmbedCode(string $ratio = '16:9'): string;

	/**
	 * Returns the raw embed src URL. Useful when the caller controls escaping.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedSrc(): string;

	/**
	 * Returns the HTML-escaped embed src URL for iframe src attributes.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedSrcForHtml(): string;

	/**
	 * Returns the thumbnail/preview image URL, or empty string if none.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function image(): string;

	/**
	 * Returns the resolved thumbnail/preview image URL, or null if none.
	 *
	 * @api
	 *
	 * @return string|null
	 */
	public function getImageSrc(): ?string;

	/**
	 * Returns a new instance with the given iframe URL parameter(s) overridden.
	 *
	 * @api
	 *
	 * @param array<string, mixed>|string $param Param name, or a map of params.
	 * @param string|float|int|bool|null $value Value when $param is a string.
	 * @return static
	 */
	public function withParam(array|string $param, string|float|int|bool|null $value = null): static;

	/**
	 * Returns a new instance with the given iframe attribute(s) overridden.
	 *
	 * @api
	 *
	 * @param array<string, mixed>|string $param Attribute name, or a map of attributes.
	 * @param string|int|bool|null $value Value when $param is a string.
	 * @return static
	 */
	public function withAttribute(array|string $param, string|int|bool|null $value = null): static;

	/**
	 * Returns a new instance with the given width (optionally adjusting height to keep ratio).
	 *
	 * @api
	 *
	 * @param int $width
	 * @param bool $adjustHeight
	 * @return static
	 */
	public function withWidth(int $width, bool $adjustHeight = false): static;

	/**
	 * Returns a new instance with the given height (optionally adjusting width to keep ratio).
	 *
	 * @api
	 *
	 * @param int $height
	 * @param bool $adjustWidth
	 * @return static
	 */
	public function withHeight(int $height, bool $adjustWidth = false): static;

	/**
	 * Returns the iframe URL parameters, or a single value when $key is given.
	 *
	 * @api
	 *
	 * @param string|null $key
	 * @return array<string, mixed>|string|null
	 */
	public function getParams(?string $key = null): array|string|null;

	/**
	 * Returns the iframe attributes, or a single value when $key is given.
	 *
	 * @api
	 *
	 * @param string|null $key
	 * @return array<string, mixed>|string|int|bool|null
	 */
	public function getAttributes(?string $key = null): mixed;

	/**
	 * Returns the matched source (page) URL, when created via URL parsing.
	 *
	 * @api
	 *
	 * @return string|null
	 */
	public function sourceUrl(): ?string;

	/**
	 * Returns the provider's oEmbed endpoint URL for this object, if registered.
	 *
	 * @api
	 *
	 * @return string|null
	 */
	public function oEmbedEndpoint(): ?string;

	/**
	 * Whether the iframe source is fully resolved (no leftover placeholders).
	 *
	 * @api
	 *
	 * @return bool
	 */
	public function isSourceResolved(): bool;

	/**
	 * Convenience wrapper for `echo $object`.
	 *
	 * @return string
	 */
	public function __toString(): string;

}
