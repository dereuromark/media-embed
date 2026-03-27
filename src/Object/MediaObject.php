<?php

namespace MediaEmbed\Object;

use MediaEmbed\Template\TemplateResolver;

/**
 * A generic media object for iframe embeds.
 */
class MediaObject implements ObjectInterface {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_stub;

	/**
	 * @var array<string>
	 */
	protected array $_match;

	/**
	 * Template resolver for URL interpolation.
	 */
	protected TemplateResolver $templateResolver;

	/**
	 * @var array<string, mixed>
	 */
	protected array $_iframeAttributes = [];

	/**
	 * @var array<string, mixed>
	 */
	protected array $_iframeParams = [];

	/**
	 * @var array<string, mixed>
	 */
	public array $config = [];

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
		$this->_stub = $stub + $stubDefaults;
		$this->_match = $this->_stub['match'];
		$this->_stub['id'] = $this->id();

		$this->_setDefaultParams($stub);

		if (isset($this->_stub['iframe-player'])) {
			$src = $this->_getObjectSrc('iframe-player');
			$this->_stub['iframe-player'] = $src;

			// Handle timestamps for providers that support them (e.g., YouTube)
			$this->_handleTimestampSupport();
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function id(): string {
		$res = $this->_match;
		$count = count($res);

		if (empty($this->_stub['id'])) {
			if (empty($res[$count - 1])) {
				return '';
			}
			$this->_stub['id'] = $res[$count - 1];
		}

		$id = $this->templateResolver->resolve($this->_stub['id'], $res);

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
		return $this->_stub['slug'];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function name(): string {
		if (empty($this->_stub['name'])) {
			return '';
		}

		return $this->templateResolver->resolve($this->_stub['name'], $this->_match);
	}

	/**
	 * Return the website URL of this type
	 *
	 * @return string
	 */
	public function website(): string {
		return !empty($this->_stub['website']) ? $this->_stub['website'] : '';
	}

	/**
	 * Returns a png img
	 *
	 * @return string|null Resource content or null if not available
	 */
	public function icon(): ?string {
		$url = $this->_stub['website'];
		if (!$url) {
			return null;
		}

		$pieces = parse_url($url);
		if (!$pieces || empty($pieces['host'])) {
			return null;
		}

		$url = $pieces['host'];

		$icon = 'http://www.google.com/s2/favicons?domain=';
		$icon .= $url;

		$context = stream_context_create(
			['http' => ['header' => 'Connection: close']],
		);
		// E.g. http://www.google.com/s2/favicons?domain=xyz.com
		$file = file_get_contents($icon, false, $context);
		if ($file === false) {
			return null;
		}

		// TODO: transform into 16x16 png
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
	 * Override a default iframe param value
	 *
	 * @param array<string, mixed>|string $param The name of the param to be set
	 *                                           or an array of multiple params to set
	 * @param string|null $value (optional) the value to set the param to
	 *                                              if only one param is being set
	 *
	 * @return $this
	 */
	public function setParam($param, ?string $value = null) {
		if (is_array($param)) {
			foreach ($param as $p => $v) {
				$this->_iframeParams[$p] = $v;
			}
		} else {
			$this->_iframeParams[$param] = $value;
		}

		return $this;
	}

	/**
	 * Override a default iframe attribute value
	 *
	 * @param array<string, mixed>|string $param The name of the attribute to be set
	 *   or an array of multiple attribs to be set
	 * @param string|int|null $value (optional) the value to set the param to
	 *   if only one param is being set
	 * @return $this
	 */
	public function setAttribute($param, $value = null) {
		if (is_array($param)) {
			foreach ($param as $p => $v) {
				$this->_iframeAttributes[$p] = $v;
			}
		} else {
			$this->_iframeAttributes[$param] = $value;
		}

		return $this;
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
			$this->_adjustDimensions('width', 'height', $height);
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
			$this->_adjustDimensions('height', 'width', $width);
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
	protected function _adjustDimensions(string $type, string $fromType, int $fromLength): void {
		$currentLength = $this->getAttributes($type);
		$currentFromLength = $this->getAttributes($fromType);

		$ratio = $fromLength / $currentFromLength;
		$newLength = $currentLength * $ratio;

		$this->setAttribute($type, (int)$newLength);
	}

	/**
	 * Return iframe params
	 *
	 * @param string|null $key
	 * @return array<string, mixed>|string|null Iframe params
	 */
	public function getParams(?string $key = null) {
		if ($key === null) {
			return $this->_iframeParams;
		}

		return $this->_iframeParams[$key] ?? null;
	}

	/**
	 * Return iframe attributes
	 *
	 * @param string|null $key
	 * @return mixed Iframe attribute
	 */
	public function getAttributes(?string $key = null) {
		if ($key === null) {
			return $this->_iframeAttributes;
		}

		return $this->_iframeAttributes[$key] ?? null;
	}

	/**
	 * Convert the url to an embeddable iframe tag
	 *
	 * @return string The embed HTML
	 */
	public function getEmbedCode(): string {
		return $this->_buildIframe();
	}

	/**
	 * Add src getter method
	 *
	 * @return string The src attribute
	 */
	public function getEmbedSrc(): string {
		$source = $this->templateResolver->resolve($this->_stub['iframe-player'], $this->_match);

		//add custom params
		if ($this->_iframeParams) {
			$c = '?';
			if (strpos($source, '?') !== false) {
				$c = '&amp;';
			}
			$source .= $c . http_build_query($this->_iframeParams, '', '&amp;');
		}

		return $source;
	}

	/**
	 * Get final iframe src
	 *
	 * @param string $type The stub key to use for the source URL.
	 * @return string|null
	 */
	protected function _getObjectSrc(string $type = 'iframe-player'): ?string {
		if (empty($this->_stub['id']) || empty($this->_stub['slug'])) {
			return null;
		}

		$stubSrc = $this->_stub[$type];
		$src = $this->templateResolver->resolveReverse($stubSrc, $this->_stub['id']);

		if (!empty($this->_stub['replace'])) {
			$src = $this->templateResolver->resolveReplacements($src, (array)$this->_stub['replace']);
		}

		return $src;
	}

	/**
	 * @return string|null
	 */
	public function getImageSrc(): ?string {
		if (empty($this->_stub['id'])) {
			return null;
		}
		if (empty($this->_stub['image-src'])) {
			return null;
		}

		return $this->templateResolver->resolveReverse($this->_stub['image-src'], $this->_stub['id']);
	}

	/**
	 * Return a thumbnail for the embeded video
	 *
	 * @return string - the thumbnail href
	 */
	public function image(): string {
		if (empty($this->_stub['image-src'])) {
			return '';
		}

		return $this->templateResolver->resolve($this->_stub['image-src'], $this->_match);
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
	 * Build an iFrame player
	 *
	 * @return string
	 */
	protected function _buildIframe(): string {
		$source = $this->templateResolver->resolve($this->_stub['iframe-player'], $this->_match);

		//add custom params
		if ($this->_iframeParams) {
			$c = '?';
			if (strpos($source, '?') !== false) {
				$c = '&amp;';
			}
			$source .= $c . http_build_query($this->_iframeParams, '', '&amp;');
		}
		$attributes = '';
		//add custom attributes

		foreach ($this->_iframeAttributes as $key => $val) {
			//if === true, is an attribute without value
			//if === false, remove the attribute
			if ($val !== false) {
				$attributes .= ' ' . $key . ($val !== true ? '="' . $this->_esc((string)$val) . '"' : '');
			}
		}

		// Transparent hack (http://groups.google.com/group/autoembed/browse_thread/thread/0ecdd9b898e12183)
		return sprintf('<iframe src="%s"%s></iframe>', $source, $attributes);
	}

	/**
	 * Set the default iframe params and attributes.
	 *
	 * @param array<string, mixed> $stub
	 * @return void
	 */
	protected function _setDefaultParams(array $stub): void {
		$this->_iframeParams = [
			'wmode' => 'transparent',
		];
		$this->_iframeAttributes = [
			'type' => 'text/html',
			'width' => $stub['embed-width'],
			'height' => $stub['embed-height'],
			'frameborder' => '0',
			'allowfullscreen' => true,
		];
	}

	/**
	 * @param string $text
	 * @return string
	 */
	protected function _esc(string $text): string {
		return htmlspecialchars($text, ENT_QUOTES, '', false);
	}

	/**
	 * Handle timestamp support for providers that support it (e.g., YouTube)
	 *
	 * @return void
	 */
	protected function _handleTimestampSupport(): void {
		// Only process if the provider supports timestamps
		if (empty($this->_stub['supports-timestamp'])) {
			return;
		}

		// Check if we have a timestamp in the matches (capture group 2, which is index 2 in array)
		if (empty($this->_match[2])) {
			return;
		}

		$timestamp = $this->_match[2];

		// For YouTube, convert 't' parameter to 'start' parameter for embed URLs
		if ($this->_stub['slug'] === 'youtube') {
			// Remove 's' suffix if present (e.g., "3724s" -> "3724")
			$timestamp = rtrim($timestamp, 's');

			// Add as iframe parameter
			$this->_iframeParams['start'] = $timestamp;
		}
	}

	/**
	 * Returns an array that can be used to describe the internal state of this
	 * object.
	 *
	 * @return array<string, mixed>
	 */
	public function __debugInfo(): array {
		return [
			'stub' => $this->_stub,
			'iframeAttributes' => $this->_iframeAttributes,
			'iframeParams' => $this->_iframeParams,
		];
	}

}
