# MediaEmbed [![Build Status](https://secure.travis-ci.org/dereuromark/MediaEmbed.png?branch=master)](http://travis-ci.org/dereuromark/MediaEmbed)
A utility that generates HTML embed tags for audio or video located on a given URL.
It also parses and validates given media URLs.

It currently works with 100+ services, including the most important ones like

- YouTube
- Dailymotion
- MyVideo
- Vimeo
- Ustream

etc. With community driven updates this aims to be a compete and up-to-date service wrapper lib.

It uses iframes if possible, and has a fallback on the embed object if necessary.

## Requirements

- PHP 5.3+
- Composer

### Note
This is alpha-software. Please feel free to join in and help out to complete it.
Once the coverage is high enough we can release a beta and soon after the first stable.


## Installation

create `composer.json`:

```json
{
    "require": {
        "dereuromark/media-embed": "dev-master"
    }
}
```

run:

```bash
php composer.phar install
```

## API Overview

### Parsing
You can either use `parseUrl()` (default lookup) or `parseId()` (reverse lookup) of `MediaEmbed`.
The latter is useful if you only store the "host slug" and "id" in the dabatase instead of the
complete URL.
Both methods will return an `MediaObject` object, which will contain the parsed input.

### Output
You can then display the HTML code with `getEmbedCode()` or retrieve more information using the getters of `MediaObject`.


## Usage
The simpliest usage, when included via composer autoload, would be:
```php
// At the top of the file
use MediaEmbed\MediaEmbed;

// Somewhere in your (class) code
$MediaEmbed = new MediaEmbed();
```

### Example with "host slug" and "id" saved in DB
A helper method
```php
public function video($host, $id, $options = array()) {
	if (!isset($this->MediaEmbed)) {
		$this->MediaEmbed = new MediaEmbed($options);
	}
	$MediaObject = $this->MediaEmbed->parseId(array('host' => $host, 'id' => $id));
	if (!$MediaObject) {
		return '';
	}
	return $MediaObject->getEmbedCode();
}
```

### Example with BBCode

#### Parse video content upon save (db input)
```php
/**
 * @param mixed $string
 * @return string
 */
protected function _parseVideo($string) {
	return preg_replace_callback('/\[video=?(.*?)\](.*?)\[\/video\]/is', array($this, '_processVideo'), $string);
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
```

So `[video]http://www.youtube.com/v/123[/video]` becomes [video=youtube]123[/video].

#### Display the resulting code snippet upon display
```php
/**
 * @param string $string
 * @return string
 */
public function prepareForOutput($string) {
	return preg_replace_callback('/\[video=?(.*?)\](.*?)\[\/video\]/is', array($this, '_finalizeVideo'), $string);
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
	if (!($MediaObject = $this->MediaEmbed->parseId(array('host' => $host, 'id' => $id)))) {
		return $params[0];
	}

	return $MediaObject->getEmbedCode();
}
```

So `[video]123[/video]` becomes `<iframe ...>...</iframe>` or `<object ...><embed src="..."</embed></object>`.

### More examples
You can see live examples when you get this repo running locally and browse to `examples` dir.
`index.php` has a list of examples, you can live-preview. `bbcode.php` shows how to use it in save/read callbacks.

## Contribute / TODOs
See [wiki](https://github.com/dereuromark/MediaEmbed/wiki).

## License

The MIT License.