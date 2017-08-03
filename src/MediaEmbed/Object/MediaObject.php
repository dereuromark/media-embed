<?php

namespace MediaEmbed\Object;

use MediaEmbed\Object\ObjectInterface;

/**
 * A generic object - for now.
 *
 * TODO: Implement audio, video separatly
 */
class MediaObject implements ObjectInterface {

	/**
	 * @var array
	 */
	protected $_stub;

	/**
	 * @var array
	 */
	protected $_match;

	/**
	 * @var array
	 */
	protected $_objectAttributes = [];

	/**
	 * @var array
	 */
	protected $_objectParams = [];

	/**
	 * @var array
	 */
	protected $_iframeAttributes = [];

	/**
	 * @var array
	 */
	protected $_iframeParams = [];

	/**
	 * @var array
	 */
	public $config = [
		'prefer' => 'iframe' // Type object or iframe (only available for few, fallback will be object)
	];

	/**
	 * MediaObject::__construct()
	 *
	 * @param array $stub
	 * @param array $config
	 */
	public function __construct(array $stub, array $config) {
		$this->config = $config += $this->config;

		$stubDefaults = [
			'id' => '',
			'name' => '',
			'website' => '',
			'slug' => '',
			'match' => []
		];
		$this->_stub = $stub + $stubDefaults;
		$this->_match = $this->_stub['match'];
		$this->_stub['id'] = $this->id();

		$this->_setDefaultParams($stub);

		$type = 'embed-src';
		if (isset($this->_stub['iframe-player'])) {
			if ($this->config['prefer'] === 'iframe') {
				$type = 'iframe-player';
			}
		}

		if ($type === 'iframe-player') {
			$src = $this->_getObjectSrc($type);
			$this->_stub['iframe-player'] = $src;

			$this->_objectParams['movie'] = $src;
			$this->_objectAttributes['data'] = $src;
		}

		if (empty($this->_stub['reverse'])) {
			return;
		}

		$flashvars = $this->_objectParams['flashvars'];
		if (strpos($flashvars, '$r2') !== false) {
			$this->_objectParams['flashvars'] = str_replace('$r2', $this->_stub['id'], $flashvars);
		} else {
			$this->_objectParams['flashvars'] = str_replace('$2', $this->_stub['id'], $flashvars);
		}
	}

	/**
	 * Getter/setter for stub
	 *
	 * @param string|null $property - (optional) the specific
	 *     property of the stub to be returned. If
	 *     omitted, array of all properties are returned.
	 * @param string|null $value
	 *
	 * @return $this|array|string
	 */
	public function stub($property = null, $value = null) {
		if ($property === null) {
			return $this->_stub;
		}
		if ($value === null) {
			return isset($this->_stub[$property]) ? $this->_stub[$property] : null;
		}
		if (!empty($property) && !empty($value)) {
			$this->_stub[$property] = $value;
		}
		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function id() {
		$res = $this->_match;
		$count = count($res);

		if (empty($this->_stub['id'])) {
			if (empty($res[$count - 1])) {
				return '';
			}
			$this->_stub['id'] = $res[$count - 1];
		}
		$id = $this->_stub['id'];

		for ($i = 1; $i <= $count; $i++) {
			$id = str_ireplace('$' . $i, $res[$i - 1], $id);
		}
		return $id;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function slug() {
		return $this->_stub['slug'];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function name() {
		$res = $this->_match;
		$count = count($res);

		if (empty($this->_stub['name'])) {
			return '';
		}
		$name = $this->_stub['name'];

		for ($i = 1; $i <= $count; $i++) {
			$name = str_ireplace('$' . $i, $res[$i - 1], $name);
		}
		return $name;
	}

	/**
	 * Return the website URL of this type
	 *
	 * @return string
	 */
	public function website() {
		return !empty($this->_stub['website']) ? $this->_stub['website'] : '';
	}

	/**
	 * Returns a png img
	 *
	 * @return resource|null Resource or null if not available
	 */
	public function icon() {
		$url = $this->_stub['website'];
		if (!$url) {
			return null;
		}

		$pieces = parse_url($url);
		$url = $pieces['host'];

		$icon = 'http://www.google.com/s2/favicons?domain=';
		$icon .= $url;

		$context = stream_context_create(
			['http' => ['header' => 'Connection: close']]);
		// E.g. http://www.google.com/s2/favicons?domain=xyz.com
		$file = file_get_contents($icon, 0, $context);
		if ($file === false) {
			return null;
		}
		// TODO: transform into 16x16 png
		return $file;
	}

	/**
	 * @param string|null $location Absolute path with trailing slash
	 * @param string|null $icon Icon data
	 * @return string|null $filename
	 */
	public function saveIcon($location = null, $icon = null) {
		if ($icon === null) {
			$icon = $this->icon();
		}
		if (!$icon) {
			return null;
		}
		if (!$location) {
			$location = IMAGES . 'content' . DS . 'video_types';
			if (!is_dir($location)) {
				mkdir($location, 0755, true);
			}
			$location .= DS;
		}
		$filename = $this->slug() . '.png';
		$file = $location . $filename;
		if (!file_put_contents($file, $icon)) {
			return null;
		}
		return $filename;
	}

	/**
	 * Override a default object param value
	 *
	 * @param mixed $param The name of the param to be set
	 *                                           or an array of multiple params to set
	 * @param string|null $value (optional) the value to set the param to
	 *                                              if only one param is being set
	 *
	 * @return $this
	 */
	public function setParam($param, $value = null) {
		if (!empty($this->_stub['iframe-player']) && $this->config['prefer'] === 'iframe') {
			if (is_array($param)) {
				foreach ($param as $p => $v) {
					$this->_iframeParams[$p] = $v;
				}

			} else {
				$this->_iframeParams[$param] = $value;
			}
		} else {
			if (is_array($param)) {
				foreach ($param as $p => $v) {
					$this->_objectParams[$p] = $v;
				}

			} else {
				$this->_objectParams[$param] = $value;
			}
		}
		return $this;
	}

	/**
	 * Override a default object attribute value
	 *
	 * @param mixed $param  The name of the attribute to be set
	 *   or an array of multiple attribs to be set
	 * @param string|null $value (optional) the value to set the param to
	 *   if only one param is being set
	 * @return $this
	 */
	public function setAttribute($param, $value = null) {
		if (!empty($this->_stub['iframe-player']) && $this->config['prefer'] === 'iframe') {
			if (is_array($param)) {
				foreach ($param as $p => $v) {
					$this->_iframeAttributes[$p] = $v;
				}

			} else {
				$this->_iframeAttributes[$param] = $value;
			}
		} else {
			if (is_array($param)) {
				foreach ($param as $p => $v) {
					$this->_objectAttributes[$p] = $v;
				}

			} else {
				$this->_objectAttributes[$param] = $value;
			}
		}
		return $this;
	}

	/**
	 * Set the height of the object
	 *
	 * @param mixed $height Height to set the object to
	 * @param bool $adjustWidth
	 * @return $this
	 */
	public function setHeight($height, $adjustWidth = false) {
		if ($adjustWidth && is_numeric($height)) {
			$this->_adjustDimensions('width', 'height', $height);
		}
		return $this->setAttribute('height', $height);
	}

	/**
	 * Set the width of the object
	 *
	 * @param mixed $width Width to set the object to
	 * @param bool $adjustHeight
	 * @return $this
	 */
	public function setWidth($width, $adjustHeight = false) {
		if ($adjustHeight && is_numeric($width)) {
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
	protected function _adjustDimensions($type, $fromType, $fromLength) {
		$currentLength = $this->getAttributes($type);
		$currentFromLength = $this->getAttributes($fromType);

		$ratio = $fromLength / $currentFromLength;
		$newLength = $currentLength * $ratio;

		$this->setAttribute($type, (int)$newLength);
	}

	/**
	 * Return object params about the video metadata
	 *
	 * @param string|null $key
	 * @return array|string - object params
	 */
	public function getParams($key = null) {
		if (!empty($this->_stub['iframe-player']) && $this->config['prefer'] === 'iframe') {
			if ($key === null) {
				return $this->_iframeParams;
			}
			if (!isset($this->_iframeParams[$key])) {
				return null;
			}
			return $this->_iframeParams[$key];
		}

		if ($key === null) {
			return $this->_objectParams;
		}
		if (!isset($this->_objectParams[$key])) {
			return null;
		}
		return $this->_objectParams[$key];
	}

	/**
	 * Return object attribute
	 *
	 * @param string|null $key
	 * @return array - object attribute
	 */
	public function getAttributes($key = null) {
		if (!empty($this->_stub['iframe-player']) && $this->config['prefer'] === 'iframe') {
			if ($key === null) {
				return $this->_iframeAttributes;
			}
			if (!isset($this->_iframeAttributes[$key])) {
				return null;
			}
			return $this->_iframeAttributes[$key];
		}

		if ($key === null) {
			return $this->_objectAttributes;
		}
		if (!isset($this->_objectAttributes[$key])) {
			return null;
		}
		return $this->_objectAttributes[$key];
	}

	/**
	 * Convert the url to an embeddable tag
	 *
	 * @return string The embed HTML
	 */
	public function getEmbedCode() {
		if (!empty($this->_stub['iframe-player']) && $this->config['prefer'] === 'iframe') {
			return $this->_buildIframe();
		}
		return $this->_buildObject();
	}

	/**
	 * Getter/setter of what this Object currently prefers as output type
	 *
	 * @param string|null $type
	 * @return $this|string
	 */
	protected function prefers($type = null) {
		if ($type === null) {
			$prefers = 'object';
			if (!empty($this->_stub['iframe-player']) && $this->config['prefer'] === 'iframe') {
				$prefers = 'iframe';
			}
			return $prefers;
		}
		$this->config['prefer'] = $type;
		return $this;
	}

	/**
	 * Get final src
	 *
	 * @param string $type
	 * @return string|null
	 */
	protected function _getObjectSrc($type = 'embed-src') {
		if (empty($this->_stub['id']) || empty($this->_stub['slug'])) {
			return null;
		}

		$stubSrc = $this->_stub[$type];
		if (strpos($stubSrc, '$r2') !== false) {
			$src = str_replace('$r2', $this->_stub['id'], $stubSrc);
		} else {
			$src = str_replace('$2', $this->_stub['id'], $stubSrc);
		}
		if (!empty($host['replace'])) {
			foreach ($host['replace'] as $placeholder => $replacement) {
				$src = str_replace($placeholder, $replacement, $src);
			}
		}
		return $src;
	}

	/**
	 * VideoLib::getImageSrc()
	 *
	 * @return string|null
	 */
	public function getImageSrc() {
		if (empty($this->_stub['id'])) {
			return null;
		}
		if (empty($this->_stub['image-src'])) {
			return null;
		}

		$stubImgSrc = $this->_stub['image-src'];
		if (strpos($stubImgSrc, '$r2') !== false) {
			$src = str_replace('$r2', $this->_stub['id'], $stubImgSrc);
		} else {
			$src = str_replace('$2', $this->_stub['id'], $stubImgSrc);
		}

		return $src;
	}

	/**
	 * Return a thumbnail for the embeded video
	 *
	 * @return string - the thumbnail href
	 */
	public function image() {
		if (empty($this->_stub['image-src'])) {
			return '';
		}
		$thumb = $this->_stub['image-src'];

		$count = count($this->_match);
		for ($i = 1; $i <= $count; $i++) {
			$thumb = str_ireplace('$' . $i, $this->_match[$i - 1], $thumb);
		}

		return $thumb;
	}

	/**
	 * Convenience wrapper for `echo $MediaObject`
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getEmbedCode();
	}

	/**
	 * Build a generic object skeleton
	 *
	 * @return string
	 */
	protected function _buildObject() {
		$objectAttributes = $objectParams = '';

		foreach ($this->_objectAttributes as $param => $value) {
			$objectAttributes .= ' ' . $param . '="' . $value . '"';
		}

		foreach ($this->_objectParams as $param => $value) {
			$objectParams .= '<param name="' . $param . '" value="' . $value . '" />';
		}

		if (!$objectAttributes && !$objectParams) {
			return '';
		}
		return sprintf('<object %s> %s</object>', $objectAttributes, $objectParams);
	}

	/**
	 * Build an iFrame player
	 *
	 * @return string
	 */
	protected function _buildIframe() {
		$source = $this->_stub['iframe-player'];
		$count = count($this->_match);

		for ($i = 1; $i <= $count; $i++) {
			$source = str_ireplace('$' . $i, $this->_match[$i - 1], $source);
		}

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
				$attributes .= ' ' . $key . ($val !== true ? '="' . $this->_esc($val) . '"' : '');
			}
		}

		// Transparent hack (http://groups.google.com/group/autoembed/browse_thread/thread/0ecdd9b898e12183)
		return sprintf('<iframe src="%s"%s></iframe>', $source, $attributes);
	}

	/**
	 * Set the default params for the type of
	 * stub we are working with
	 *
	 * @param array $stub
	 * @return void
	 */
	protected function _setDefaultParams($stub) {
		$source = $stub['embed-src'];
		$flashvars = (isset($stub['flashvars'])) ? $stub['flashvars'] : null;
		$count = count($this->_match);

		for ($i = 1; $i <= $count; $i++) {
			$source = str_ireplace('$' . $i, $this->_match[$i - 1], $source);
			$flashvars = str_ireplace('$' . $i, $this->_match[$i - 1], $flashvars);
		}

		$source = $this->_esc($source);
		$flashvars = $this->_esc($flashvars);

		$this->_objectParams = [
			'movie' => $source,
			'quality' => 'high',
			'allowFullScreen' => 'true',
			'allowScriptAccess' => 'always',
			'pluginspage' => 'http://www.macromedia.com/go/getflashplayer',
			'autoplay' => 'false',
			'autostart' => 'false',
			'flashvars' => $flashvars,
		];

		$this->_objectAttributes = [
			'type' => 'application/x-shockwave-flash',
			'data' => $source,
			'width' => $stub['embed-width'],
			'height' => $stub['embed-height'],
		];

		//separate iframe params and attributes
		$this->_iframeParams = [
			'wmode' => 'transparent'
		];
		$this->_iframeAttributes = [
			'type' => 'text/html',
			'width' => $stub['embed-width'],
			'height' => $stub['embed-height'],
			'frameborder' => '0',
			'allowfullscreen' => true
		];
	}

	/**
	 * MediaObject::_esc()
	 *
	 * @param string $text
	 * @return string
	 */
	protected function _esc($text) {
		return htmlspecialchars($text, ENT_QUOTES, null, false);
	}

}
