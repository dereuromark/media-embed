<?php

namespace MediaEmbed;

use \MediaEmbed\Object\MediaObject;

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

	protected $_match;

	protected $_stubs = array();

	protected $_hosts = array();

	/**
	 * See MediaObject for details
	 */
	public $config = array();

	/**
	 * Loads stubs
	 */
	public function __construct(array $config = array()) {
		include dirname(__FILE__) . DS . 'Data' . DS . 'stubs.php';
		$this->_stubs = $stubs;

		$this->config = $config += $this->config;
	}

	/**
	 * Prepare embed video from different video hosts.
	 *
	 * @param array $data:
	 * - id
	 * - host (slugged)
	 * @param array $flashParams
	 * @return \MediaEmbed\Object\MediaObject|null
	 */
	public function parseId($id, $host, $config = array()) {
		if (empty($id) || empty($host)) {
			return;
		}

		// local files?
		if ($host === 'local') {
			$res = $this->embedLocal($id);
			if (!$res) {
				$stub = array();
				$Object = $this->object($stub, $config);
				return $Object;
			}
			//TODO
			return;
		}

		// all other hosts
		if (!($host = $this->getHost($host))) {var_dump($host);
			return;
		}
		$stub = $host;
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
	 * @param $url string - href to check for embeded video
	 * @param array $config
	 * @return \MediaEmbed\Object\MediaObject|null
	 */
	public function parseUrl($url, $config = array()) {
		foreach ($this->_stubs as $stub) {
			if (preg_match('~' . $stub['url-match'] . '~imu', $url, $match)) {
var_dump($match);
				$this->_match = $match;

				if (!empty($stub['fetch-match'])) {
					if (!$this->_parseLink($url, $stub['fetch-match'])) {
						return;
					}
				}

				$stub['match'] = $this->_match;
				$Object = $this->object($stub, $config + $this->config);
				return $Object;
			}
		}
	}

	/**
	 * Attempt to parse the embed id from a given URL
	 */
	protected function _parseLink($url, $regex) {
		$context = stream_context_create(
			array('http' => array('header' => 'Connection: close')));
		$source = preg_replace('/[^(\x20-\x7F)]*/', '', file_get_contents($url, 0, $context));

		if (preg_match('~' . $regex . '~imu', $source, $match)) {
			$this->_match = $match;
			return true;
		}

		return false;
	}

	/**
	 * @param array $whitelist (alias/keys)
	 * @return array hostInfos or false on failure
	 */
	public function getHosts($whitelist = array()) {
		if (!$this->_hosts) {
			$res = array();
			foreach ($this->_stubs as $stub) {
				$slug = $this->_slug($stub['name']);

				// latest ones in stubs array override predecessors (they are the newest ones!)
				$res[$slug] = $stub;
			}

			$this->_hosts = $res;
		}

		if ($whitelist) {
			$res = array();
			foreach ($this->_hosts as $slug => $host) {
				if (!in_array($slug, $whitelist)) {
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
	 * @return array hostInfos or false on failure
	 */
	public function getHost($alias) {
		if (!$this->_hosts) {
			$this->_hosts = $this->getHosts();
		}
		if (empty($this->_hosts[$alias])) {
			return false;
		}
		return $this->_hosts[$alias];
	}

	/**
	 * Create the embed code for a local file
	 *
	 * @param $file string - the file we are wanting to embed
	 * @return bool Whether or not the url contains valid/supported video
	 */
	public function embedLocal($file) {
		return false;
	}

	public function object($stub, array $config) {
		if (!is_array($stub)) {
			$host = $this->getHost($stub);
			if (!$host) {
				return;
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
	protected function _slug($text) {
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		// trim
		$text = trim($text, '-');

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);
		return $text;
	}

/* deprecated */

	/**
	 * Contains the preg info
	 * DOES NOT contain width/height etc
	 */
	public $availableTypes = array(
		'youtube' => array(
			'iframe' => 'http://www.youtube.com/embed/{id}',
			'swf' => 'http://www.youtube.com/v/{id}',
			'url' => 'http://www.youtube.com/watch?v={id}&t={t}',
		),
		'google' => array(
		),
		'myvideo' => array(
			'swf' => 'http://www.myvideo.de/movie/',
			'url' => array('http://www.myvideo.de/watch/', 'http://www.myvideo.ch/watch/', 'http://www.myvideo.at/watch/'),
		),
		'vimeo' => array(
			'iframe' => 'http://player.vimeo.com/video/{id}',
			//'swf' => '',
			'url' => ''
		),
		'dailymotion' => array(
			'swf' => 'http://www.dailymotion.com/embed/video/{id}',
			//'url' => 'http://www.dailymotion.com'
		),
		'videojug' => array(
		),
		'revver' => array(
		)
	);

	public $availableNonJSTypes = array(
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
	);

	public $_ojectParamAttr = array(
		'allowscriptaccess' => 'always',
		'allowfullscreen' => 'true',
	);

	public $_embedAttr = array(
		'allowfullscreen' => 'true',
		''
	);

}
