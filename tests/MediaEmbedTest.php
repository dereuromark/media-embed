<?php

namespace MediaEmbed\Test;

use MediaEmbed\MediaEmbed;
use MediaEmbed\Object\MediaObject;
use MediaEmbed\Provider\ProviderConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test MediaEmbed
 */
class MediaEmbedTest extends TestCase {

	/**
	 * @var array
	 */
	protected static array $_stubs = [
		'https://www.dailymotion.com/video/x2bqyl6_l-entourloop-ft-ruffian-rugged-madder-than-dat_music' => 'x2bqyl6',
		'https://dai.ly/x2bqyl6' => 'x2bqyl6',
		'http://www.youtube.com/watch?v=yiSjHJnc9CY&feature=feedrec_grec_index' => 'yiSjHJnc9CY',
		'https://m.youtube.com/watch?v=yWm4YwqO93I' => 'yWm4YwqO93I',
		'https://www.youtube.com/embed/yWm4YwqO93I?rel=0' => 'yWm4YwqO93I',
		'http://youtu.be/MKlq4gQKtU0' => 'MKlq4gQKtU0',
		'https://www.facebook.com/mega90er/videos/1309058692443747/' => '1309058692443747',
		'https://www.facebook.com/diginights.HN/videos/1231155290281511/' => '1231155290281511',
		'https://www.facebook.com/SkySports/videos/vb.10911153761/10153310275743762/?type=2&theater' => '10153310275743762',
		'https://www.facebook.com/demotivateurFood/videos/vl.184872862011827/1034411179983244/?type=1' => '1034411179983244',
		'http://vimeo.com/19570639' => '19570639',
		'http://vimeo.com/245928033/572c32a20d' => '245928033/572c32a20d',
		'http://vimeo.com/channels/staffpicks/99585787' => '99585787',
		'https://player.vimeo.com/video/19570639' => '19570639',
		'http://www.clipfish.de/special/dsds/video/3507980/dsds-recall-anna-und-tobias-harmonieren/' => '3507980',
		'http://www.clipfish.de/special/kino-trailer/video/3495650/serengeti-filmausschnitt-gepardenkinder-und-die-jagd-der-mutter/' => '3495650',
		'http://www.clipfish.de/musikvideos/video/3486922/nicole-scherzinger-poison/' => '3486922',
		'http://www.youtube.com/watch?v=-vGzem8glbE&feature=channel' => '-vGzem8glbE',
		'http://www.aparat.com/v/sSLMC' => 'sSLMC',
		'http://www.metatube.com/en/videos/245145/J-Alvarez-Tu-Cuerpo-Pide-Fiesta/' => '245145/J-Alvarez-Tu-Cuerpo-Pide-Fiesta',
		// Fetch lookup required
		//'https://www.screencast.com/t/Hh4ulI0M' => '1d44810a-01f4-4c60-a862-6d114bed50c7',
		// Not available anymore
		//'https://www.ustream.tv/channel/america2oficial' => '17916695',
		//'https://www.ustream.tv/channel/16962149' => '16962149',
		'http://example.wistia.com/medias/1voyrefhy9' => '1voyrefhy9',
		'http://rutube.ru/video/c1b3c6c6ee77def7a8e54553c1fabbb8/' => 'c1b3c6c6ee77def7a8e54553c1fabbb8',
		// Not available anymore
		//'http://video.google.com/videoplay?docid=-5767589436465272649#' => '-5767589436465272649',
		'https://my.matterport.com/show/?m=Zh14WDtkjdC&lp=1' => 'Zh14WDtkjdC',
		'https://www.twitch.tv/videos/293684811' => '293684811',
		'https://clips.twitch.tv/WonderfulPiliableSquirrelBleedPurple' => 'WonderfulPiliableSquirrelBleedPurple',
		'https://lds.cdn.vooplayer.com/publish/MTEwNTMw' => 'MTEwNTMw',
		'https://soundcloud.com/kalax/kalax-take-me-back-feat-world-wild-1' => 'kalax/kalax-take-me-back-feat-world-wild-1',
		'https://www.mixcloud.com/spartacus/party-time/' => 'spartacus/party-time',
		'https://mixcloud.com/NTSRadio/boiler-room-dekmantel-2014/' => 'NTSRadio/boiler-room-dekmantel-2014',
		'https://www.loom.com/share/bdb8f2009224416ca642a50296430b8f' => 'bdb8f2009224416ca642a50296430b8f',
		'https://www.loom.com/embed/bdb8f2009224416ca642a50296430b8f?referrer=https%3A%2F%2Fwww.loom.com%2Fuse-cases%2Fengineering' => 'bdb8f2009224416ca642a50296430b8f',
		'https://www.loom.com/embed/bdb8f2009224416ca642a50296430b8f' => 'bdb8f2009224416ca642a50296430b8f',
		// YouTube live URLs
		'https://www.youtube.com/live/_L3nFAGwXdQ' => '_L3nFAGwXdQ',
		'https://www.youtube.com/live/_L3nFAGwXdQ?si=8LzqZPR1EHqULhg7&t=6372' => '_L3nFAGwXdQ',
		'https://youtube.com/live/dQw4w9WgXcQ' => 'dQw4w9WgXcQ',
		// TikTok
		'https://www.tiktok.com/@username/video/7123456789012345678' => '7123456789012345678',
		'https://tiktok.com/@user/video/7123456789012345678' => '7123456789012345678',
		// Instagram
		'https://www.instagram.com/p/ABC123xyz/' => 'ABC123xyz',
		'https://instagram.com/reel/XYZ789abc/' => 'XYZ789abc',
		'https://www.instagram.com/tv/DEF456ghi/' => 'DEF456ghi',
		// Twitter/X
		'https://twitter.com/user/status/1234567890123456789' => '1234567890123456789',
		'https://x.com/user/status/1234567890123456789' => '1234567890123456789',
		'https://www.twitter.com/username/status/9876543210987654321' => '9876543210987654321',
		// Reddit
		'https://www.reddit.com/r/videos/comments/abc123/some_title/' => 'abc123',
		'https://reddit.com/r/funny/comments/xyz789/another_post/' => 'xyz789',
		// Spotify
		'https://open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh' => '4iV5W9uYEdYUVa79Axb7Rh',
		'https://open.spotify.com/album/1DFixLWuPkv3KT3TnV35m3' => '1DFixLWuPkv3KT3TnV35m3',
		'https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M' => '37i9dQZF1DXcBWIGoYBM5M',
		// Streamable
		'https://streamable.com/abc123' => 'abc123',
		'https://www.streamable.com/xyz789' => 'xyz789',
		'https://streamable.com/e/def456' => 'def456',
		// Bilibili
		'https://www.bilibili.com/video/BV1xx411c7mD' => 'BV1xx411c7mD',
		'https://bilibili.com/video/BV1Ab4y1a7XY' => 'BV1Ab4y1a7XY',
		// Bilibili (Legacy av format)
		'https://www.bilibili.com/video/av12345' => '12345',
		// Rumble
		'https://rumble.com/v1abc12-example-video.html' => 'v1abc12',
		'https://rumble.com/embed/v1xyz99' => 'v1xyz99',
		// Odysee
		'https://odysee.com/$/embed/video-title/abc123def' => 'abc123def',
		// Kick
		'https://kick.com/username/clips/clip_abc123' => 'clip_abc123',
		'https://kick.com/video/12345-abcd-6789' => '12345-abcd-6789',
		// Bandcamp
		'https://artist.bandcamp.com/track/song-title' => 'artist/song-title',
		'https://someband.bandcamp.com/album/album-name' => 'someband/album-name',
		// PeerTube
		'https://peertube.example.org/w/abc123XYZ' => 'abc123XYZ',
		'https://video.instance.com/videos/watch/def456789' => 'def456789',
	];

	/**
	 * Test getting a provider configuration.
	 *
	 * @return void
	 */
	public function testGetProvider(): void {
		$MediaEmbed = new MediaEmbed();
		$provider = $MediaEmbed->getProvider('youtube');
		$this->assertNotNull($provider);
		$this->assertSame('YouTube', $provider->name);
		$this->assertSame('https://www.youtube.com', $provider->website);
	}

	/**
	 * MediaEmbedTest::testParseUrl()
	 *
	 * @return void
	 */
	public function testParseUrlInvalid(): void {
		$MediaEmbed = new MediaEmbed();
		$result = $MediaEmbed->parseUrl('http://www.youtube.com/foobar');
		$this->assertNull($result);
	}

	/**
	 * @dataProvider getUrls
	 * @param string $url
	 * @param string $id
	 * @return void
	 */
	#[DataProvider('getUrls')]
	public function testParseUrl(string $url, string $id): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl($url);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$result = $Object->id();
		$this->assertSame($id, $result, 'Invalid ID ' . $result . ' for ' . $url);
	}

	/**
	 * Data provider for stub URLs.
	 *
	 * @return array
	 */
	public static function getUrls(): array {
		$urls = [];
		foreach (static::$_stubs as $k => $v) {
			$urls[] = [$k, $v];
		}

		return $urls;
	}

	/**
	 * Test parseId()
	 *
	 * @return void
	 */
	public function testParseId(): void {
		$test = [
			'dailymotion' => 'x2bqyl6',
			'youtube' => 'yiSjHJnc9CY',
			'matterport' => 'Zh14WDtkjdC',
		];

		$MediaEmbed = new MediaEmbed();
		foreach ($test as $host => $id) {
			$Object = $MediaEmbed->parseId($id, $host);
			$this->assertInstanceOf(MediaObject::class, $Object);

			$is = $Object->getEmbedCode();
			$this->assertTrue(!empty($is));
		}
	}

	/**
	 * MediaEmbedTest::testYoutube()
	 *
	 * @return void
	 */
	public function testYoutube(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('http://www.youtube.com/watch?v=h9Pu4bZqWyg');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$id = $Object->id();
		$this->assertSame('h9Pu4bZqWyg', $id);

		$icon = $Object->icon();
		$this->assertNotEmpty($icon);

		$location = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$filename = $Object->saveIcon($location, $icon);
		$this->assertSame('youtube.png', $filename);

		$img = $Object->image();
		$this->assertSame('//img.youtube.com/vi/h9Pu4bZqWyg/0.jpg', $img);

		$code = $Object->getEmbedCode();
		$this->assertStringContainsString('<iframe', $code);

		$src = $Object->getEmbedSrc();
		$this->assertStringContainsString('//www.youtube.com/embed/h9Pu4bZqWyg', $src);
	}

	/**
	 * Test YouTube /live/ URL format
	 *
	 * @return void
	 */
	public function testYoutubeLive(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/live/_L3nFAGwXdQ');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$id = $Object->id();
		$this->assertSame('_L3nFAGwXdQ', $id);

		$code = $Object->getEmbedCode();
		$this->assertStringContainsString('<iframe', $code);
		$this->assertStringContainsString('_L3nFAGwXdQ', $code);
	}

	/**
	 * Test YouTube /live/ URL with timestamp and other parameters
	 *
	 * @return void
	 */
	public function testYoutubeLiveWithTimestamp(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/live/_L3nFAGwXdQ?si=8LzqZPR1EHqULhg7&t=6372');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$id = $Object->id();
		$this->assertSame('_L3nFAGwXdQ', $id);

		$code = $Object->getEmbedCode();
		$this->assertStringContainsString('<iframe', $code);
		// Verify timestamp is included as start parameter
		$this->assertStringContainsString('start=6372', $code);
	}

	/**
	 * Test YouTube watch URL with timestamp parameter
	 *
	 * @return void
	 */
	public function testYoutubeWatchWithTimestamp(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=NLIbe47YWiQ&t=3724s');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$id = $Object->id();
		$this->assertSame('NLIbe47YWiQ', $id);

		$code = $Object->getEmbedCode();
		$this->assertStringContainsString('<iframe', $code);
		// Verify timestamp is included as start parameter (with 's' suffix removed)
		$this->assertStringContainsString('start=3724', $code);
	}

	/**
	 * Test YouTube short URL with timestamp
	 *
	 * @return void
	 */
	public function testYoutubeShortUrlWithTimestamp(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://youtu.be/dQw4w9WgXcQ?t=42');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$id = $Object->id();
		$this->assertSame('dQw4w9WgXcQ', $id);

		$code = $Object->getEmbedCode();
		$this->assertStringContainsString('<iframe', $code);
		// Verify timestamp is included
		$this->assertStringContainsString('start=42', $code);
	}

	/**
	 * @return void
	 */
	public function testDailymotion(): void {
		$MediaEmbed = new MediaEmbed();

		$url = 'https://www.dailymotion.com/video/xgv8nw_david-guetta-who-s-that-chick_music#hp-sc-p-1';
		$Object = $MediaEmbed->parseUrl($url);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$img = $Object->image();
		$this->assertSame('https://www.dailymotion.com/thumbnail/160x120/video/xgv8nw', $img);

		$url = 'https://www.dailymotion.com/video/x6x13ln';
		$Object = $MediaEmbed->parseUrl($url);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$img = $Object->image();
		$this->assertSame('https://www.dailymotion.com/thumbnail/160x120/video/x6x13ln', $img);

		$url = 'https://dai.ly/x6x039x';
		$Object = $MediaEmbed->parseUrl($url);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$img = $Object->image();
		$this->assertSame('https://www.dailymotion.com/thumbnail/160x120/video/x6x039x', $img);
	}

	/**
	 * @return void
	 */
	public function testMatterport(): void {
		$mediaEmbed = new MediaEmbed();

		$url = 'https://my.matterport.com/show/?m=Zh14WDtkjdC&st=2000';
		$Object = $mediaEmbed->parseUrl($url);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$id = $Object->id();
		$this->assertSame('Zh14WDtkjdC', $id);

		$code = $Object->getEmbedCode();
		$this->assertStringContainsString('<iframe', $code);
	}

	/**
	 * Test getHosts()
	 *
	 * @return void
	 */
	public function testGetHosts(): void {
		$MediaEmbed = new MediaEmbed();

		$hosts = $MediaEmbed->getHosts();
		$this->assertTrue(count($hosts) > 30);

		$hosts = $MediaEmbed->getHosts(['vimeo', 'youtube']);
		$this->assertTrue(count($hosts) === 2);
	}

	/**
	 * Test addProviderConfig() method
	 *
	 * @return void
	 */
	public function testAddProviderConfig(): void {
		$MediaEmbed = new MediaEmbed();

		$customProvider = new ProviderConfig(
			name: 'CustomProvider',
			website: 'https://custom.example.com',
			urlMatch: ['https?://(?:www\.)?custom\.example\.com/video/([0-9]+)'],
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//custom.example.com/embed/$2',
		);

		$MediaEmbed->addProviderConfig($customProvider);

		$provider = $MediaEmbed->getProvider('customprovider');
		$this->assertNotNull($provider);
		$this->assertSame('CustomProvider', $provider->name);
		$this->assertSame('https://custom.example.com', $provider->website);

		// Test parsing a URL with the custom provider
		$Object = $MediaEmbed->parseUrl('https://custom.example.com/video/12345');
		$this->assertInstanceOf(MediaObject::class, $Object);
		$this->assertSame('12345', $Object->id());
	}

	/**
	 * Test custom_providers config option
	 *
	 * @return void
	 */
	public function testCustomProvidersConfig(): void {
		$customProviders = [
			[
				'name' => 'TestProvider1',
				'website' => 'https://test1.example.com',
				'url-match' => [
					'https?://test1\.example\.com/v/([a-z0-9]+)',
				],
				'embed-src' => '',
				'embed-width' => '500',
				'embed-height' => '300',
				'iframe-player' => '//test1.example.com/embed/$2',
			],
			[
				'name' => 'TestProvider2',
				'website' => 'https://test2.example.com',
				'url-match' => [
					'https?://test2\.example\.com/watch/([0-9]+)',
				],
				'embed-src' => '',
				'embed-width' => '600',
				'embed-height' => '400',
				'iframe-player' => '//test2.example.com/player/$2',
			],
		];

		$MediaEmbed = new MediaEmbed(['custom_providers' => $customProviders]);

		$provider1 = $MediaEmbed->getProvider('testprovider1');
		$this->assertNotNull($provider1);
		$this->assertSame('TestProvider1', $provider1->name);

		$provider2 = $MediaEmbed->getProvider('testprovider2');
		$this->assertNotNull($provider2);
		$this->assertSame('TestProvider2', $provider2->name);

		// Test parsing URLs
		$Object1 = $MediaEmbed->parseUrl('https://test1.example.com/v/abc123');
		$this->assertInstanceOf(MediaObject::class, $Object1);
		$this->assertSame('abc123', $Object1->id());

		$Object2 = $MediaEmbed->parseUrl('https://test2.example.com/watch/98765');
		$this->assertInstanceOf(MediaObject::class, $Object2);
		$this->assertSame('98765', $Object2->id());
	}

	/**
	 * Test provider override functionality
	 *
	 * @return void
	 */
	public function testProviderOverride(): void {
		$MediaEmbed = new MediaEmbed();

		// Try to add without override - should not replace existing
		$customYouTube = new ProviderConfig(
			name: 'YouTube',
			website: 'https://custom-youtube.example.com',
			urlMatch: ['https?://custom-youtube\.example\.com/watch/([0-9]+)'],
			embedWidth: 800,
			embedHeight: 600,
			iframePlayer: '//custom-youtube.example.com/embed/$2',
		);

		$MediaEmbed->addProviderConfig($customYouTube, false);
		$provider = $MediaEmbed->getProvider('youtube');
		$this->assertSame('https://www.youtube.com', $provider->website); // Should still be original

		// Now with override
		$MediaEmbed->addProviderConfig($customYouTube, true);
		$provider = $MediaEmbed->getProvider('youtube');
		$this->assertSame('https://custom-youtube.example.com', $provider->website); // Should be overridden
	}

	/**
	 * Test loadProvidersFromFile() with PHP file
	 *
	 * @return void
	 */
	public function testLoadProvidersFromPhpFile(): void {
		$tempFile = sys_get_temp_dir() . '/test_providers.php';
		$providers = [
			[
				'name' => 'FileProvider',
				'website' => 'https://file.example.com',
				'url-match' => [
					'https?://file\.example\.com/video/([0-9]+)',
				],
				'embed-src' => '',
				'embed-width' => '700',
				'embed-height' => '400',
				'iframe-player' => '//file.example.com/embed/$2',
			],
		];

		file_put_contents($tempFile, '<?php return ' . var_export($providers, true) . ';');

		$MediaEmbed = new MediaEmbed(['providers_config' => $tempFile]);

		$provider = $MediaEmbed->getProvider('fileprovider');
		$this->assertNotNull($provider);
		$this->assertSame('FileProvider', $provider->name);

		unlink($tempFile);
	}

	/**
	 * Test loadProvidersFromFile() with JSON file
	 *
	 * @return void
	 */
	public function testLoadProvidersFromJsonFile(): void {
		$tempFile = sys_get_temp_dir() . '/test_providers.json';
		$providers = [
			[
				'name' => 'JsonProvider',
				'website' => 'https://json.example.com',
				'url-match' => [
					'https?://json\.example\.com/video/([0-9]+)',
				],
				'embed-src' => '',
				'embed-width' => '800',
				'embed-height' => '450',
				'iframe-player' => '//json.example.com/embed/$2',
			],
		];

		file_put_contents($tempFile, json_encode($providers));

		$MediaEmbed = new MediaEmbed(['providers_config' => $tempFile]);

		$provider = $MediaEmbed->getProvider('jsonprovider');
		$this->assertNotNull($provider);
		$this->assertSame('JsonProvider', $provider->name);

		unlink($tempFile);
	}

	/**
	 * Test getProviders() returning ProviderCollection
	 *
	 * @return void
	 */
	public function testGetProviders(): void {
		$MediaEmbed = new MediaEmbed();

		$providers = $MediaEmbed->getProviders();
		$this->assertGreaterThan(30, count($providers));
		$this->assertTrue($providers->has('youtube'));
		$this->assertTrue($providers->has('vimeo'));

		// Test whitelist
		$filtered = $MediaEmbed->getProviders(['youtube', 'vimeo']);
		$this->assertCount(2, $filtered);
		$this->assertTrue($filtered->has('youtube'));
		$this->assertTrue($filtered->has('vimeo'));
		$this->assertFalse($filtered->has('dailymotion'));

		// Test collection methods
		$withIframe = $providers->withIframeSupport();
		$this->assertGreaterThan(0, count($withIframe));

		$withThumbnail = $providers->withThumbnailSupport();
		$this->assertGreaterThan(0, count($withThumbnail));
	}

}
