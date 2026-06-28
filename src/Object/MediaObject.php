<?php

declare(strict_types=1);

namespace MediaEmbed\Object;

use InvalidArgumentException;
use MediaEmbed\Template\TemplateResolver;

/**
 * A generic media object for iframe embeds.
 */
class MediaObject implements ObjectInterface {

	/**
	 * Google favicon service URL.
	 *
	 * @var string
	 */
	protected const FAVICON_SERVICE_URL = 'https://www.google.com/s2/favicons?domain=';

	/**
	 * Provider stub data.
	 *
	 * @var array<string, mixed>
	 */
	protected array $stub;

	/**
	 * URL match results.
	 *
	 * @var array<string>
	 */
	protected array $match;

	/**
	 * Template resolver for URL interpolation.
	 */
	protected TemplateResolver $templateResolver;

	/**
	 * Iframe attributes (width, height, etc).
	 *
	 * @var array<string, mixed>
	 */
	protected array $iframeAttributes = [];

	/**
	 * Iframe URL parameters.
	 *
	 * @var array<string, mixed>
	 */
	protected array $iframeParams = [];

	/**
	 * Configuration options.
	 *
	 * @var array<string, mixed>
	 */
	protected array $config = [];

	/**
	 * MediaObject::__construct()
	 *
	 * @param array<string, mixed> $stub
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $stub, array $config) {
		$this->templateResolver = new TemplateResolver();
		$this->config = $config + $this->config;

		$stubDefaults = [
			'id' => '',
			'name' => '',
			'website' => '',
			'slug' => '',
			'match' => [],
		];
		$this->stub = $stub + $stubDefaults;
		$this->match = $this->stub['match'];
		$this->stub['id'] = $this->id();

		$this->setDefaultParams($stub);

		if (!empty($this->config['privacy'])) {
			$this->applyPrivacyMode();
		}

		if (!empty($this->stub['reverse']) && isset($this->stub['iframe-player'])) {
			$src = $this->getObjectSrc('iframe-player');
			$this->stub['iframe-player'] = $src;
		}

		if (isset($this->stub['iframe-player'])) {
			// Handle timestamps for providers that support them.
			$this->handleTimestampSupport();
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function id(): string {
		$res = $this->match;
		$count = count($res);

		if (empty($this->stub['id'])) {
			if (empty($res[$count - 1])) {
				return '';
			}
			$this->stub['id'] = $res[$count - 1];
		}

		$id = $this->templateResolver->resolve($this->stub['id'], $res);

		// If the ID is still a placeholder (no matches were provided), return empty string
		if ($this->templateResolver->hasUnresolvedPlaceholders($id)) {
			return '';
		}

		return $id;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function slug(): string {
		return $this->stub['slug'];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function name(): string {
		if (empty($this->stub['name'])) {
			return '';
		}

		return $this->templateResolver->resolve($this->stub['name'], $this->match);
	}

	/**
	 * Return the website URL of this type
	 *
	 * @return string
	 */
	public function website(): string {
		return !empty($this->stub['website']) ? $this->stub['website'] : '';
	}

	/**
	 * Returns a png img
	 *
	 * @return string|null Resource content or null if not available
	 */
	public function icon(): ?string {
		$url = $this->stub['website'];
		if (!$url) {
			return null;
		}

		$pieces = parse_url($url);
		if (!$pieces || empty($pieces['host'])) {
			return null;
		}

		$url = $pieces['host'];
		$icon = static::FAVICON_SERVICE_URL . $url;

		$context = stream_context_create(
			['http' => ['header' => 'Connection: close']],
		);
		$file = file_get_contents($icon, false, $context);
		if ($file === false) {
			return null;
		}

		return $file;
	}

	/**
	 * @param string $location Absolute path with trailing slash
	 * @param string|null $icon Icon data
	 * @return string|null Filename
	 */
	public function saveIcon(string $location, ?string $icon = null): ?string {
		if ($icon === null) {
			$icon = $this->icon();
		}
		if (!$icon) {
			return null;
		}
		if (!$location) {
			return null;
		}
		$filename = $this->slug() . '.png';
		$file = $location . $filename;
		if (!file_put_contents($file, $icon)) {
			return null;
		}

		return $filename;
	}

	/**
	 * Override a default iframe param value.
	 *
	 * @param array<string, mixed>|string $param The name of the param to be set
	 *   or an array of multiple params to set.
	 * @param string|float|int|bool|null $value The value to set the param to (when $param is string).
	 * @return $this
	 */
	public function setParam(array|string $param, string|float|int|bool|null $value = null) {
		if (is_array($param)) {
			foreach ($param as $p => $v) {
				$this->iframeParams[$p] = $v;
			}
		} else {
			$this->iframeParams[$param] = $value;
		}

		return $this;
	}

	/**
	 * Override a default iframe attribute value.
	 *
	 * @param array<string, mixed>|string $param The name of the attribute to be set
	 *   or an array of multiple attributes to set.
	 * @param string|int|bool|null $value The value to set (when $param is string).
	 * @return $this
	 */
	public function setAttribute(array|string $param, string|int|bool|null $value = null) {
		if (is_array($param)) {
			foreach ($param as $p => $v) {
				$this->assertValidAttributeName((string)$p);
				$this->iframeAttributes[$p] = $v;
			}
		} else {
			$this->assertValidAttributeName($param);
			$this->iframeAttributes[$param] = $value;
		}

		return $this;
	}

	/**
	 * @param string $name Attribute name.
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	protected function assertValidAttributeName(string $name): void {
		if (!preg_match('/^[^\s"\'=<>\/\x00-\x1F\x7F]+$/', $name) || preg_match('/^on/i', $name)) {
			throw new InvalidArgumentException(sprintf('Invalid iframe attribute name "%s"', $name));
		}
	}

	/**
	 * Set the height of the object
	 *
	 * @param int $height Height to set the object to
	 * @param bool $adjustWidth
	 * @return $this
	 */
	public function setHeight(int $height, bool $adjustWidth = false) {
		if ($adjustWidth) {
			$this->adjustDimensions('width', 'height', $height);
		}

		return $this->setAttribute('height', $height);
	}

	/**
	 * Set the width of the object
	 *
	 * @param int $width Width to set the object to
	 * @param bool $adjustHeight
	 * @return $this
	 */
	public function setWidth(int $width, bool $adjustHeight = false) {
		if ($adjustHeight) {
			$this->adjustDimensions('height', 'width', $width);
		}

		return $this->setAttribute('width', $width);
	}

	/**
	 * Auto-adjusts one dimension from the other to keep the current ratio.
	 *
	 * @param string $type
	 * @param string $fromType
	 * @param int $fromLength
	 * @return void
	 */
	protected function adjustDimensions(string $type, string $fromType, int $fromLength): void {
		$currentLength = (int)$this->getAttributes($type);
		$currentFromLength = (int)$this->getAttributes($fromType);
		if ($currentLength === 0 || $currentFromLength === 0) {
			return;
		}

		$ratio = $fromLength / $currentFromLength;
		$newLength = $currentLength * $ratio;

		$this->setAttribute($type, (int)$newLength);
	}

	/**
	 * Return iframe params.
	 *
	 * @param string|null $key
	 * @return array<string, mixed>|string|null Iframe params
	 */
	public function getParams(?string $key = null): array|string|null {
		if ($key === null) {
			return $this->iframeParams;
		}

		return $this->iframeParams[$key] ?? null;
	}

	/**
	 * Return iframe attributes.
	 *
	 * @param string|null $key
	 * @return array<string, mixed>|string|int|bool|null Iframe attribute
	 */
	public function getAttributes(?string $key = null): mixed {
		if ($key === null) {
			return $this->iframeAttributes;
		}

		return $this->iframeAttributes[$key] ?? null;
	}

	/**
	 * Convert the url to an embeddable iframe tag
	 *
	 * @return string The embed HTML
	 */
	public function getEmbedCode(): string {
		return $this->buildIframe();
	}

	/**
	 * Convert the url to a responsive iframe embed wrapped in an aspect-ratio container.
	 *
	 * The iframe is absolutely positioned to fill a wrapper whose height is driven by
	 * the given aspect ratio, so the embed scales fluidly with its parent width.
	 *
	 * @param string $ratio Aspect ratio as "width:height" (e.g. "16:9", "4:3", "1:1").
	 * @throws \InvalidArgumentException When the ratio is malformed or has a zero component.
	 * @return string The responsive embed HTML
	 */
	public function getResponsiveEmbedCode(string $ratio = '16:9'): string {
		[$ratioWidth, $ratioHeight] = $this->parseRatio($ratio);
		$paddingBottom = $ratioHeight / $ratioWidth * 100;

		// Build the iframe with a responsive style without mutating persistent object state,
		// preserving any style a caller may already have set.
		$originalAttributes = $this->iframeAttributes;
		$existingStyle = isset($this->iframeAttributes['style']) ? rtrim((string)$this->iframeAttributes['style'], ';') . ';' : '';
		$this->iframeAttributes['style'] = $existingStyle . 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;';

		try {
			$iframe = $this->buildIframe();
		} finally {
			$this->iframeAttributes = $originalAttributes;
		}

		return sprintf(
			'<div style="position:relative;width:100%%;height:0;padding-bottom:%s%%;overflow:hidden;">%s</div>',
			rtrim(rtrim(sprintf('%.4f', $paddingBottom), '0'), '.'),
			$iframe,
		);
	}

	/**
	 * Get the raw iframe src URL with parameters.
	 *
	 * @return string The unescaped src URL
	 */
	public function getEmbedSrc(): string {
		$source = $this->templateResolver->resolve($this->stub['iframe-player'], $this->match);

		return $this->appendQueryParams($source);
	}

	/**
	 * Get the iframe src URL escaped for HTML attributes.
	 *
	 * @return string The escaped src attribute value
	 */
	public function getEmbedSrcForHtml(): string {
		return $this->esc($this->getEmbedSrc());
	}

	/**
	 * Get final iframe src
	 *
	 * @param string $type The stub key to use for the source URL.
	 * @return string|null
	 */
	protected function getObjectSrc(string $type = 'iframe-player'): ?string {
		if (empty($this->stub['id']) || empty($this->stub['slug'])) {
			return null;
		}

		$stubSrc = $this->stub[$type];
		$src = $this->templateResolver->resolveReverse($stubSrc, $this->stub['id']);

		if (!empty($this->stub['replace'])) {
			$src = $this->templateResolver->resolveReplacements($src, (array)$this->stub['replace']);
		}

		return $src;
	}

	/**
	 * @return string|null
	 */
	public function getImageSrc(): ?string {
		if (empty($this->stub['id'])) {
			return null;
		}
		if (empty($this->stub['image-src'])) {
			return null;
		}

		return $this->templateResolver->resolveReverse($this->stub['image-src'], $this->stub['id']);
	}

	/**
	 * Return a thumbnail for the embeded video
	 *
	 * @return string - the thumbnail href
	 */
	public function image(): string {
		if (empty($this->stub['image-src'])) {
			return '';
		}

		return $this->templateResolver->resolve($this->stub['image-src'], $this->match);
	}

	/**
	 * Convenience wrapper for `echo $MediaObject`
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->getEmbedCode();
	}

	/**
	 * Build an iFrame player.
	 *
	 * @return string
	 */
	protected function buildIframe(): string {
		$attributes = $this->buildAttributeString();

		return sprintf('<iframe src="%s"%s></iframe>', $this->getEmbedSrcForHtml(), $attributes);
	}

	/**
	 * Append query parameters to a URL.
	 *
	 * @param string $url The base URL.
	 * @return string URL with appended parameters.
	 */
	protected function appendQueryParams(string $url): string {
		if (!$this->iframeParams) {
			return $url;
		}

		$separator = str_contains($url, '?') ? '&' : '?';

		return $url . $separator . http_build_query($this->iframeParams, '', '&');
	}

	/**
	 * Build HTML attribute string from iframe attributes.
	 *
	 * @return string
	 */
	protected function buildAttributeString(): string {
		$attributes = '';

		foreach ($this->iframeAttributes as $key => $val) {
			if ($val === false || $val === null) {
				continue;
			}
			$attributes .= ' ' . $key . ($val !== true ? '="' . $this->esc((string)$val) . '"' : '');
		}

		return $attributes;
	}

	/**
	 * Set the default iframe params and attributes.
	 *
	 * @param array<string, mixed> $stub
	 * @return void
	 */
	protected function setDefaultParams(array $stub): void {
		$this->iframeParams = [
			'wmode' => 'transparent',
		];
		if (!empty($stub['iframe-params']) && is_array($stub['iframe-params'])) {
			$this->iframeParams += $stub['iframe-params'];
		}
		if (!empty($stub['slug']) && !empty($this->config['provider_params'][$stub['slug']]) && is_array($this->config['provider_params'][$stub['slug']])) {
			$this->iframeParams = $this->config['provider_params'][$stub['slug']] + $this->iframeParams;
		}
		$this->iframeAttributes = [
			'type' => 'text/html',
			'title' => $this->titleAttribute(),
			'width' => $stub['embed-width'],
			'height' => $stub['embed-height'],
			'loading' => 'lazy',
			'referrerpolicy' => 'strict-origin-when-cross-origin',
			'allow' => 'fullscreen; picture-in-picture',
			'frameborder' => '0',
			'allowfullscreen' => true,
		];
	}

	/**
	 * @return string
	 */
	protected function titleAttribute(): string {
		$name = $this->name();
		if (!$name) {
			return 'Embedded media';
		}

		return $name . ' embed';
	}

	/**
	 * @param string $text
	 * @return string
	 */
	protected function esc(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
	}

	/**
	 * Switch the embed to its privacy-friendly variant when the provider declares one.
	 *
	 * A provider may declare a `privacy-player` URL template (e.g. youtube-nocookie.com)
	 * and/or `privacy-params` (e.g. Vimeo `dnt=1`) that are applied when the
	 * `privacy` config flag is enabled.
	 *
	 * @return void
	 */
	protected function applyPrivacyMode(): void {
		if (!empty($this->stub['privacy-player'])) {
			$this->stub['iframe-player'] = $this->stub['privacy-player'];
		}

		if (!empty($this->stub['privacy-params']) && is_array($this->stub['privacy-params'])) {
			foreach ($this->stub['privacy-params'] as $key => $value) {
				$this->iframeParams[$key] = $value;
			}
		}
	}

	/**
	 * Parse a "width:height" aspect ratio into its integer components.
	 *
	 * @param string $ratio Aspect ratio as "width:height".
	 * @throws \InvalidArgumentException When the ratio is malformed or has a zero component.
	 * @return array{0: int, 1: int}
	 */
	protected function parseRatio(string $ratio): array {
		if (!preg_match('/^\s*(\d+)\s*:\s*(\d+)\s*$/', $ratio, $matches)) {
			throw new InvalidArgumentException(sprintf('Invalid aspect ratio "%s", expected "width:height"', $ratio));
		}

		$width = (int)$matches[1];
		$height = (int)$matches[2];
		if ($width === 0 || $height === 0) {
			throw new InvalidArgumentException(sprintf('Aspect ratio "%s" must not contain a zero component', $ratio));
		}

		return [$width, $height];
	}

	/**
	 * Handle timestamp support for providers that support it.
	 *
	 * @return void
	 */
	protected function handleTimestampSupport(): void {
		// Only process if the provider supports timestamps
		if (empty($this->stub['supports-timestamp'])) {
			return;
		}

		// Check if we have a timestamp in the matches (capture group 2, which is index 2 in array)
		if (empty($this->match[2])) {
			return;
		}

		$timestampParam = $this->stub['timestamp-param'] ?? 'start';
		if (!$timestampParam) {
			return;
		}

		$timestamp = rtrim($this->match[2], 's');
		$this->iframeParams[$timestampParam] = $timestamp;
	}

	/**
	 * Returns an array that can be used to describe the internal state of this
	 * object.
	 *
	 * @return array<string, mixed>
	 */
	public function __debugInfo(): array {
		return [
			'stub' => $this->stub,
			'iframeAttributes' => $this->iframeAttributes,
			'iframeParams' => $this->iframeParams,
		];
	}

}
