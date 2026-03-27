# MediaEmbed

## API Overview

### Parsing
You can either use `parseUrl()` (default lookup) or `parseId()` (reverse lookup) of `MediaEmbed`.
The latter is useful if you only store the "host slug" and "id" in the database instead of the
complete URL.
Both methods will return a `MediaObject` object, which will contain the parsed input.

For stricter error handling, use the `*OrFail()` variants which throw exceptions instead of returning null:
- `parseUrlOrFail()` - throws `InvalidUrlException` or `FetchException`
- `parseIdOrFail()` - throws `InvalidUrlException` or `ProviderNotFoundException`

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
    $MediaObject = $this->MediaEmbed->parseUrl($url);
    if ($MediaObject) {
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
$MediaObject = $this->MediaEmbed->parseUrl($url);
if ($MediaObject) {
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
$MediaObject = $this->MediaEmbed->parseUrl('https://www.youtube.com/watch?v=111111');
if ($MediaObject) {
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
<embed src="https://www.youtube.com/embed/111111?autoplay=1&amp;loop=1" class="iframe-class" data-html5-parameter></iframe>
```

### Adding Custom Providers

You can add your own custom providers in several ways:

#### 1. Via Configuration Array

Pass custom providers through the constructor config:

```php
$customProviders = [
    [
        'name' => 'MyCustomService',
        'website' => 'https://custom.example.com',
        'url-match' => [
            'https?://(?:www\.)?custom\.example\.com/video/([0-9]+)',
        ],
        'embed-width' => '640',
        'embed-height' => '360',
        'iframe-player' => '//custom.example.com/embed/$2',
    ],
];

$MediaEmbed = new MediaEmbed(['custom_providers' => $customProviders]);

// Now you can parse URLs from your custom provider
$MediaObject = $MediaEmbed->parseUrl('https://custom.example.com/video/12345');
```

#### 2. Dynamically with addProviderConfig()

Add providers at runtime using the type-safe ProviderConfig DTO:

```php
use MediaEmbed\Provider\ProviderConfig;

$MediaEmbed = new MediaEmbed();

$customProvider = new ProviderConfig(
    name: 'AnotherService',
    website: 'https://another.example.com',
    urlMatch: ['https?://another\.example\.com/watch/([a-z0-9]+)'],
    embedWidth: 560,
    embedHeight: 315,
    iframePlayer: '//another.example.com/player/$2',
);

$MediaEmbed->addProviderConfig($customProvider);
```

#### 3. From a Configuration File

You can load providers from a PHP or JSON file:

**PHP File (custom-providers.php):**
```php
<?php
return [
    [
        'name' => 'FileBasedProvider',
        'website' => 'https://file.example.com',
        'url-match' => [
            'https?://file\.example\.com/v/([0-9]+)',
        ],
        'embed-width' => '640',
        'embed-height' => '360',
        'iframe-player' => '//file.example.com/embed/$2',
    ],
];
```

**JSON File (custom-providers.json):**
```json
[
    {
        "name": "JsonProvider",
        "website": "https://json.example.com",
        "url-match": [
            "https?://json\\.example\\.com/video/([0-9]+)"
        ],
        "embed-width": "640",
        "embed-height": "360",
        "iframe-player": "//json.example.com/embed/$2"
    }
]
```

**Usage:**
```php
$MediaEmbed = new MediaEmbed(['providers_config' => '/path/to/custom-providers.php']);
// or
$MediaEmbed = new MediaEmbed(['providers_config' => '/path/to/custom-providers.json']);
```

#### 4. Overriding Built-in Providers

By default, custom providers won't override existing ones. To override:

```php
use MediaEmbed\Provider\ProviderConfig;

$MediaEmbed = new MediaEmbed();

$customYouTube = new ProviderConfig(
    name: 'YouTube',
    website: 'https://www.youtube.com',
    urlMatch: ['https?://youtu\.be/([0-9a-z-_]{11})'],
    embedWidth: 800,  // Custom width
    embedHeight: 600, // Custom height
    iframePlayer: '//www.youtube.com/embed/$2?custom=param',
);

$MediaEmbed->addProviderConfig($customYouTube, override: true);
```

#### Provider Configuration Format

Using `ProviderConfig` DTO (recommended):

```php
$config = new ProviderConfig(
    name: 'MyProvider',           // Required: Display name
    website: 'https://...',       // Homepage URL
    urlMatch: ['regex...'],       // Required: URL patterns (array or string)
    embedWidth: 640,              // Required: Default width
    embedHeight: 360,             // Required: Default height
    iframePlayer: '//.../$2',     // Required: Iframe URL template
    slug: 'myprovider',           // Optional: Custom slug (auto-generated if omitted)
    imageSrc: '//.../$2.jpg',     // Optional: Thumbnail URL template
    supportsTimestamp: false,     // Optional: Timestamp support (like YouTube)
);
```

For array-based configs (legacy format):

- **name** (required): Display name of the provider
- **website**: Homepage URL of the service
- **url-match** (required): Array of regex patterns to match URLs
- **embed-width** (required): Default width in pixels
- **embed-height** (required): Default height in pixels
- **iframe-player** (required): URL template for iframe embedding
- **slug**: Optional custom slug (auto-generated from name if not provided)
- **image-src**: Optional thumbnail image URL template

**Note:** In regex patterns and templates, `$1` is the full matched URL, `$2` is the first capture group, `$3` is the second, etc.

## Advanced Usage

### Exception-Based Error Handling

Instead of checking for `null` returns, you can use the `*OrFail()` methods which throw typed exceptions:

```php
use MediaEmbed\MediaEmbed;
use MediaEmbed\Exception\InvalidUrlException;
use MediaEmbed\Exception\ProviderNotFoundException;

$MediaEmbed = new MediaEmbed();

try {
    $MediaObject = $MediaEmbed->parseUrlOrFail($url);
    echo $MediaObject->getEmbedCode();
} catch (InvalidUrlException $e) {
    echo "URL not supported: " . $e->getUrl();
}

try {
    $MediaObject = $MediaEmbed->parseIdOrFail($id, $host);
} catch (ProviderNotFoundException $e) {
    echo "Provider not found: " . $e->getProviderSlug();
}
```

### Using ProviderConfig DTO

For type-safe provider access, use the `ProviderConfig` class:

```php
use MediaEmbed\Provider\ProviderConfig;

// Get provider as typed object
$config = $MediaEmbed->getProvider('youtube');
if ($config !== null) {
    echo $config->name;           // "YouTube"
    echo $config->website;        // "https://www.youtube.com"
    echo $config->embedWidth;     // "480"

    if ($config->hasIframeSupport()) {
        echo $config->iframePlayer;
    }
}

// Or use the throwing variant
$config = $MediaEmbed->getProviderOrFail('youtube');
```

You can also add providers using the DTO:

```php
$config = new ProviderConfig(
    name: 'MyService',
    website: 'https://myservice.com',
    urlMatch: 'https://myservice\\.com/v/([a-z0-9]+)',
    embedWidth: '640',
    embedHeight: '360',
    iframePlayer: '//myservice.com/embed/$2',
);

$MediaEmbed->addProviderConfig($config);
```

### Custom HTTP Client

For testing or custom HTTP handling, inject your own HTTP client:

```php
use MediaEmbed\Http\HttpClientInterface;

class MockHttpClient implements HttpClientInterface {
    public function get(string $url, array $options = []): ?string {
        // Return mock content or null
        return '<html>...</html>';
    }
}

$MediaEmbed = new MediaEmbed([], null, new MockHttpClient());
```

### Provider Loaders

Load providers from different sources using the loader interface:

```php
use MediaEmbed\Provider\ArrayLoader;
use MediaEmbed\Provider\JsonFileLoader;
use MediaEmbed\Provider\PhpFileLoader;

// Load from PHP file (default)
$loader = new PhpFileLoader('/path/to/providers.php');
$MediaEmbed = new MediaEmbed([], null, null, $loader);

// Load from JSON file
$loader = new JsonFileLoader('/path/to/providers.json');
$MediaEmbed->loadProvidersFromLoader($loader);

// Load from array (useful for testing)
$loader = new ArrayLoader([
    ['name' => 'Test', 'website' => '...', 'url-match' => '...', 'embed-width' => '640', 'embed-height' => '360'],
]);
$MediaEmbed->loadProvidersFromLoader($loader, reset: true);
```

### Provider Collection

Get all providers as a filterable collection:

```php
use MediaEmbed\Provider\ProviderCollection;

$MediaEmbed = new MediaEmbed();

// Get all providers
$providers = $MediaEmbed->getProviders();
echo count($providers); // e.g., 137

// Filter to specific providers
$subset = $MediaEmbed->getProviders(['youtube', 'vimeo', 'dailymotion']);

// Filter by capabilities
$withIframe = $providers->withIframeSupport();
$withThumbnails = $providers->withThumbnailSupport();

// Iterate over providers
foreach ($providers as $slug => $config) {
    echo $config->name . ' (' . $slug . ')' . PHP_EOL;
}

// Chain filters
$filtered = $providers
    ->withIframeSupport()
    ->whitelist(['youtube', 'vimeo', 'twitch']);
```

### Caching

For better performance on repeated requests, use the cache support:

```php
use MediaEmbed\Cache\ArrayCache;
use MediaEmbed\Cache\CacheInterface;

// Using the built-in ArrayCache (in-memory, single request)
$cache = new ArrayCache();
$MediaEmbed = new MediaEmbed();
$matcher = $MediaEmbed->getUrlMatcher();
$matcher->setCache($cache);

// The domain index will be cached after first match
$MediaEmbed->parseUrl('https://youtube.com/watch?v=abc');
```

For persistent caching, implement `CacheInterface` with your preferred cache backend (Redis, Memcached, filesystem, etc.):

```php
use MediaEmbed\Cache\CacheInterface;
use Psr\SimpleCache\CacheInterface as Psr16Cache;

class Psr16Adapter implements CacheInterface {
    public function __construct(private Psr16Cache $cache) {}

    public function get(string $key, mixed $default = null): mixed {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool {
        return $this->cache->set($key, $value, $ttl);
    }

    public function delete(string $key): bool {
        return $this->cache->delete($key);
    }

    public function has(string $key): bool {
        return $this->cache->has($key);
    }
}

// Use with any PSR-16 cache
$cache = new Psr16Adapter($yourPsr16Cache);
$MediaEmbed->getUrlMatcher()->setCache($cache, ttl: 3600);
```

### oEmbed Discovery

For URLs not covered by built-in providers, use oEmbed auto-discovery:

```php
use MediaEmbed\OEmbed\OEmbedDiscovery;

$discovery = new OEmbedDiscovery();

// Auto-discover and fetch oEmbed data
$response = $discovery->discover('https://example.com/video/123');

if ($response !== null) {
    echo $response->title;
    echo $response->providerName;

    if ($response->hasHtml()) {
        echo $response->html; // Ready-to-use embed code
    }

    if ($response->hasThumbnail()) {
        echo $response->thumbnailUrl;
    }
}

// With size constraints
$response = $discovery->discover($url, maxWidth: 640, maxHeight: 480);

// Or fetch directly from a known endpoint
$response = $discovery->fetch('https://example.com/oembed?url=...');
```

The `OEmbedResponse` provides typed access to all oEmbed fields:

```php
$response->type;           // 'video', 'photo', 'link', or 'rich'
$response->version;        // oEmbed version (usually '1.0')
$response->title;          // Content title
$response->authorName;     // Author/creator name
$response->authorUrl;      // Author URL
$response->providerName;   // Provider name (e.g., 'YouTube')
$response->providerUrl;    // Provider homepage
$response->thumbnailUrl;   // Thumbnail image URL
$response->thumbnailWidth; // Thumbnail width
$response->thumbnailHeight;// Thumbnail height
$response->html;           // Embed HTML (for video/rich types)
$response->width;          // Embed width
$response->height;         // Embed height
$response->url;            // Source URL (for photo type)
$response->cacheAge;       // Suggested cache duration in seconds

// Type checks
$response->isVideo();
$response->isPhoto();
$response->isRich();
$response->hasHtml();
$response->hasThumbnail();
```

### Example with BBCode

#### Parse video content upon save (db input)
```php
/**
 * @param string $string
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
    $MediaObject = $this->MediaEmbed->parseUrl($url);
    if (!$MediaObject) {
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

So `[video]https://www.youtube.com/v/123[/video]` becomes `[video=youtube]123[/video]`.

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
    $MediaObject = $this->MediaEmbed->parseId($id, $host);
    if (!$MediaObject) {
        return $params[0];
    }

    return $MediaObject->getEmbedCode();
}
```

So `[video]123[/video]` becomes `<iframe ...>...</iframe>`.

### More examples
You can see live examples when you get this repo running locally and browse to `examples` dir.
`index.php` has a list of examples, you can live-preview. `bbcode.php` shows how to use it in save/read callbacks.


## Contribute / TODOs
See [wiki](https://github.com/dereuromark/media-embed/wiki).

Looking forward for contributions, e.g. adding more yet missing services etc.
Please provide a simple test URL and test case for any new service.

Run tests with
```
composer test
```

Run coverage
```
composer test-coverage
```
and browse to the generated `tmp/index.php` via browser.

Run PHPStan
```
composer stan-setup
composer stan
```

Run CS check/fix with
```
composer cs-check
composer cs-fix
```

Update list of services in `docs/supported.md` with
```
bin/generate-docs
```
