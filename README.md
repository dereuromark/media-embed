# MediaEmbed
[![CI](https://github.com/dereuromark/media-embed/workflows/CI/badge.svg)](https://github.com/dereuromark/media-embed/actions?query=workflow%3ACI+branch%3Amaster)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/media-embed/license.svg)](https://packagist.org/packages/dereuromark/media-embed)
[![Total Downloads](https://poser.pugx.org/dereuromark/media-embed/d/total.svg)](https://packagist.org/packages/dereuromark/media-embed)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A utility library that generates HTML embed tags for audio or video located on a given URL.
It also parses and validates given media URLs.

It currently works with [160+ services](docs/supported.md), including the most important ones like

- YouTube
- Dailymotion
- MyVideo
- Vimeo
- Ustream

etc. With community driven updates this aims to be a complete and up-to-date service wrapper lib.

It uses iframes if possible, and has a fallback on the embed object if necessary.

## Demo
https://sandbox.dereuromark.de/sandbox/media-embed

## Requirements

- [jbroadway/urlify](https://github.com/jbroadway/urlify) for slugging

### Note
Please feel free to join in and help out to further improve or complete it.
There are always some providers changing their URLs/API or some new ones which are not yet completed.

## Installation

Run
```bash
composer require dereuromark/media-embed
```
This will get the latest tagged version for you.

## Documentation
For detailed documentation see **[/docs](docs/README.md)**.

## Credits
Inspired by autoembed.com which already included most of the supported services and laid the foundation of this OOP approach here.
