<?php

namespace MediaEmbed\Test\Provider;

use MediaEmbed\Exception\ProviderConfigException;
use MediaEmbed\MediaEmbed;
use MediaEmbed\Provider\ArrayLoader;
use MediaEmbed\Provider\JsonFileLoader;
use MediaEmbed\Provider\PhpFileLoader;
use MediaEmbed\Provider\ProviderLoaderInterface;
use PHPUnit\Framework\TestCase;

class ProviderLoaderTest extends TestCase {

	public function testArrayLoaderImplementsInterface(): void {
		$loader = new ArrayLoader([]);

		$this->assertInstanceOf(ProviderLoaderInterface::class, $loader);
	}

	public function testArrayLoaderLoad(): void {
		$providers = [
			['name' => 'Test1', 'website' => 'https://test1.com', 'url-match' => 'test1', 'embed-width' => '100', 'embed-height' => '100', 'iframe-player' => '//test1.com/embed/$2'],
			['name' => 'Test2', 'website' => 'https://test2.com', 'url-match' => 'test2', 'embed-width' => '200', 'embed-height' => '200', 'iframe-player' => '//test2.com/embed/$2'],
		];

		$loader = new ArrayLoader($providers);

		$this->assertTrue($loader->canLoad());
		$this->assertSame($providers, $loader->load());
	}

	public function testMediaEmbedValidatesConstructorProviderLoader(): void {
		$loader = new ArrayLoader([
			[
				'name' => 'Broken',
				'website' => 'https://broken.example.com',
				'url-match' => 'broken\\.example\\.com/([0-9]+)',
				'embed-width' => 640,
				'embed-height' => 360,
			],
		]);

		$this->expectException(ProviderConfigException::class);
		$this->expectExceptionMessage('Provider configuration is missing required field: iframe-player');

		new MediaEmbed(providerLoader: $loader);
	}

	public function testMediaEmbedValidatesProviderLoaderMissingName(): void {
		$loader = new ArrayLoader([
			[
				'website' => 'https://broken.example.com',
				'url-match' => 'broken\\.example\\.com/([0-9]+)',
				'embed-width' => 640,
				'embed-height' => 360,
				'iframe-player' => '//broken.example.com/embed/$2',
			],
		]);

		$this->expectException(ProviderConfigException::class);
		$this->expectExceptionMessage('Provider configuration is missing required field: name');

		new MediaEmbed(providerLoader: $loader);
	}

	public function testMediaEmbedValidatesLoadedProviderLoader(): void {
		$mediaEmbed = new MediaEmbed();
		$loader = new ArrayLoader([
			[
				'name' => 'Broken',
				'website' => 'https://broken.example.com',
				'url-match' => 'broken\\.example\\.com/([0-9]+)',
				'embed-width' => 640,
				'embed-height' => 360,
			],
		]);

		$this->expectException(ProviderConfigException::class);
		$this->expectExceptionMessage('Provider configuration is missing required field: iframe-player');

		$mediaEmbed->loadProvidersFromLoader($loader);
	}

	public function testPhpFileLoaderImplementsInterface(): void {
		$loader = new PhpFileLoader('/nonexistent/path.php');

		$this->assertInstanceOf(ProviderLoaderInterface::class, $loader);
	}

	public function testPhpFileLoaderCannotLoadNonexistent(): void {
		$loader = new PhpFileLoader('/nonexistent/path.php');

		$this->assertFalse($loader->canLoad());
		$this->assertSame([], $loader->load());
	}

	public function testPhpFileLoaderLoadsDefaultStubs(): void {
		$stubsPath = dirname(__DIR__, 2) . '/data/stubs.php';
		$loader = new PhpFileLoader($stubsPath);

		$this->assertTrue($loader->canLoad());
		$this->assertSame($stubsPath, $loader->getPath());

		$providers = $loader->load();
		$this->assertIsArray($providers);
		$this->assertNotEmpty($providers);
	}

	public function testJsonFileLoaderImplementsInterface(): void {
		$loader = new JsonFileLoader('/nonexistent/path.json');

		$this->assertInstanceOf(ProviderLoaderInterface::class, $loader);
	}

	public function testJsonFileLoaderCannotLoadNonexistent(): void {
		$loader = new JsonFileLoader('/nonexistent/path.json');

		$this->assertFalse($loader->canLoad());
		$this->assertSame([], $loader->load());
	}

}
