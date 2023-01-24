<?php

namespace MediaEmbed;

use MediaEmbed\Object\MediaObject;
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
	 * Loads stubs
	 *
	 * @param array<string, mixed> $config
	 * @param string|null $stubsPath
	 */
	public function __construct(array $config = [], ?string $stubsPath = null) {
		if ($stubsPath === null) {
			$stubsPath = dirname(__DIR__) . DS . 'data' . DS . 'stubs.php';
		}
		$stubs = include $stubsPath;
		$this->setHosts($stubs);
		$this->config = $config + $this->config;
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

		// local files?
		if ($host === 'local') {
			$res = $this->embedLocal($id);
			if (!$res) {
				$stub = [];
				$Object = $this->object($stub, $config);

				return $Object;
			}

			//TODO
			return null;
		}

		// all other hosts
		$hostArray = $this->getHost($host);
		if (!$hostArray) {
			return null;
		}
		$stub = $hostArray;
		$config += $this->config;

		$stub['id'] = $id;
		$stub['reverse'] = true;
		$Object = $this->object($stub, $config);

		return $Object;
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
		foreach ($this->_hosts as $stub) {
			$match = $this->_matchUrl($url, (array)$stub['url-match']);
			if (!$match) {
				continue;
			}

			$this->_match = $match;

			if (!empty($stub['fetch-match'])) {
				if (!$this->_parseLink($url, $stub['fetch-match'])) {
					return null;
				}
			}

			$stub['match'] = $this->_match;
			$Object = $this->object($stub, $config + $this->config);

			return $Object;
		}

		return null;
	}

	/**
	 * MediaEmbed::_match()
	 *
	 * @param string $url
	 * @param array<string> $regexRules
	 * @return array<string>
	 */
	protected function _matchUrl(string $url, array $regexRules): array {
		foreach ($regexRules as $regexRule) {

			if (preg_match('~' . $regexRule . '~imu', $url, $match)) {
				return $match;
			}
		}

		return [];
	}

	/**
	 * Attempt to parse the embed id from a given URL
	 *
	 * @param string $url
	 * @param string $regex
	 * @return bool
	 */
	protected function _parseLink(string $url, string $regex): bool {
		$context = stream_context_create(
			['http' => ['header' => 'Connection: close']],
		);
		$content = file_get_contents($url, false, $context);
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
	 * @param string $alias
	 * @return array<string, mixed>|null Host info or null on failure
	 */
	public function getHost(string $alias): ?array {
		if (!$this->_hosts) {
			$this->_hosts = $this->getHosts();
		}
		if (empty($this->_hosts[$alias])) {
			return null;
		}

		return $this->_hosts[$alias];
	}

	/**
	 * Create the embed code for a local file
	 *
	 * @param string $file The file we are wanting to embed
	 * @return bool Whether the URL contains valid/supported video
	 */
	public function embedLocal(string $file): bool {
		return false;
	}

	/**
	 * @param array<string, mixed>|string $stub
	 * @param array<string, mixed> $config
	 *
	 * @return \MediaEmbed\Object\MediaObject|null
	 */
	public function object($stub, array $config = []): ?MediaObject {
		if (!is_array($stub)) {
			$host = $this->getHost($stub);
			if (!$host) {
				return null;
			}
			$stub = $host;
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

	/**
	 * Contains the preg info
	 * DOES NOT contain width/height etc
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $availableTypes = [
		'youtube' => [
			'iframe' => 'http://www.youtube.com/embed/{id}',
			'swf' => 'http://www.youtube.com/v/{id}',
			'url' => ['http://www.youtube.com/watch?v={id}&t={t}', 'http://www.youtube.com/shorts/{id}', 'http://www.youtube.com/shorts/{id}?feature=share'],
		],
		'google' => [
		],
		'myvideo' => [
			'swf' => 'http://www.myvideo.de/movie/',
			'url' => ['http://www.myvideo.de/watch/', 'http://www.myvideo.ch/watch/', 'http://www.myvideo.at/watch/'],
		],
		'vimeo' => [
			'iframe' => 'http://player.vimeo.com/video/{id}',
			//'swf' => '',
			'url' => '',
		],
		'dailymotion' => [
			'swf' => 'http://www.dailymotion.com/embed/video/{id}',
		//'url' => 'http://www.dailymotion.com'
		],
		'videojug' => [
		],
		'revver' => [
		],
	];

	/**
	 * @var array<string, string>
	 */
	public array $availableNonJSTypes = [
		'youtube' => '<iframe src="http://www.youtube.com/embed/{id}" width="100%" height="385" frameborder="0"></iframe>',
		'vimeo' => '<iframe src="http://player.vimeo.com/video/{id}" width="100%" height="385" frameborder="0"></iframe>',
		'google' => '<embed id="test" src="http://video.google.com/googleplayer.swf?docid={id}&hl=de&fs=true" style="width:100%;height:385px" allowFullScreen="true" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>',
		'myvideo' => '<embed src="http://www.myvideo.de/movie/{id}" width="100%" height="385" type="application/x-shockwave-flash"></embed>',
		#'flv' => '',
		#'mp4' => ''
		'dailymotion' => '<iframe frameborder="0" width="100%" height="385" src="http://www.dailymotion.com/embed/video/{id}?width=&theme=none&foreground=%23F7FFFD&highlight=%23FFC300&background=%23171D1B&start=&animatedTitle=&iframe=1&additionalInfos=0&autoPlay=0&hideInfos=0"></iframe>',
		//'dailymotion' => '<object width="480" height="269"><param name="movie" value="http://www.dailymotion.com/swf/video/{id}?additionalInfos=0" width="480" height="269" allowfullscreen="true" allowscriptaccess="always"></embed></object>',
		'videojug' => '<embed src="http://www.videojug.com/player?id={id}" type="application/x-shockwave-flash" width="100%" height="385" allowFullScreen="true" allowScriptAccess="always"></embed>',
		'revver' => '<embed type="application/x-shockwave-flash" src="http://flash.revver.com/player/1.0/player.swf?mediaId={id}" pluginspage="http://www.macromedia.com/go/getflashplayer" allowScriptAccess="always" flashvars="allowFullScreen=true" allowfullscreen="true" height="385" width="100%"></embed>',
		'xvideos' => '<embed src="http://static.xvideos.com/swf/flv_player_site_v4.swf" allowscriptaccess="always" width="100%" height="385" menu="false" quality="high" bgcolor="#000000" allowfullscreen="true" flashvars="id_video={id}" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />',
	];

	/**
	 * @var array<string, string>
	 */
	public array $_ojectParamAttr = [
		'allowscriptaccess' => 'always',
		'allowfullscreen' => 'true',
	];

	/**
	 * @var array<string, string>
	 */
	public array $_embedAttr = [
		'allowfullscreen' => 'true',
	];

}
