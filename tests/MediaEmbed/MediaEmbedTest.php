<?php

use \MediaEmbed\MediaEmbed;

/**
 * Test MediaEmbed
 */
class MediaEmbedTest extends \PHPUnit_Framework_TestCase {

	protected $_stubs = [
		'http://bnqt.com/videos/detail/Tuesdays-with-Miles-Dallas-Session-San-Diego/782337671001' => '782337671001',
		'http://www.clipmoon.com/videos/91464f/dog-cat-and-printer.html' => '91464f',
		'http://www.dailymotion.com/video/x2bqyl6_l-entourloop-ft-ruffian-rugged-madder-than-dat_music' => 'x2bqyl6',
		'http://dai.ly/x2bqyl6' => 'x2bqyl6',
		'http://www.youtube.com/watch?v=yiSjHJnc9CY&feature=feedrec_grec_index' => 'yiSjHJnc9CY',
		'https://m.youtube.com/watch?v=yWm4YwqO93I' => 'yWm4YwqO93I',
		'https://www.youtube.com/embed/yWm4YwqO93I?rel=0' => 'yWm4YwqO93I',
		'http://youtu.be/MKlq4gQKtU0' => 'MKlq4gQKtU0',

		// Not yet possible
		//'https://www.youtube.com/playlist?list=PLD1FA4687572EDB23' => 'PLD1FA4687572EDB23',

		'http://www.myvideo.de/watch/7645001/Lena_Nach_Poolszene_bald_nackt_im_Playboy' => '7645001',
		'http://www.metacafe.com/watch/1417475/try_it_yourself_episode_2_hacks_and_tricks_in_google/' => '1417475',
		'http://vimeo.com/19570639' => '19570639',
		'http://vimeo.com/channels/staffpicks/99585787' => '99585787',

		'http://www.clipfish.de/special/dsds/video/3507980/dsds-recall-anna-und-tobias-harmonieren/' => '3507980',
		'http://www.clipfish.de/special/kino-trailer/video/3495650/serengeti-filmausschnitt-gepardenkinder-und-die-jagd-der-mutter/' => '3495650',
		'http://www.clipfish.de/musikvideos/video/3486922/nicole-scherzinger-poison/' => '3486922',

		'http://www.foxhead.com/us/mx/videos/id/23798' => '23798',
		'http://foxhead.com/us/mx/videos/id/23798' => '23798',

		'http://video.aol.com/video/defining-moments-sarah-chalke/711269187' => '711269187',
		'http://www.aniboom.com/animation-video/364332/israel/' => '364332',
		'http://video.adultswim.com/king-of-the-hill/you-there-god-its-me-margaret-hill.html' => 'king-of-the-hill',

		'http://www.xvideos.com/video566979/amber_tickle_tied_to_bed' => '566979',
		'http://xvideos.com/video566979/amber_tickle_tied_to_bed' => '566979',

		// errors
		//'http://www.spike.com/video-clips/g4539c/1000-ways-to-die-gangsta-trapped' => 'g4539c',

		'http://en.vidivodo.com/549248/girlie-slumber-party-with-michelle-rodriguez-' => '549248',
		'http://www.vidivodo.com/549248/girlie-slumber-party-with-michelle-rodriguez-' => '549248',

		'http://www.crackle.com/c/comedians-in-cars-getting-coffee/jon-stewart-the-sound-of-virginity/2493123' => '2493123',

		// errors
		//'http://www.theonion.com/video/american-dream-declared-dead-as-final-believer-giv,19846/' => '19846',

		// errors
		//'http://www.flickr.com/photos/24068543@N00/5582723426/' => '5582723426',

		//'http://video.sina.com.cn/v/b/49393210-2042807271.html' => '49393210',
		//'http://video.sina.com.cn/p/news/w/v/2014-07-21/170364075707.html' => '170364075707',

		'http://community.webshots.com/slideshow/577840443HaeXKG?mediaPosition=4' => '577840443HaeXKG',

		'http://www.crunchyroll.com/super-robot-wars-og-the-inspector/episode-25-what-once-was-572858' => '572858',

		// Errors
		//'http://video.yahoo.com/purinaanimalallstars-10513021/nowplaying-24721185/dog-s-guilty-conscience-charms-web-24722485.html' => '24722485',

		'http://www.viddler.com/explore/sandieman/videos/618/' => '618',

		//'http://new.music.yahoo.com/Burning-Spear/videos/view/Burning-Reggae--2139897;_ylt=AhnR4YcZGFPnoo2G5.JJRTesvyUv' => '2139897',
		'http://new.music.yahoo.com/reggae-cowboys/videos/view/reggae-rodeo--2146467' => '2146467',

		// more difficult
		//'http://www.youtube.com/user/AttilaHildmannTV#p/c/D0F9D267C03BF7BE/0/hHCnY3RwxMM' => 'hHCnY3RwxMM',
		'http://www.youtube.com/watch?v=-vGzem8glbE&feature=channel' => '-vGzem8glbE',

		'http://www.ebaumsworld.com/video/watch/80648170' => '80648170',
		'http://www.ebaumsworld.com/video/watch/80648170/' => '80648170',

		'http://www.videojug.com/film/summer-party-look-with-daniel-sandler' => 'f027ea3e-6eda-8f23-3cc3-ff0008d15e6e',
		'http://www.aparat.com/v/sSLMC' => 'sSLMC',
		'http://www.metatube.com/en/videos/245145/J-Alvarez-Tu-Cuerpo-Pide-Fiesta/' => '245145/J-Alvarez-Tu-Cuerpo-Pide-Fiesta',

		// Fetch lookup required
		'http://www.screencast.com/t/Hh4ulI0M' => '1d44810a-01f4-4c60-a862-6d114bed50c7',
		'http://www.ustream.tv/channel/america2oficial' => '17916695',
		'http://www.ustream.tv/channel/16962149' => '16962149',
		'http://blip.tv/stylestar/shine-6866879' => 'AYOjkCMC',

		'http://example.wistia.com/medias/1voyrefhy9' => '1voyrefhy9',
		
		'http://rutube.ru/video/c1b3c6c6ee77def7a8e54553c1fabbb8/' => 'c1b3c6c6ee77def7a8e54553c1fabbb8',

		// Not available anymore
		//'http://video.google.com/videoplay?docid=-5767589436465272649#' => '-5767589436465272649',
	];

	/**
	 * Test Generation of a basic youtube MediaObject (empty)
	 *
	 * @return void
	 */
	public function testObject() {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->object('youtube');
		$this->assertTrue($Object !== null);
		$result = $Object->name();
		$this->assertEquals('YouTube', $result);

		$result = $Object->id();
		$this->assertEquals('', $result);
	}

	/**
	 * MediaEmbedTest::testParseUrl()
	 *
	 * @return void
	 */
	public function testParseUrlInvalid() {
		$MediaEmbed = new MediaEmbed();
		$result = $MediaEmbed->parseUrl('http://www.youtube.com/foobar');
		$this->assertNull($result);
	}

	/**
	 * MediaEmbedTest::testParseUrl()
	 *
	 * @dataProvider getUrls
	 * @return void
	 */
	public function testParseUrl($url, $id) {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl($url);
		$this->assertInstanceOf('\MediaEmbed\Object\MediaObject', $Object);

		$result = $Object->id();
		$this->assertEquals($id, $result, 'Invalid ID ' . $result . ' for ' . $url);
	}

	/**
	 * Data provider for stub URLs.
	 *
	 * @return array
	 */
	public function getUrls() {
		$urls = [];
		foreach ($this->_stubs as $k => $v) {
			$urls[] = [$k, $v];
		}
		return $urls;
	}

	/**
	 * Test parseId()
	 *
	 * @return void
	 */
	public function testParseId() {
		$test = [
			'dailymotion' => 'x2bqyl6',
			'youtube' => 'yiSjHJnc9CY',
			'myvideo' => '7645001'
		];

		$MediaEmbed = new MediaEmbed();
		foreach ($test as $host => $id) {
			$Object = $MediaEmbed->parseId($id, $host);
			$this->assertInstanceOf('\MediaEmbed\Object\MediaObject', $Object);

			$is = $Object->getEmbedCode();
			$this->assertTrue(!empty($is));
		}
	}


	/**
	 * MediaEmbedTest::testYoutube()
	 *
	 * @return void
	 */
	public function testYoutube() {
		$MediaEmbed = new MediaEmbed();
		$Object = $MediaEmbed->parseUrl('http://www.youtube.com/watch?v=h9Pu4bZqWyg');
		$this->assertInstanceOf('\MediaEmbed\Object\MediaObject', $Object);

		$id = $Object->id();
		$this->assertEquals('h9Pu4bZqWyg', $id);

		$icon = $Object->icon();
		$this->assertNotEmpty($icon);

		$location = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DS;
		$filename = $Object->saveIcon($location, $icon);
		$this->assertEquals('youtube.png', $filename);

		$img = $Object->image();
		$this->assertEquals('https://img.youtube.com/vi/h9Pu4bZqWyg/0.jpg', $img);

		$code = $Object->getEmbedCode();
		$this->assertContains('<iframe', $code);
	}

	public function testYoutubeWithoutIframe() {
		$MediaEmbed = new MediaEmbed(['prefer' => 'object']);
		$Object = $MediaEmbed->parseUrl('http://www.youtube.com/watch?v=h9Pu4bZqWyg');
		$this->assertInstanceOf('\MediaEmbed\Object\MediaObject', $Object);

		$code = $Object->getEmbedCode();
		$this->assertNotContains('<iframe', $code);
	}

	public function testDailymotion() {
		$MediaEmbed = new MediaEmbed();

		$url = 'http://www.dailymotion.com/video/xgv8nw_david-guetta-who-s-that-chick_music#hp-sc-p-1';
		$Object = $MediaEmbed->parseUrl($url);
		$this->assertInstanceOf('\MediaEmbed\Object\MediaObject', $Object);

		$img = $Object->image();
		$this->assertEquals('http://www.dailymotion.com/thumbnail/160x120/video/xgv8nw', $img);
	}

	/**
	 * Test getHosts()
	 *
	 * @return void
	 */
	public function testGetHosts() {
		$MediaEmbed = new MediaEmbed();

		$hosts = $MediaEmbed->getHosts();
		$this->assertTrue(count($hosts) > 50);

		$hosts = $MediaEmbed->getHosts(['myvideo', 'youtube']);
		$this->assertTrue(count($hosts) === 2);
	}

}
