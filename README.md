# MediaEmbed
A utility that generates HTML embed tags for audio or video located on a given URL.
It also parses and validates given media URLs.

It currently works with

## Requirements

- PHP 5.3+

- Composer

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

## API

## Usage

## Example with "type" and "id" saved in DB
A helper method
```php
public function video($type, $id, $options = array(), $params = array()) {
	if (!isset($this->MediaEmbed)) {
		$this->MediaEmbed = new MediaEmbed();
	}
	$MediaObject = $this->MediaEmbed->parseId(array('host' => $type, 'id' => $id));
	if (!$is) {
		return '';
	}
	return $MediaObject->getEmbedCode();
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

So `[video]123[/video]` becomes `<object ...><embed src="..."</embed></object>`.


## License

The MIT License.