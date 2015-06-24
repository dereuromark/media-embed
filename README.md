# MediaEmbed
[![Build Status](https://secure.travis-ci.org/dereuromark/MediaEmbed.png?branch=master)](http://travis-ci.org/dereuromark/MediaEmbed)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/media-embed/license.png)](https://packagist.org/packages/dereuromark/media-embed)
[![Total Downloads](https://poser.pugx.org/dereuromark/media-embed/d/total.png)](https://packagist.org/packages/dereuromark/media-embed)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A stand-alone utility library that generates HTML embed tags for audio or video located on a given URL.
It also parses and validates given media URLs.

It currently works with 150+ services, including the most important ones like

- YouTube
- Dailymotion
- MyVideo
- Vimeo
- Ustream

etc. With community driven updates this aims to be a complete and up-to-date service wrapper lib.

It uses iframes if possible, and has a fallback on the embed object if necessary.

## Requirements

- PHP 5.4+
- Composer

### Note
Please feel free to join in and help out to further improve or complete it.
There are always some providers changing their URLs/API or some new ones which are not yet completed.

## Installation

create `composer.json`:

```json
{
    "require": {
        "dereuromark/media-embed": "0.*"
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
The simplest usage, when included via composer autoload, would be:
```php
// At the top of the file
use MediaEmbed\MediaEmbed;

// Somewhere in your (class) code
$MediaEmbed = new MediaEmbed();
```

### Auto-transforming user posted URLs in inline content

Usually, users don't care or don't know how exactly a video is linked/embedded.
So if they just paste the URL of the browser, you can directly replace those URLs with the HTML code of it:
```php
// Process all links in some content
public function autoLink($text) {
	return preg_replace_callback(..., [&$this, '_linkUrls'], $text);
}

protected function _linkUrls($matches) {
	if (!isset($this->MediaEmbed)) {
		$this->MediaEmbed = new MediaEmbed();
	}
	if ($MediaObject = $this->MediaEmbed->parseUrl($url)) {
		return $MediaObject->getEmbedCode();
	}
	// No media match found - normal <a href="...">...</a> replacing here
}
```

As this is costly when used at runtime, it is usually better to parse the URL upon save
and transform it into a BBCode like syntax that can be translated into HTML quicker and easier.

### Example with "host slug" and "id" saved in DB
When a URL is posted in the video field (varchar 255), we can extract the data from it and validate it:
```php
$id = $host = null;
if ($MediaObject = $this->MediaEmbed->parseUrl($url)) {
	$id = $MediaObject->id();
	$host = $MediaObject->slug();
}
```
Those two values can be stored persistently (the complete URL including schema might change).

A helper method can then display it:
```php
public function video($host, $id, array $options = []) {
	if (!isset($this->MediaEmbed)) {
		$this->MediaEmbed = new MediaEmbed($options);
	}
	$MediaObject = $this->MediaEmbed->parseId($id, $host);
	if (!$MediaObject) {
		return '';
	}
	if (!empty($options['attributes'])) {
		foreach ($options['attributes'] as $attribute => $value) {
			$MediaObject->setAttribute($attribute, $value);
		}
	}
	return $MediaObject->getEmbedCode();
}
```

### Customizing attributes and params in the embed code
This example shows you how to add custom attributes to the iframe tag or parameters to the src url (so you can add the autoplay parameter on youtube for example):
```php
if ($MediaObject = $this->MediaEmbed->parseUrl('http://www.youtube.com/watch?v=111111')) {
	$MediaObject->setParam([
		'autoplay' => 1,
		'loop' => 1
	]);
	$MediaObject->setAttribute([
		'type' => null,
		'class' => 'iframe-class',
		'data-html5-parameter' => true
	]);

	return $MediaObject->getEmbedCode();
}
```
This should return and embed code like:
```html
<embed src="http:/www.youtube.com/embed/111111?autoplay=1&amp;loop=1" class="iframe-class" data-html5-parameter></iframe>
```

### Example with BBCode

#### Parse video content upon save (db input)
```php
/**
 * @param mixed $string
 * @return string
 */
protected function _parseVideo($string) {
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
```

So `[video]http://www.youtube.com/v/123[/video]` becomes `[video=youtube]123[/video]`.

#### Display the resulting code snippet upon display
```php
/**
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
```

So `[video]123[/video]` becomes `<iframe ...>...</iframe>` or `<object ...><embed src="..."</embed></object>`.

### More examples
You can see live examples when you get this repo running locally and browse to `examples` dir.
`index.php` has a list of examples, you can live-preview. `bbcode.php` shows how to use it in save/read callbacks.

## Contribute / TODOs
See [wiki](https://github.com/dereuromark/MediaEmbed/wiki).

## Credits
Inspired by [autoembed](http://autoembed.com/) which already included most of the supported services and laid the foundation of this OOP approach here.
