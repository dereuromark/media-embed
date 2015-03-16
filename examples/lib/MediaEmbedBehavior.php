<?php

use MediaEmbed\MediaEmbed;

class MediaEmbedBehavior {

	/**
	 * We translate all BBCodes into HTML now.
	 *
	 * @param string $string
	 * @return string
	 */
	public function prepareForOutput($string) {
		return preg_replace_callback('/\[video=?(.*?)\](.*?)\[\/video\]/is', [$this, '_finalizeVideo'], $string);
	}

	/**
	 * @param array $params
	 * @return string
	 */
	protected function _finalizeVideo($params) {
		if (!isset($this->MediaEmbed)) {
			$this->MediaEmbed = new MediaEmbed();
		}
		$host = $params[1];
		$id = $params[2];
		if (!($MediaObject = $this->MediaEmbed->parseId($id, $host))) {
			return $params[0];
		}

		return $MediaObject->getEmbedCode();
	}

	/**
	 * We simulate a save operation and simply return the modified string again.
	 *
	 * @param string $string
	 * @return string
	 */
	public function simulateSave($string) {
		return preg_replace_callback('/\[video=?(.*?)\](.*?)\[\/video\]/is', [$this, '_processVideo'], $string);
	}

	/**
	 * @param array $params
	 * @return string
	 */
	protected function _processVideo($params) {
		if (!isset($this->MediaEmbed)) {
			$this->MediaEmbed = new MediaEmbed();
		}
		$url = $params[2];
		if (strpos($url, 'www.') === 0) {
			$url = 'http://' . $url;
		}
		if (!($MediaObject = $this->MediaEmbed->parseUrl($url))) {
			return $params[0];
		}
		$slug = $MediaObject->slug();
		if (!$slug) {
			$slug = $params[1];
		}
		if ($slug) {
			$slug = '=' . $slug;
		}
		$id = $MediaObject->id();
		$result = '[video' . $slug . ']' . $id . '[/video]';
		return $result;
	}

}