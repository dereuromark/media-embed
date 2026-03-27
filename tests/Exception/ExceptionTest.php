<?php

namespace MediaEmbed\Test\Exception;

use MediaEmbed\Exception\FetchException;
use MediaEmbed\Exception\InvalidUrlException;
use MediaEmbed\Exception\MediaEmbedException;
use MediaEmbed\Exception\ProviderConfigException;
use MediaEmbed\Exception\ProviderNotFoundException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase {

	public function testProviderNotFoundException(): void {
		$e = new ProviderNotFoundException('youtube');

		$this->assertInstanceOf(MediaEmbedException::class, $e);
		$this->assertSame('youtube', $e->getProviderSlug());
		$this->assertStringContainsString('youtube', $e->getMessage());
	}

	public function testInvalidUrlException(): void {
		$url = 'https://invalid.example.com/video';
		$e = new InvalidUrlException($url);

		$this->assertInstanceOf(MediaEmbedException::class, $e);
		$this->assertSame($url, $e->getUrl());
		$this->assertStringContainsString($url, $e->getMessage());
	}

	public function testInvalidUrlExceptionWithCustomMessage(): void {
		$e = new InvalidUrlException('', 'Custom error message');

		$this->assertSame('Custom error message', $e->getMessage());
	}

	public function testFetchException(): void {
		$url = 'https://example.com/fetch';
		$e = new FetchException($url);

		$this->assertInstanceOf(MediaEmbedException::class, $e);
		$this->assertSame($url, $e->getUrl());
		$this->assertStringContainsString($url, $e->getMessage());
	}

	public function testProviderConfigExceptionMissingField(): void {
		$config = ['website' => 'https://example.com'];
		$e = ProviderConfigException::missingField('name', $config);

		$this->assertInstanceOf(MediaEmbedException::class, $e);
		$this->assertSame($config, $e->getConfig());
		$this->assertStringContainsString('name', $e->getMessage());
	}

	public function testProviderConfigExceptionInvalidField(): void {
		$config = ['name' => 123];
		$e = ProviderConfigException::invalidField('name', 123, 'string', $config);

		$this->assertStringContainsString('name', $e->getMessage());
		$this->assertStringContainsString('string', $e->getMessage());
	}

}
