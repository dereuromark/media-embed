<?php

declare(strict_types=1);

namespace MediaEmbed\Test\Slugger;

use MediaEmbed\MediaEmbed;
use MediaEmbed\Provider\ProviderConfig;
use MediaEmbed\Slugger\SluggerInterface;
use MediaEmbed\Slugger\UrlifySlugger;
use PHPUnit\Framework\TestCase;

class SluggerTest extends TestCase {

	public function testUrlifySluggerProducesLowercaseDashedSlug(): void {
		$slugger = new UrlifySlugger();

		$this->assertSame('youtube', $slugger->slug('YouTube'));
		$this->assertSame('apple-podcasts', $slugger->slug('Apple Podcasts'));
		$this->assertSame('twitch-clip', $slugger->slug('Twitch Clip'));
	}

	public function testCustomSluggerIsUsedForProviderRegistration(): void {
		$slugger = new class implements SluggerInterface {

			public function slug(string $value): string {
				return 'fixed-slug';
			}

		};

		$mediaEmbed = new MediaEmbed(slugger: $slugger);
		$mediaEmbed->addProviderConfig(new ProviderConfig(
			name: 'Whatever',
			website: 'https://example.com',
			urlMatch: 'https://example\\.com/([0-9]+)',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//example.com/embed/$2',
		));

		$this->assertNotNull($mediaEmbed->getProvider('fixed-slug'));
	}

}
