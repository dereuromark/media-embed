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

Use the matching helpers when you need to validate or classify a URL without creating an embed object:

```php
if ($MediaEmbed->supportsUrl($url)) {
    $match = $MediaEmbed->matchUrl($url);
    $provider = $MediaEmbed->getProviderForUrl($url);
}
```

### Output
You can then display the HTML code with `getEmbedCode()` or retrieve more information using the getters of `MediaObject`.

`getEmbedSrc()` returns the embed source value without HTML attribute escaping. Use `getEmbedSrcForHtml()` when placing the source into your own iframe `src` attribute. `getEmbedCode()` already uses the HTML-safe value internally.


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
            $MediaObject = $MediaObject->withAttribute($attribute, $value);
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
    $MediaObject = $MediaObject->withParam([
        'autoplay' => 1,
        'loop' => 1
    ])
        ->withAttribute([
            'type' => null,
            'class' => 'iframe-class',
            'data-html5-parameter' => true
        ]);

    return $MediaObject->getEmbedCode();
}
```
This should return an embed code like:
```html
<iframe src="//www.youtube.com/embed/111111?wmode=transparent&amp;autoplay=1&amp;loop=1" title="YouTube embed" width="480" height="295" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="fullscreen; picture-in-picture" frameborder="0" allowfullscreen class="iframe-class" data-html5-parameter></iframe>
```

Attribute names must be valid iframe attribute names. Whitespace/control characters, HTML syntax characters, and `on*` event-handler attributes are rejected.
Default iframe attributes include `title`, `loading="lazy"`, `referrerpolicy="strict-origin-when-cross-origin"`, `allow="fullscreen; picture-in-picture"`, `frameborder="0"`, and `allowfullscreen`. Override any default with `withAttribute()`, or remove it by setting the attribute value to `null` or `false`.
`sandbox` is not enabled by default because most third-party embeds need provider-specific permissions. Add it explicitly when your target provider supports the restrictions you choose.

Provider-specific iframe params can also be configured once when constructing `MediaEmbed`. This is useful for providers such as Twitch that require a domain-specific `parent` query parameter:

```php
$MediaEmbed = new MediaEmbed([
    'provider_params' => [
        'twitch-video' => [
            'parent' => 'example.com',
        ],
        'twitch-clip' => [
            'parent' => 'example.com',
        ],
    ],
]);
```

### Privacy mode (no-cookie embeds)

Pass `'privacy' => true` to opt into privacy-friendly embeds for providers that support them. YouTube switches to the `youtube-nocookie.com` host and Vimeo adds the `dnt=1` (do-not-track) parameter:

```php
$MediaObject = $this->MediaEmbed->parseUrl('https://www.youtube.com/watch?v=111111', ['privacy' => true]);
// src => //www.youtube-nocookie.com/embed/111111?wmode=transparent
```

You can also enable it globally for every embed via the constructor:

```php
$MediaEmbed = new MediaEmbed(['privacy' => true]);
```

Providers declare their privacy variant via the optional `privacy-player` (alternate URL template) and `privacy-params` (extra query params) stub keys, so custom providers can opt in too.

### Responsive embeds

`getResponsiveEmbedCode()` wraps the iframe in a fluid aspect-ratio container so it scales with its parent width. The default ratio is `16:9`; pass any `width:height` ratio:

```php
$MediaObject = $this->MediaEmbed->parseUrl('https://www.youtube.com/watch?v=111111');
echo $MediaObject->getResponsiveEmbedCode();      // 16:9
echo $MediaObject->getResponsiveEmbedCode('4:3'); // custom ratio
```

This returns the iframe wrapped in a positioned container:

```html
<div style="position:relative;width:100%;height:0;padding-bottom:56.25%;overflow:hidden;"><iframe src="..." ... style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"></iframe></div>
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
    status: 'active',             // Optional: active, legacy, deprecated
    category: 'video',            // Optional: video, audio, social, streaming, 3d
    exampleUrl: 'https://...',    // Optional: fixture/example URL
    notes: '...',                 // Optional: generated docs note
    slug: 'myprovider',           // Optional: Custom slug (auto-generated if omitted)
    imageSrc: '//.../$2.jpg',     // Optional: Thumbnail URL template
    supportsTimestamp: false,     // Optional: Timestamp support (like YouTube)
    timestampParam: 'start',      // Optional: Embed query parameter for timestamps
    iframeParams: [               // Optional: Default iframe query parameters
        'parent' => 'example.com',
    ],
);
```

For array-based configs (legacy format):

- **name** (required): Display name of the provider
- **website**: Homepage URL of the service
- **url-match** (required): Array of regex patterns to match URLs
- **embed-width** (required): Default width in pixels or as percentage
- **embed-height** (required): Default height in pixels or as percentage
- **iframe-player** (required): URL template for iframe embedding
- **status**: Optional provider lifecycle status (`active`, `legacy`, `deprecated`)
- **category**: Optional content category (`video`, `audio`, `social`, `streaming`, `3d`)
- **example-url**: Optional example URL covered by release fixture tests
- **notes**: Optional provider note shown in generated supported-provider docs
- **slug**: Optional custom slug (auto-generated from name if not provided)
- **image-src**: Optional thumbnail image URL template
- **id**: Optional custom ID template
- **fetch-match**: Optional regex for secondary HTTP lookup
- **supports-timestamp**: Optional timestamp support flag
- **timestamp-param**: Optional embed query parameter for timestamp values
- **iframe-params**: Optional default iframe query parameters

**Note:** In regex patterns and templates, `$1` is the full matched URL, `$2` is the first capture group, `$3` is the second, etc.

Provider templates must use valid `$1`, `$2`, ... placeholders and absolute HTTP(S) URLs. Protocol-relative URLs such as `//www.youtube.com/embed/$2` are supported for iframe and image templates. Run `composer validate-providers` after changing provider data to catch invalid regex patterns, unsafe URL schemes, relative template URLs, duplicate slugs, and malformed placeholders.

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
    echo $config->status;         // "active"
    echo $config->category;       // "video"
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
    embedWidth: 640,
    embedHeight: 360,
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

The default `StreamHttpClient` only fetches public `http` and `https` URLs, rejects localhost/private literal IP targets, caps response size, and validates each redirect target before following it. You can configure timeout, maximum response size, redirect limit, and user agent:

```php
use MediaEmbed\Http\StreamHttpClient;

$client = new StreamHttpClient(
    timeout: 5,
    maxBytes: 1048576,
    maxRedirects: 3,
    userAgent: 'my-app/media-embed',
);

$MediaEmbed = new MediaEmbed(httpClient: $client);
```

Per-request HTTP client options can also include `timeout`, `max_bytes`, `max_redirects`, and `user_agent`. Inject a custom `HttpClientInterface` implementation if you need a different network policy.

#### PSR-18 client

You can plug in any PSR-18 client (Guzzle, Symfony HttpClient, ...) together with a PSR-17 request factory instead of implementing `HttpClientInterface`. The bundled `StreamHttpClient` stays the default when none is given:

```php
$MediaEmbed = new MediaEmbed(
    psrHttpClient: $psr18Client,
    requestFactory: $psr17RequestFactory,
);
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
echo count($providers); // number of bundled providers

// Filter to specific providers
$subset = $MediaEmbed->getProviders(['youtube', 'vimeo', 'dailymotion']);

// Filter by capabilities
$withIframe = $providers->withIframeSupport();
$withThumbnails = $providers->withThumbnailSupport();
$active = $providers->withStatus('active');
$video = $providers->withCategory('video');

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

// The built-in ArrayCache is in-memory and lasts for the current request only.
$cache = new ArrayCache();
$MediaEmbed = new MediaEmbed();
$MediaEmbed->setCache($cache);

// The domain index will be cached after first match
$MediaEmbed->parseUrl('https://youtube.com/watch?v=abc');
```

For persistent caching, pass any PSR-16 cache implementation (Redis, Memcached, filesystem, etc.):

```php
use Psr\SimpleCache\CacheInterface;

/** @var CacheInterface $yourPsr16Cache */
$MediaEmbed = new MediaEmbed(cache: $yourPsr16Cache, cacheTtl: 3600);
```

The cache stores the generated provider domain index under an internal key. It is invalidated automatically when providers change. The `cacheTtl` argument controls how long persistent caches may retain the index. The built-in `ArrayCache` honors TTL values and can store `null` values.
The internal key includes a hash of the provider definitions, so persistent caches do not reuse stale indexes after provider data changes.

### Thumbnails and oEmbed for bundled providers

Many bundled providers register an official oEmbed endpoint (YouTube, Vimeo, SoundCloud, Spotify, TikTok, TED, Sketchfab, Audiomack, Spreaker, Mixcloud). For a parsed object you can fetch its oEmbed data or a thumbnail directly:

```php
$mediaObject = $mediaEmbed->parseUrl('https://vimeo.com/channels/staffpicks/99585787');

$response = $mediaEmbed->oEmbed($mediaObject); // OEmbedResponse|null
echo $response?->title;

// Thumbnail: prefers the static image-src (e.g. YouTube/Dailymotion), then falls back
// to the provider's oEmbed thumbnail_url.
$thumb = $mediaEmbed->thumbnail($mediaObject); // string|null
```

`oEmbed()` returns null when the provider has no registered endpoint or the object was created via `parseId()` (no source URL). These calls perform an HTTP request, so a PSR-16 cache (and the injected HTTP client) apply.

### oEmbed Discovery

For URLs not covered by built-in providers, use oEmbed auto-discovery:

```php
use MediaEmbed\Cache\ArrayCache;
use MediaEmbed\OEmbed\OEmbedDiscovery;

$discovery = new OEmbedDiscovery(cache: new ArrayCache());

// Auto-discover and fetch oEmbed data
$response = $discovery->discover('https://example.com/video/123');

if ($response !== null) {
    echo $response->title;
    echo $response->providerName;

    if ($response->hasHtml()) {
        echo $response->html; // Raw remote provider HTML
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

If you already know an oEmbed endpoint, configure a direct endpoint template and avoid HTML discovery:

```php
$discovery = new OEmbedDiscovery(
    endpoints: [
        'video.example.com' => 'https://example.com/oembed?url={url}',
    ],
);

$response = $discovery->discover('https://video.example.com/watch/123');
```

Discovered oEmbed endpoint URLs may be absolute, protocol-relative, root-relative, or path-relative. Relative endpoint URLs are resolved against the source page URL before they are fetched.
Only public `http` and `https` endpoint URLs are fetched; local/private IP endpoints and unsafe schemes are rejected.
JSON and XML oEmbed responses are supported. When a PSR-16 cache is provided, discovered endpoints are cached with the configured `cacheTtl`, and fetched responses are cached using `cache_age` when the provider sends it.
The returned `html` field is raw remote provider HTML. Only render it for providers you trust, or sanitize it first.

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

$response->toArray();      // oEmbed array shape
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

Provider change checklist:

- Add or update `tests/Fixture/provider_urls.php` with at least one fixture URL per bundled provider.
- Include expected slug, parsed ID, and final embed source for each provider fixture.
- Include a mocked `response` entry in the fixture for providers using `fetch-match`.
- Set provider `status`, `category`, and `example-url` metadata.
- Keep `testDefaultProviderPatternsHaveFixtureCoverage()` green for pattern-level coverage.
- Add a mocked HTTP test for providers using `fetch-match`.
- Run `composer validate-providers` after editing provider data.
- Run `bin/generate-docs` and commit any `docs/supported.md` changes.
- Confirm generated iframe URLs still match the provider's current embed documentation.

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

Validate provider configuration data with
```
composer validate-providers
```
