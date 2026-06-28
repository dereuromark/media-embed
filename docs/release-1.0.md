# MediaEmbed 1.0 Release Notes Draft

MediaEmbed 1.0 modernizes the library for PHP 8.1+ and focuses on iframe-based embeds, provider data quality, safer defaults, and easier integration.

## Highlights

- Requires PHP 8.1+.
- Removes Flash/object embed support.
- Uses typed provider DTOs and filterable provider collections.
- Adds `parseUrlOrFail()` and `parseIdOrFail()` for exception-based error handling.
- Adds `supportsUrl()`, `matchUrl()`, and `getProviderForUrl()` for validation and classification without rendering.
- Adds provider metadata for lifecycle status, content category, release fixture URL, and provider notes.
- Adds release fixture coverage for every bundled provider.
- Adds safer default iframe attributes: `title`, `loading`, `referrerpolicy`, `allow`, `frameborder`, and `allowfullscreen`.
- Hardens the default HTTP client with public URL checks, redirect validation, response-size limits, timeout configuration, and user-agent configuration.
- Adds oEmbed JSON/XML discovery, direct endpoint templates, response caching, and `OEmbedResponse::toArray()`.
- Adds PSR-16 cache support for the URL matcher domain index with provider-data-specific cache keys.

## Provider Changes

- Bundled provider list currently contains 29 providers: 28 active and 1 legacy.
- Removed dead providers whose public URLs or embed endpoints were no longer usable:
  - ClipFish
  - ClipFish Search
  - ClipFish Show
  - Ustream
- Kept `bilibili-legacy` as legacy for existing `av` URLs.

## Upgrade Notes

Read `UPGRADE.md` before upgrading from 0.7. The main application-facing changes are removed legacy methods, iframe-only output, typed provider APIs, safer iframe defaults, and removed dead bundled providers.
