# Upgrade Guide

## Upgrading from 0.7 to 1.0

Version 1.0 modernizes the codebase with PHP 8.1+ features, removes deprecated methods, and drops Flash/object embed support (iframe-only).

### Breaking Changes

#### Removed Methods

| 0.7 Method | 1.0 Replacement |
|------------|-----------------|
| `addProvider(array $data)` | `addProviderConfig(ProviderConfig::fromArray($data))` |
| `getHost(string $slug)` | `getProvider(string $slug)` |
| `getHostOrFail(string $slug)` | `getProviderOrFail(string $slug)` |
| `setHosts(array $stubs)` | Use constructor with `ProviderLoaderInterface` or `addProviderConfig()` |
| `object(string $slug)` | `parseId(string $id, string $host)` or `getProvider(string $slug)` |
| `MediaObject::setParam()` | `MediaObject::withParam()` |
| `MediaObject::setAttribute()` | `MediaObject::withAttribute()` |
| `MediaObject::setWidth()` | `MediaObject::withWidth()` |
| `MediaObject::setHeight()` | `MediaObject::withHeight()` |

`MediaObject` customization methods are now immutable. `withParam()`, `withAttribute()`, `withWidth()`, and `withHeight()` return a new changed instance and leave the original object unchanged, so callers must reassign the return value:

```php
$object = $object
    ->withParam('autoplay', 1)
    ->withAttribute('class', 'iframe-class');
```

#### Removed Flash Support

The `<object>` embed mode has been removed. All embeds now use `<iframe>` exclusively.

Removed from provider configuration:
- `embed-src` - Flash player URL
- `flashvars` - Flash variables

If you have custom providers using these keys, remove them.

#### Removed Dead Providers

The bundled provider list no longer includes providers whose public URLs or embed endpoints were no longer usable during the 1.0 cleanup:

- ClipFish
- ClipFish Search
- ClipFish Show
- Ustream

Existing stored embeds for those providers should be treated as unsupported by 1.0 unless applications register their own custom replacement provider.

#### Property Visibility Changes

| Property | Change |
|----------|--------|
| `MediaObject::$config` | `public` → `protected` |
| `MediaEmbed::$config` | `public` → `protected` |
| `MediaEmbed::object()` | `public` → `protected` |
| `MediaEmbed::setHosts()` | Removed (was `public`) |

#### Type Changes

- `ProviderConfig::$embedWidth` - Now accepts `int|string` (was `string`)
- `ProviderConfig::$embedHeight` - Now accepts `int|string` (was `string`)
- `UrlMatcher` cache arguments now accept `Psr\SimpleCache\CacheInterface`
- `MediaEmbed\Cache\CacheInterface` now extends PSR-16 and includes multi-key cache methods
- `MediaEmbed::__construct()` accepts optional `cache` and `cacheTtl` arguments
- `UrlMatcher` persistent cache keys include a provider-data hash instead of using one shared static key

The `fromArray()` method preserves legacy array dimension values as either `int` or `string`, including percentage dimensions such as `100%`.

- `ProviderConfig::$status` is now a `MediaEmbed\Provider\ProviderStatus` enum (was a `string` with `STATUS_*` class constants). `ProviderConfig::$category` is now a `MediaEmbed\Provider\ProviderCategory` enum (was a `string` with `CATEGORY_*` constants). Read the string with `->value`; the old `ProviderConfig::STATUS_*` / `CATEGORY_*` constants were removed. `ProviderCollection::withStatus()` / `withCategory()` accept either the enum or its string value. Array input/output (`fromArray()` / `toArray()` / `data/stubs.php`) still uses the plain strings.
- `MediaEmbed\Object\ObjectInterface` now declares the full public `MediaObject` surface (the `with*()` customizers, `getResponsiveEmbedCode()`, `getImageSrc()`, `image()`, `getParams()`, `getAttributes()`, `sourceUrl()`, `oEmbedEndpoint()`, `isSourceResolved()`, `website()`, `__toString()`). Custom implementations of the interface must provide these.

#### Dependencies

- `jbroadway/urlify` is no longer a hard dependency (moved to `suggest`). A built-in ASCII slugger is used when it is absent; inject a custom `MediaEmbed\Slugger\SluggerInterface` to override.
- `psr/http-client` and `psr/http-factory` are now required. A PSR-18 client can be injected via the new `psrHttpClient` + `requestFactory` constructor arguments; the bundled `StreamHttpClient` remains the default.

### Migration Examples

#### Adding Custom Providers

```php
// 0.7
$mediaEmbed->addProvider([
    'name' => 'MyProvider',
    'website' => 'https://example.com',
    'url-match' => 'example\\.com/v/([a-z0-9]+)',
    'embed-width' => '640',
    'embed-height' => '360',
    'iframe-player' => '//example.com/embed/$2',
]);

// 1.0
use MediaEmbed\Provider\ProviderConfig;

$mediaEmbed->addProviderConfig(ProviderConfig::fromArray([
    'name' => 'MyProvider',
    'website' => 'https://example.com',
    'url-match' => 'example\\.com/v/([a-z0-9]+)',
    'embed-width' => '640',
    'embed-height' => '360',
    'iframe-player' => '//example.com/embed/$2',
]));

// Or using the DTO directly
$mediaEmbed->addProviderConfig(new ProviderConfig(
    name: 'MyProvider',
    website: 'https://example.com',
    urlMatch: 'example\\.com/v/([a-z0-9]+)',
    embedWidth: 640,
    embedHeight: 360,
    iframePlayer: '//example.com/embed/$2',
));
```

#### Getting Provider Information

```php
// 0.7
$host = $mediaEmbed->getHost('youtube');
$host = $mediaEmbed->getHostOrFail('youtube');

// 1.0
$provider = $mediaEmbed->getProvider('youtube');        // Returns ProviderConfig|null
$provider = $mediaEmbed->getProviderOrFail('youtube');  // Throws ProviderNotFoundException
```

#### Creating MediaObject by Host

```php
// 0.7
$object = $mediaEmbed->object('youtube');

// 1.0 - Use parseId with the video ID
$object = $mediaEmbed->parseId('dQw4w9WgXcQ', 'youtube');

// Or get provider config directly
$provider = $mediaEmbed->getProvider('youtube');
```

#### Bulk Loading Providers

```php
// 0.7
$mediaEmbed->setHosts($providers, $reset = true);

// 1.0 - Use constructor with custom loader
use MediaEmbed\Provider\ArrayLoader;

$mediaEmbed = new MediaEmbed(
    providerLoader: new ArrayLoader($providers),
);

// Or load from file
use MediaEmbed\Provider\JsonFileLoader;

$mediaEmbed = new MediaEmbed(
    providerLoader: new JsonFileLoader('/path/to/providers.json'),
);
```

### New Features in 1.0

#### Exception Handling

New `*OrFail()` methods throw specific exceptions:

```php
use MediaEmbed\Exception\InvalidUrlException;
use MediaEmbed\Exception\ProviderNotFoundException;
use MediaEmbed\Exception\FetchException;

try {
    $media = $mediaEmbed->parseUrlOrFail($url);
} catch (InvalidUrlException $e) {
    // URL not supported by any provider
} catch (FetchException $e) {
    // Provider requires fetch-match but HTTP request failed
}
```

#### Dependency Injection

Inject custom HTTP client for testing or caching:

```php
use MediaEmbed\Http\HttpClientInterface;

class CachingHttpClient implements HttpClientInterface {
    public function get(string $url, array $options = []): ?string {
        // Your implementation
    }
}

$mediaEmbed = new MediaEmbed(
    httpClient: new CachingHttpClient(),
);
```

#### Provider DTOs

Type-safe provider configuration:

```php
$provider = $mediaEmbed->getProvider('youtube');

echo $provider->name;           // "YouTube"
echo $provider->website;        // "https://www.youtube.com"
echo $provider->status->value;  // "active" (ProviderStatus enum)
echo $provider->category->value; // "video" (ProviderCategory enum)
echo $provider->exampleUrl;     // Example URL used by release fixtures
echo $provider->embedWidth;     // 480
echo $provider->embedHeight;    // 295
echo $provider->iframePlayer;   // "//www.youtube.com/embed/$2"

// Check capabilities
$provider->hasIframeSupport();    // true
$provider->hasThumbnailSupport(); // true
$provider->requiresFetch();       // false
```

#### Provider Collections

Work with filtered provider lists:

```php
$providers = $mediaEmbed->getProviders();                    // All providers
$providers = $mediaEmbed->getProviders(['youtube', 'vimeo']); // Filtered

foreach ($providers as $provider) {
    echo $provider->name;
}

$providers->has('youtube');  // true
$providers->get('youtube');  // ProviderConfig
$providers->slugs();         // ['youtube', 'vimeo', ...]
$providers->withStatus('active');
$providers->withCategory('video');
```

#### URL Matching Helpers

Validate and classify URLs without creating a `MediaObject`:

```php
$mediaEmbed->supportsUrl($url);       // bool
$match = $mediaEmbed->matchUrl($url); // MatchResult|null
$provider = $mediaEmbed->getProviderForUrl($url); // ProviderConfig|null
```

#### Safer Default Iframe Attributes

Generated iframe embeds now include safer defaults:

- `title="{Provider} embed"`
- `loading="lazy"`
- `referrerpolicy="strict-origin-when-cross-origin"`
- `allow="fullscreen; picture-in-picture"`
- `frameborder="0"`
- `allowfullscreen`

`sandbox` remains opt-in because many third-party embeds need provider-specific permissions.

#### Hardened Fetching

The default `StreamHttpClient` rejects non-public local/private IP targets, validates redirects before following them, caps response size, and exposes timeout, redirect, size, and user-agent options.

#### oEmbed

oEmbed discovery supports JSON and XML responses, direct endpoint templates, response caching, and `OEmbedResponse::toArray()`.

#### Compound IDs for multi-segment providers

Providers whose embed needs more than one URL segment (e.g. Apple Podcasts, Deezer, Audiomack, Bandcamp, Mixcloud, Metatube, Odysee, PeerTube, Vooplayer) now return a composite `id()` (e.g. `playlist/1479458365`, `us/rimscast/1436041526`) instead of a single token. Store this full value if you persist `id()` for later `parseId()` reverse lookups. `parseId()` now returns `null` (and `parseIdOrFail()` throws) when a given ID cannot reconstruct a complete embed source, rather than emitting an iframe with unresolved placeholders.
