<?php

namespace MediaEmbed\Test;

use InvalidArgumentException;
use MediaEmbed\Exception\ProviderConfigException;
use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\Matcher\MatchResult;
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
		'https://www.youtube.com/shorts/yiSjHJnc9CY' => 'yiSjHJnc9CY',
		'https://www.facebook.com/mega90er/videos/1309058692443747/' => '1309058692443747',
		'https://www.facebook.com/diginights.HN/videos/1231155290281511/' => '1231155290281511',
		'https://www.facebook.com/SkySports/videos/vb.10911153761/10153310275743762/?type=2&theater' => '10153310275743762',
		'https://www.facebook.com/demotivateurFood/videos/vl.184872862011827/1034411179983244/?type=1' => '1034411179983244',
		'http://vimeo.com/19570639' => '19570639',
		'http://vimeo.com/245928033/572c32a20d' => '245928033/572c32a20d',
		'http://vimeo.com/channels/staffpicks/99585787' => '99585787',
		'https://player.vimeo.com/video/19570639' => '19570639',
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
		'https://streamable.com/moo' => 'moo',
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
		'https://odysee.com/@channel:a/video-title:b' => 'video-title:b',
		// Kick
		'https://kick.com/username/clips/clip_abc123' => 'clip_abc123',
		'https://kick.com/video/12345-abcd-6789' => '12345-abcd-6789',
		// Bandcamp
		'https://publicpractice.bandcamp.com/track/disposable' => 'publicpractice/disposable',
		'https://someband.bandcamp.com/album/album-name' => 'someband/album-name',
		// PeerTube
		'https://peertube.tv/w/oxKYBCdgGHmQgAxUZe3cv8' => 'oxKYBCdgGHmQgAxUZe3cv8',
		'https://video.instance.com/videos/watch/def456789' => 'def456789',
		// TED
		'https://www.ted.com/talks/sir_ken_robinson_do_schools_kill_creativity' => 'sir_ken_robinson_do_schools_kill_creativity',
		// Giphy
		'https://giphy.com/gifs/feels-feelings-l0HlvtIPzPdt2usKs' => 'l0HlvtIPzPdt2usKs',
		'https://giphy.com/embed/l0HlvtIPzPdt2usKs' => 'l0HlvtIPzPdt2usKs',
		// Niconico
		'https://www.nicovideo.jp/watch/sm9' => 'sm9',
		'https://nico.ms/sm9' => 'sm9',
		// Audiomack
		'https://audiomack.com/officialsisqo/song/thong-song-1' => 'officialsisqo/song/thong-song-1',
		// Spreaker
		'https://www.spreaker.com/episode/worst-haircut--11728706' => '11728706',
		'https://www.spreaker.com/episode/11728706' => '11728706',
		// Sketchfab
		'https://sketchfab.com/3d-models/the-great-drawing-room-2QpgjMeXKHq6L8KIBAJjRrFV3jg' => '2QpgjMeXKHq6L8KIBAJjRrFV3jg',
		// Coub
		'https://coub.com/view/3as0mf' => '3as0mf',
		// BitChute
		'https://www.bitchute.com/video/UGlrF9o9b-Q/' => 'UGlrF9o9b-Q',
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

	public function testParseUrlRejectsTextContainingSupportedUrl(): void {
		$MediaEmbed = new MediaEmbed();
		$result = $MediaEmbed->parseUrl('Watch https://www.youtube.com/watch?v=yiSjHJnc9CY now');

		$this->assertNull($result);
	}

	public function testParseUrlRejectsNonHttpUrl(): void {
		$MediaEmbed = new MediaEmbed();
		$result = $MediaEmbed->parseUrl('javascript:https://www.youtube.com/watch?v=yiSjHJnc9CY');

		$this->assertNull($result);
	}

	public function testMatchUrl(): void {
		$MediaEmbed = new MediaEmbed();
		$result = $MediaEmbed->matchUrl('https://www.youtube.com/watch?v=yiSjHJnc9CY');

		$this->assertInstanceOf(MatchResult::class, $result);
		$this->assertSame('youtube', $result->providerSlug);
		$this->assertSame('yiSjHJnc9CY', $result->getId());
	}

	public function testSupportsUrl(): void {
		$MediaEmbed = new MediaEmbed();

		$this->assertTrue($MediaEmbed->supportsUrl('https://www.youtube.com/watch?v=yiSjHJnc9CY'));
		$this->assertFalse($MediaEmbed->supportsUrl('https://example.com/no-provider'));
	}

	public function testGetProviderForUrl(): void {
		$MediaEmbed = new MediaEmbed();
		$provider = $MediaEmbed->getProviderForUrl('https://www.youtube.com/watch?v=yiSjHJnc9CY');

		$this->assertInstanceOf(ProviderConfig::class, $provider);
		$this->assertSame('youtube', $provider->slug);
		$this->assertSame('YouTube', $provider->name);
		$this->assertNull($MediaEmbed->getProviderForUrl('https://example.com/no-provider'));
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

	public function testDefaultProviderPatternsHaveFixtureCoverage(): void {
		$providers = include dirname(__DIR__) . '/data/stubs.php';
		$missing = [];

		foreach ($providers as $provider) {
			if (!empty($provider['fetch-match'])) {
				continue;
			}

			foreach ((array)$provider['url-match'] as $index => $pattern) {
				$covered = false;
				foreach (array_keys(static::$_stubs) as $url) {
					if (preg_match('~' . $pattern . '~imu', $url)) {
						$covered = true;

						break;
					}
				}

				if (!$covered) {
					$missing[] = $provider['name'] . ' #' . $index;
				}
			}
		}

		$this->assertSame([], $missing);
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
	 * @dataProvider getEmbedSrcUrls
	 * @param string $url
	 * @param string $expected
	 * @return void
	 */
	#[DataProvider('getEmbedSrcUrls')]
	public function testGetEmbedSrc(string $url, string $expected): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl($url);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertSame($expected, $Object->getEmbedSrc());
	}

	/**
	 * Data provider for expected embed src URLs.
	 *
	 * @return array
	 */
	public static function getEmbedSrcUrls(): array {
		return [
			['https://www.mixcloud.com/spartacus/party-time/', '//www.mixcloud.com/widget/iframe/?feed=https%3A%2F%2Fwww.mixcloud.com%2Fspartacus%2Fparty-time%2F&wmode=transparent'],
			['https://open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh', 'https://open.spotify.com/embed/track/4iV5W9uYEdYUVa79Axb7Rh?wmode=transparent'],
			['https://publicpractice.bandcamp.com/track/disposable', 'https://bandcamp.com/EmbeddedPlayer/track=publicpractice/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/transparent=true/?wmode=transparent'],
			['https://peertube.tv/w/oxKYBCdgGHmQgAxUZe3cv8', 'https://peertube.tv/videos/embed/oxKYBCdgGHmQgAxUZe3cv8?wmode=transparent'],
			['https://www.metatube.com/en/videos/245145/J-Alvarez-Tu-Cuerpo-Pide-Fiesta/', 'https://www.metatube.com/en/videos/245145/J-Alvarez-Tu-Cuerpo-Pide-Fiesta/embed/?wmode=transparent'],
			['https://lds.cdn.vooplayer.com/publish/MTEwNTMw', 'https://lds.cdn.vooplayer.com/publish/MTEwNTMw?fallback=true&wmode=transparent'],
			['https://instagram.com/reel/XYZ789abc/', 'https://www.instagram.com/reel/XYZ789abc/embed?wmode=transparent'],
			['https://www.instagram.com/tv/DEF456ghi/', 'https://www.instagram.com/tv/DEF456ghi/embed?wmode=transparent'],
		];
	}

	public function testScreencastFetchProvider(): void {
		$httpClient = $this->createStub(HttpClientInterface::class);
		$httpClient->method('get')
			->willReturn('<iframe src="https://www.screencast.com/users/CamtasiaTraining/folders/Camtasia/media/1d44810a-01f4-4c60-a862-6d114bed50c7/embed"></iframe>');

		$MediaEmbed = new MediaEmbed(httpClient: $httpClient);
		$Object = $MediaEmbed->parseUrl('https://www.screencast.com/t/Hh4ulI0M');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertSame('1d44810a-01f4-4c60-a862-6d114bed50c7', $Object->id());
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

	public function testEmbedCodeWithCustomAttributesAndParams(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$Object->setParam([
			'autoplay' => 1,
			'loop' => 1,
		]);
		$Object->setParam('rel', 0);
		$Object->setAttribute([
			'type' => null,
			'class' => 'iframe-class',
			'data-html5-parameter' => true,
			'hidden' => false,
		]);

		$code = $Object->getEmbedCode();

		$this->assertStringStartsWith('<iframe src="//www.youtube.com/embed/11111111111?wmode=transparent&amp;autoplay=1&amp;loop=1&amp;rel=0"', $code);
		$this->assertStringContainsString(' class="iframe-class"', $code);
		$this->assertStringContainsString(' data-html5-parameter', $code);
		$this->assertStringNotContainsString(' type=', $code);
		$this->assertStringNotContainsString(' hidden', $code);
	}

	public function testEmbedCodeIncludesDefaultIframeAttributes(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$code = $Object->getEmbedCode();

		$this->assertStringContainsString(' title="YouTube embed"', $code);
		$this->assertStringContainsString(' loading="lazy"', $code);
		$this->assertStringContainsString(' referrerpolicy="strict-origin-when-cross-origin"', $code);
		$this->assertStringContainsString(' allow="fullscreen; picture-in-picture"', $code);
		$this->assertStringContainsString(' allowfullscreen', $code);
	}

	public function testPrivacyModeUsesNoCookieHostForYoutube(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111', ['privacy' => true]);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertSame('//www.youtube-nocookie.com/embed/11111111111?wmode=transparent', $Object->getEmbedSrc());
	}

	public function testPrivacyModeIsOptInForYoutube(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertStringContainsString('//www.youtube.com/embed/11111111111', $Object->getEmbedSrc());
		$this->assertStringNotContainsString('nocookie', $Object->getEmbedSrc());
	}

	public function testPrivacyModeAddsParamsForVimeo(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://vimeo.com/channels/staffpicks/99585787', ['privacy' => true]);
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertStringContainsString('dnt=1', $Object->getEmbedSrc());
	}

	public function testResponsiveEmbedCodeWrapsIframeWithDefaultRatio(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$code = $Object->getResponsiveEmbedCode();

		$this->assertStringStartsWith('<div style="position:relative;width:100%;height:0;padding-bottom:56.25%;overflow:hidden;">', $code);
		$this->assertStringContainsString('<iframe', $code);
		$this->assertStringContainsString('style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;"', $code);
		$this->assertStringEndsWith('</div>', $code);
	}

	public function testResponsiveEmbedCodeDoesNotMutateObjectState(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$Object->getResponsiveEmbedCode();

		$this->assertNull($Object->getAttributes('style'));
		$this->assertStringNotContainsString('position:absolute', $Object->getEmbedCode());
	}

	public function testResponsiveEmbedCodePreservesExistingStyle(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$Object->setAttribute('style', 'border-radius:8px');
		$code = $Object->getResponsiveEmbedCode();

		$this->assertStringContainsString('style="border-radius:8px;position:absolute;', $code);
	}

	public function testResponsiveEmbedCodeSupportsCustomRatio(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertStringContainsString('padding-bottom:75%;', $Object->getResponsiveEmbedCode('4:3'));
	}

	public function testResponsiveEmbedCodeRejectsInvalidRatio(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->expectException(InvalidArgumentException::class);
		$Object->getResponsiveEmbedCode('16x9');
	}

	public function testResponsiveEmbedCodeRejectsZeroRatioComponent(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->expectException(InvalidArgumentException::class);
		$Object->getResponsiveEmbedCode('16:0');
	}

	public function testEmbedCodeDefaultIframeAttributesCanBeOverridden(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$Object->setAttribute([
			'title' => 'Custom video title',
			'loading' => 'eager',
			'referrerpolicy' => null,
			'allow' => false,
			'sandbox' => 'allow-scripts allow-same-origin',
		]);
		$code = $Object->getEmbedCode();

		$this->assertStringContainsString(' title="Custom video title"', $code);
		$this->assertStringContainsString(' loading="eager"', $code);
		$this->assertStringNotContainsString(' referrerpolicy=', $code);
		$this->assertStringNotContainsString(' allow=', $code);
		$this->assertStringContainsString(' sandbox="allow-scripts allow-same-origin"', $code);
	}

	public function testProviderDefaultIframeParams(): void {
		$MediaEmbed = new MediaEmbed();
		$MediaEmbed->addProviderConfig(new ProviderConfig(
			name: 'ParamProvider',
			website: 'https://param.example.com',
			urlMatch: 'https://param\\.example\\.com/video/([0-9]+)',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//param.example.com/embed/$2',
			iframeParams: [
				'parent' => 'example.com',
			],
		));

		$Object = $MediaEmbed->parseUrl('https://param.example.com/video/12345');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertStringContainsString('parent=example.com', $Object->getEmbedSrc());
	}

	public function testConfiguredProviderParamsOverrideDefaults(): void {
		$MediaEmbed = new MediaEmbed([
			'provider_params' => [
				'paramprovider' => [
					'parent' => 'configured.example.com',
				],
			],
		]);
		$MediaEmbed->addProviderConfig(new ProviderConfig(
			name: 'ParamProvider',
			website: 'https://param.example.com',
			urlMatch: 'https://param\\.example\\.com/video/([0-9]+)',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//param.example.com/embed/$2',
			iframeParams: [
				'parent' => 'default.example.com',
			],
		));

		$Object = $MediaEmbed->parseUrl('https://param.example.com/video/12345');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertStringContainsString('parent=configured.example.com', $Object->getEmbedSrc());
		$this->assertStringNotContainsString('parent=default.example.com', $Object->getEmbedSrc());
	}

	public function testTwitchParentParamCanBeConfigured(): void {
		$MediaEmbed = new MediaEmbed([
			'provider_params' => [
				'twitch-video' => [
					'parent' => 'example.com',
				],
			],
		]);

		$Object = $MediaEmbed->parseUrl('https://www.twitch.tv/videos/293684811');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertStringContainsString('parent=example.com', $Object->getEmbedSrc());
	}

	public function testEmbedCodeEscapesIframeSource(): void {
		$MediaEmbed = new MediaEmbed();
		$MediaEmbed->addProviderConfig(new ProviderConfig(
			name: 'UnsafeProvider',
			website: 'https://unsafe.example.com',
			urlMatch: 'https://unsafe\\.example\\.com/video/([0-9]+)',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//unsafe.example.com/embed/$2?foo=1&bar="quoted"',
		));

		$Object = $MediaEmbed->parseUrl('https://unsafe.example.com/video/12345');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$code = $Object->getEmbedCode();

		$this->assertStringContainsString('src="//unsafe.example.com/embed/12345?foo=1&amp;bar=&quot;quoted&quot;&amp;wmode=transparent"', $code);
		$this->assertStringNotContainsString('bar="quoted"', $code);
		$this->assertSame('//unsafe.example.com/embed/12345?foo=1&bar="quoted"&wmode=transparent', $Object->getEmbedSrc());
		$this->assertSame('//unsafe.example.com/embed/12345?foo=1&amp;bar=&quot;quoted&quot;&amp;wmode=transparent', $Object->getEmbedSrcForHtml());
	}

	public function testGetEmbedSrcUsesRawQuerySeparator(): void {
		$separator = ini_get('arg_separator.output');
		ini_set('arg_separator.output', '&amp;');

		try {
			$MediaEmbed = new MediaEmbed();
			$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
			$this->assertInstanceOf(MediaObject::class, $Object);

			$this->assertSame('//www.youtube.com/embed/11111111111?wmode=transparent', $Object->getEmbedSrc());
		} finally {
			ini_set('arg_separator.output', $separator);
		}
	}

	public function testSetAttributeRejectsInvalidAttributeName(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid iframe attribute name "x onload"');

		$Object->setAttribute('x onload', 'alert(1)');
	}

	public function testSetAttributeRejectsEventHandlerAttributeName(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid iframe attribute name "onload"');

		$Object->setAttribute('onload', 'alert(1)');
	}

	public function testSetAttributeAllowsDataAndAriaAttributes(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$Object->setAttribute([
			'data-controller' => 'media',
			'aria-label' => 'Video',
		]);

		$code = $Object->getEmbedCode();

		$this->assertStringContainsString(' data-controller="media"', $code);
		$this->assertStringContainsString(' aria-label="Video"', $code);
	}

	public function testAdjustDimensionsSkipsMissingCurrentDimension(): void {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('https://www.youtube.com/watch?v=11111111111');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$Object->setAttribute('width', null);
		$Object->setHeight(200, adjustWidth: true);

		$this->assertNull($Object->getAttributes('width'));
		$this->assertSame(200, $Object->getAttributes('height'));
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

	public function testCustomProviderTimestampParameter(): void {
		$MediaEmbed = new MediaEmbed();
		$MediaEmbed->addProviderConfig(new ProviderConfig(
			name: 'TimedProvider',
			website: 'https://timed.example.com',
			urlMatch: 'https://timed\\.example\\.com/video/([0-9]+)\\?at=([0-9]+)',
			embedWidth: 640,
			embedHeight: 360,
			iframePlayer: '//timed.example.com/embed/$2',
			supportsTimestamp: true,
			timestampParam: 'time',
		));

		$Object = $MediaEmbed->parseUrl('https://timed.example.com/video/12345?at=60');
		$this->assertInstanceOf(MediaObject::class, $Object);

		$this->assertStringContainsString('time=60', $Object->getEmbedCode());
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
		$this->assertCount(37, $hosts);

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

	public function testAddProviderConfigRequiresIframePlayer(): void {
		$MediaEmbed = new MediaEmbed();
		$customProvider = new ProviderConfig(
			name: 'NoIframeProvider',
			website: 'https://no-iframe.example.com',
			urlMatch: ['https?://(?:www\.)?no-iframe\.example\.com/video/([0-9]+)'],
			embedWidth: 640,
			embedHeight: 360,
		);

		$this->expectException(ProviderConfigException::class);
		$this->expectExceptionMessage('Provider configuration is missing required field: iframe-player');

		$MediaEmbed->addProviderConfig($customProvider);
	}

	public function testCustomProvidersConfigHonorsExplicitSlug(): void {
		$customProviders = [
			[
				'name' => 'Display Name Provider',
				'slug' => 'stable-provider',
				'website' => 'https://stable.example.com',
				'url-match' => [
					'https?://stable\.example\.com/v/([a-z0-9]+)',
				],
				'embed-width' => '500',
				'embed-height' => '300',
				'iframe-player' => '//stable.example.com/embed/$2',
			],
		];

		$MediaEmbed = new MediaEmbed(['custom_providers' => $customProviders]);

		$this->assertNotNull($MediaEmbed->getProvider('stable-provider'));
		$this->assertNull($MediaEmbed->getProvider('display-name-provider'));

		$Object = $MediaEmbed->parseUrl('https://stable.example.com/v/abc123');
		$this->assertInstanceOf(MediaObject::class, $Object);
		$this->assertSame('stable-provider', $Object->slug());
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
		$this->assertCount(37, $providers);
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
