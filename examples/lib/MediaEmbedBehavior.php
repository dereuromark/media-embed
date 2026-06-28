<?php

declare(strict_types=1);

use MediaEmbed\MediaEmbed;

class MediaEmbedBehavior {

	private ?MediaEmbed $mediaEmbed = null;

	/**
	 * Translate stored BBCodes into HTML.
	 */
	public function prepareForOutput(string $string): string {
		return (string)preg_replace_callback('/\[video=?(.*?)\](.*?)\[\/video\]/is', [$this, 'finalizeVideo'], $string);
	}

	/**
	 * Simulate a save operation and return normalized video BBCodes.
	 */
	public function simulateSave(string $string): string {
		return (string)preg_replace_callback('/\[video=?(.*?)\](.*?)\[\/video\]/is', [$this, 'processVideo'], $string);
	}

	/**
	 * @param array<int, string> $params
	 */
	protected function finalizeVideo(array $params): string {
		$mediaObject = $this->mediaEmbed()->parseId($params[2], $params[1]);
		if ($mediaObject === null) {
			return $params[0];
		}

		return $mediaObject->getEmbedCode();
	}

	/**
	 * @param array<int, string> $params
	 */
	protected function processVideo(array $params): string {
		$url = $params[2];
		if (str_starts_with($url, 'www.')) {
			$url = 'https://' . $url;
		}

		$mediaObject = $this->mediaEmbed()->parseUrl($url);
		if ($mediaObject === null) {
			return $params[0];
		}

		return '[video=' . $mediaObject->slug() . ']' . $mediaObject->id() . '[/video]';
	}

	protected function mediaEmbed(): MediaEmbed {
		if ($this->mediaEmbed === null) {
			$this->mediaEmbed = new MediaEmbed();
		}

		return $this->mediaEmbed;
	}

}
