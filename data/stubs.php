<?php
/**
 * Stub Data File for MediaEmbed Plugin
 *
 * To extend or overwrite, specify a custom config file via config array.
 * Make sure to return an array of providers, same format as this file.
 *
 * @author Mark Scherer
 * @license MIT
 */
return [
	/*
	 * Available keys:
	 * - "slug" (string, alphanumeric lowercase) to reference the host
	 *      (fallback: slugified name)
	 * - "name" (string) Source the embeded media comes from
	 * - "website" (string, URL) Website of the host
	 * - "url-match" (string or array of strings) Match the URL(s) to parse
	 * - "fetch-match" (string) To find ID, needs a follow up request (secondary matching)
	 * - "embed-src" (string) Source of the embed (usually flash player swf)
	 * - "embed-width" (string) Width of the embed (in pixels)
	 * - "embed-height" (string) Height of the embed (in pixels)
	 * - "image-src" (string) For preview thumbnail image
	 * - "iframe-player" (string) If an <iframe> player is available
	 * - "id" (string) The ID of the video, returned as $id
	 * - "flashvars" (string) Parameters to pass to the flash player
	 */
	[
		'name' => 'YouTube',
		'website' => 'https://www.youtube.com',
		'url-match' => [
			'https?://youtu\\.be/([0-9a-z-_]{11})(?:[?&](?:t|start)=([0-9]+))?',
			'https?://(?:www\\.)?youtube\\.com/live/([0-9a-z-_A-Z]{11})(?:.*?[?&](?:t|start)=([0-9]+))?',
			'https?://(?:(?:m|www|au|br|ca|es|fr|de|hk|ie|in|il|it|jp|kr|mx|nl|nz|pl|ru|tw|uk)\\.)?youtube\\.com/watch\\?(?:[^&]*&)*v=([0-9a-z-_]{11})(?:&(?:[^&]*&)*(?:t|start)=([0-9]+))?',
			'https?://(?:video\\.google\\.(?:com|com\\.au|co\\.uk|de|es|fr|it|nl|pl|ca|cn)/(?:[^"]*?))?(?:(?:m|www|au|br|ca|es|fr|de|hk|ie|in|il|it|jp|kr|mx|nl|nz|pl|ru|tw|uk)\\.)?youtube\\.com(?:[^"]*?)?(?:&|&amp;|/|\\?|;|\\%3F|\\%2F)(?:video_id=|v(?:/|=|\\%3D|\\%2F)|embed(?:/|=|\\%3D|\\%2F))([0-9a-z-_]{11})',
			'https?://(?:www\\.)?youtube\\.com/shorts/([0-9a-z-_A-Z]{11})',
		],
		'embed-src' => 'https://www.youtube.com/v/$2&rel=0&fs=1',
		'embed-width' => '480',
		'embed-height' => '295',
		'image-src' => '//img.youtube.com/vi/$2/0.jpg',
		'iframe-player' => '//www.youtube.com/embed/$2',
		'id' => '$2',
		'supports-timestamp' => true,
	],
	[
		'name' => 'Facebook',
		'website' => 'https://www.facebook.com',
		'url-match' => [
			'https://www.facebook.com/[0-9a-z-_.]+/videos/([0-9]+)/',
			'https://www.facebook.com/[0-9a-zA-Z-_.]+/videos/(?:vb.\\d+)/(\\d+)/',
			'https://www.facebook.com/[0-9a-zA-Z-_.]+/videos/(?:vl.\\d+)/(\\d+)/',
		],
		'embed-src' => '//www.facebook.com/video/embed?video_id=$2',
		'embed-width' => '480',
		'embed-height' => '295',
		'iframe-player' => '//www.facebook.com/plugins/video.php?href=$1&show_text=0',
	],
	[
		'name' => 'Dailymotion',
		'website' => 'https://www.dailymotion.com',
		'url-match' => [
			'https?://dai\\.ly/([a-z0-9]{1,})',
			'https?://(?:www\\.)?dailymotion\\.(?:com|alice\\.it)/(?:(?:[^"]*?)?video|swf)/([a-z0-9]{1,18})',
		],
		'embed-src' => 'https://www.dailymotion.com/swf/$2&related=0',
		'embed-width' => '420',
		'embed-height' => '339',
		'image-src' => 'https://www.dailymotion.com/thumbnail/160x120/video/$2',
		'iframe-player' => '//www.dailymotion.com/embed/video/$2',
	],
	[
		'name' => 'Vimeo',
		'website' => 'https://www.vimeo.com',
		'url-match' => [
			'https?:\\/\\/player\\.vimeo\\.com\\/video\\/([0-9]+(?:\\/[a-zA-Z0-9]+)?)',
			'https?:\\/\\/(?:www\\.)?vimeo\\.com\\/(?:channels\\/[a-zA-Z0-9]+\\/)?([0-9]+(?:\\/[a-zA-Z0-9]+)?)',
		],
		'embed-src' => 'https://vimeo.com/moogaloop.swf?clip_id=$2&server=vimeo.com&fullscreen=1&show_title=1&show_byline=1&show_portrait=0&color=01AAEA',
		'embed-width' => '400',
		'embed-height' => '302',
		'iframe-player' => '//player.vimeo.com/video/$2',
	],
	[
		'name' => 'Aparat',
		'website' => 'https://www.aparat.com',
		'url-match' => 'https?://www.aparat.com/v/([A-Za-z0-9-_]+)(?:/.*)?',
		'embed-src' => '',
		'embed-width' => '425',
		'embed-height' => '354',
		'iframe-player' => 'https://www.aparat.com/video/video/embed/videohash/$2/vt/frame/',
	],
	[
		'slug' => 'clipfish-old',
		'name' => 'ClipFish (Old)',
		'website' => 'https://www.clipfish.de',
		'url-match' => 'https?://(?:www\\.)?clipfish\\.de/(?:(?:player\\.php|videoplayer\\.swf)\\?(?:[^"]*?)?vid=|video/)([0-9]{1,20})',
		'embed-src' => 'https://www.clipfish.de/videoplayer.swf?as=0&vid=$2&r=1',
		'embed-width' => '464',
		'embed-height' => '380',
		'iframe-player' => 'https://www.clipfish.de/embed_video/?vid=$2',
	],
	[
		'slug' => 'clipfish-special',
		'name' => 'ClipFish (Special)',
		'website' => 'https://www.clipfish.de',
		'url-match' => 'https?://(?:www\\.)?clipfish\\.de/(?:[^"]*?)/video/((?:[a-z0-9]{18})(?:==)?|(?:[a-z0-9]{6,7})(?:==)?)',
		'embed-src' => 'https://www.clipfish.de/videoplayer.swf?as=0&videoid=$2%3D%3D&r=1',
		'embed-width' => '464',
		'embed-height' => '380',
		'iframe-player' => 'https://www.clipfish.de/embed_video/?vid=$2',
	],
	[
		'slug' => 'clipfish',
		'name' => 'ClipFish (New)',
		'website' => 'https://www.clipfish.de',
		'url-match' => 'https?://(?:www\\.)?clipfish\\.de/(?:video)?player\\.(?:swf|php)(?:[^"]*?)videoid=((?:[a-z0-9]{18})(?:==)?|(?:[a-z0-9]{6})(?:==)?)',
		'embed-src' => 'https://www.clipfish.de/videoplayer.swf?as=0&videoid=$2%3D%3D&r=1',
		'embed-width' => '464',
		'embed-height' => '380',
		'iframe-player' => 'https://www.clipfish.de/embed_video/?vid=$2',
	],
	[
		'name' => 'Matterport',
		'website' => 'https://matterport.com',
		'url-match' => 'https://my\\.matterport\\.com/show/[?&]m=([0-9a-zA-Z]+)',
		'embed-src' => '',
		'embed-width' => '450',
		'embed-height' => '450',
		'iframe-player' => 'https://my.matterport.com/show/?m=$2',
	],
	[
		'name' => 'Metatube',
		'website' => 'https://www.metatube.com',
		'url-match' => 'https?://www\\.metatube\\.com/([a-z]+)/videos/([a-z0-9-/]+)/',
		'embed-src' => '',
		'embed-width' => '420',
		'embed-height' => '315',
		'iframe-player' => 'https://www.metatube.com/$2/videos/$3/embed/',
	],
	[
		'name' => 'RuTube',
		'website' => 'https://www.rutube.ru',
		'url-match' => 'https?://(?:www\\.|video\\.)?rutube\\.ru/(?:video/|tracks/\\d+?\\.html\\?(?:(?:pos|related)=1&(?:amp;)?)?v=)?([0-9a-f]{32})',
		'embed-src' => 'https://video.rutube.ru/$2',
		'embed-width' => '470',
		'embed-height' => '353',
		'iframe-player' => 'https://rutube.ru/play/embed/$2',
	],
	[
		'name' => 'Screencast',
		'website' => 'https://www.screencast.com',
		'url-match' => 'https://(?:www\\.)?screencast\\.com/t/([0-9a-zA-Z]+)',
		'fetch-match' => 'https://www\\.screencast\\.com/users/CamtasiaTraining/folders/Camtasia/media/([a-z0-9-]+)/embed',
		'embed-src' => 'https://content.screencast.com/users/CamtasiaTraining/folders/Camtasia/media/1d44810a-01f4-4c60-a862-6d114bed50c7/tscplayer.swf',
		'embed-width' => '425',
		'embed-height' => '344',
		'iframe-player' => 'https://www.screencast.com/users/CamtasiaTraining/folders/Camtasia/media/$2/embed',
	],
	[
		'name' => 'Ustream',
		'website' => 'https://www.ustream.tv',
		'url-match' => 'https?://www\\.ustream\\.tv/channel/[0-9a-z-]+',
		'fetch-match' => 'https?://www\\.ustream\\.tv/embed/([0-9]+)',
		'embed-src' => 'https://www.ustream.tv/flash/viewer.swf',
		'embed-width' => '480',
		'embed-height' => '299',
		'iframe-player' => 'https://www.ustream.tv/embed/$2?mode=direct',
		'flashvars' => 'cid=$2&amp;autoplay=false&amp;locale=de_DE',
	],
	[
		'name' => 'Wistia',
		'website' => 'https://www.wistia.com',
		'url-match' => 'https?://[a-z0-9\\-_]*\\.wistia\\.com/medias/([a-z0-9]*)',
		'embed-src' => 'https://fast.wistia.net/embed/iframe/$2',
		'embed-width' => '480',
		'embed-height' => '270',
		'iframe-player' => 'https://fast.wistia.net/embed/iframe/$2',
	],
	[
		'name' => 'Twitch Video',
		'website' => 'https://www.twitch.tv',
		'url-match' => 'https?://www\\.twitch\\.tv/videos/([0-9]{9})',
		'embed-src' => 'https://player.twitch.tv/?video=v$2',
		'embed-width' => '620',
		'embed-height' => '378',
		'iframe-player' => 'https://player.twitch.tv/?video=v$2',
	],
	[
		'name' => 'Twitch Clip',
		'website' => 'https://clips.twitch.tv',
		'url-match' => 'https?://clips\\.twitch\\.tv/([A-Za-z]+)',
		'embed-src' => 'https://clips.twitch.tv/embed?clip=$2',
		'embed-width' => '620',
		'embed-height' => '378',
		'iframe-player' => 'https://clips.twitch.tv/embed?clip=$2',
	],
	[
		'name' => 'Vooplayer',
		'website' => 'https://vooplayer.com/',
		'url-match' => 'https:\\/\\/(.+)\\.cdn\\.vooplayer\\.com/publish/([0-9A-Za-z-]+)',
		'iframe-player' => 'https://$2.cdn.vooplayer.com/publish/$3?fallback=true',
		'embed-src' => '',
		'embed-width' => '480',
		'embed-height' => '270',
	],
	[
		'name' => 'SoundCloud',
		'website' => 'https://soundcloud.com',
		'url-match' => [
			'https://soundcloud\\.com/([0-9a-zA-Z-_\\/]+)',
		],
		'embed-src' => '',
		'embed-width' => '100%',
		'embed-height' => '150',
		'iframe-player' => 'https://w.soundcloud.com/player/?url=https%3A%2F%2Fsoundcloud.com%2F$2',
		'id' => '$2',
	],
	[
		'name' => 'Mixcloud',
		'website' => 'https://www.mixcloud.com',
		'url-match' => [
			'https?://(?:www\\.)?mixcloud\\.com/([^/]+)/([^/]+)/?',
		],
		'embed-src' => '',
		'embed-width' => '100%',
		'embed-height' => '120',
		'iframe-player' => '//www.mixcloud.com/widget/iframe/?feed=https%3A%2F%2Fwww.mixcloud.com%2F$2%2F$3%2F',
		'id' => '$2/$3',
	],
	[
		'name' => 'Loom',
		'website' => 'https://loom.com',
		'url-match' => [
			'https:\\/\\/www\\.loom\\.com\\/(share|embed)?\\/([0-9a-z-]+)',
		],
		'embed-src' => '',
		'embed-width' => '640',
		'embed-height' => '400',
		'iframe-player' => 'https://www.loom.com/embed/$3',
		'id' => '$3',
	],
	[
		'name' => 'TikTok',
		'website' => 'https://www.tiktok.com',
		'url-match' => [
			'https?://(?:www\\.)?tiktok\\.com/@[^/]+/video/([0-9]+)',
			'https?://(?:vm|vt)\\.tiktok\\.com/([A-Za-z0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '325',
		'embed-height' => '580',
		'iframe-player' => 'https://www.tiktok.com/embed/v2/$2',
		'id' => '$2',
	],
	[
		'name' => 'Instagram',
		'website' => 'https://www.instagram.com',
		'url-match' => [
			'https?://(?:www\\.)?instagram\\.com/(?:p|reel|tv)/([A-Za-z0-9_-]+)',
		],
		'embed-src' => '',
		'embed-width' => '400',
		'embed-height' => '480',
		'iframe-player' => 'https://www.instagram.com/p/$2/embed',
		'id' => '$2',
	],
	[
		'name' => 'Twitter',
		'website' => 'https://twitter.com',
		'url-match' => [
			'https?://(?:www\\.)?(?:twitter|x)\\.com/[^/]+/status/([0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '550',
		'embed-height' => '400',
		'iframe-player' => 'https://platform.twitter.com/embed/Tweet.html?id=$2',
		'id' => '$2',
	],
	[
		'name' => 'Reddit',
		'website' => 'https://www.reddit.com',
		'url-match' => [
			'https?://(?:www\\.)?reddit\\.com/r/[^/]+/comments/([A-Za-z0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '640',
		'embed-height' => '400',
		'iframe-player' => 'https://www.redditmedia.com/r/videos/comments/$2?ref_source=embed&embed=true',
		'id' => '$2',
	],
	[
		'name' => 'Spotify',
		'website' => 'https://open.spotify.com',
		'url-match' => [
			'https?://open\\.spotify\\.com/(track|album|playlist|episode|show)/([A-Za-z0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '300',
		'embed-height' => '380',
		'iframe-player' => 'https://open.spotify.com/embed/$2/$3',
		'id' => '$3',
	],
	[
		'name' => 'Streamable',
		'website' => 'https://streamable.com',
		'url-match' => [
			'https?://(?:www\\.)?streamable\\.com/(?:e/)?([A-Za-z0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '640',
		'embed-height' => '360',
		'iframe-player' => 'https://streamable.com/e/$2',
		'id' => '$2',
	],
	[
		'name' => 'Bilibili',
		'website' => 'https://www.bilibili.com',
		'url-match' => [
			'https?://(?:www\\.)?bilibili\\.com/video/(BV[A-Za-z0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '640',
		'embed-height' => '360',
		'iframe-player' => '//player.bilibili.com/player.html?bvid=$2',
		'id' => '$2',
	],
	[
		'slug' => 'bilibili-legacy',
		'name' => 'Bilibili (Legacy)',
		'website' => 'https://www.bilibili.com',
		'url-match' => [
			'https?://(?:www\\.)?bilibili\\.com/video/av([0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '640',
		'embed-height' => '360',
		'iframe-player' => '//player.bilibili.com/player.html?aid=$2',
		'id' => '$2',
	],
	[
		'name' => 'Rumble',
		'website' => 'https://rumble.com',
		'url-match' => [
			'https?://(?:www\\.)?rumble\\.com/embed/([a-z0-9]+)',
			'https?://(?:www\\.)?rumble\\.com/(v[a-z0-9]+)-[^/]+\\.html',
		],
		'embed-src' => '',
		'embed-width' => '640',
		'embed-height' => '360',
		'iframe-player' => 'https://rumble.com/embed/$2/',
		'id' => '$2',
	],
	[
		'name' => 'Odysee',
		'website' => 'https://odysee.com',
		'url-match' => [
			'https?://(?:www\\.)?odysee\\.com/(@[^/:]+:[a-z0-9])/([^/:]+:[a-z0-9])',
			'https?://(?:www\\.)?odysee\\.com/\\$/embed/([^/]+)/([a-z0-9]+)',
		],
		'embed-src' => '',
		'embed-width' => '560',
		'embed-height' => '315',
		'iframe-player' => 'https://odysee.com/$/embed/$2/$3',
		'id' => '$3',
	],
	[
		'name' => 'Kick',
		'website' => 'https://kick.com',
		'url-match' => [
			'https?://(?:www\\.)?kick\\.com/[^/]+/clips/([a-zA-Z0-9_-]+)',
			'https?://(?:www\\.)?kick\\.com/video/([a-z0-9-]+)',
		],
		'embed-src' => '',
		'embed-width' => '640',
		'embed-height' => '360',
		'iframe-player' => 'https://player.kick.com/video/$2',
		'id' => '$2',
	],
	[
		'name' => 'Bandcamp',
		'website' => 'https://bandcamp.com',
		'url-match' => [
			'https?://([a-z0-9-]+)\\.bandcamp\\.com/(track|album)/([a-z0-9-]+)',
		],
		'embed-src' => '',
		'embed-width' => '350',
		'embed-height' => '470',
		'iframe-player' => 'https://bandcamp.com/EmbeddedPlayer/$3=$2/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/transparent=true/',
		'id' => '$2/$4',
	],
	[
		'name' => 'PeerTube',
		'website' => 'https://joinpeertube.org',
		'url-match' => [
			'https?://([a-z0-9.-]+)/(?:videos/watch|w)/([a-zA-Z0-9-]+)',
		],
		'embed-src' => '',
		'embed-width' => '560',
		'embed-height' => '315',
		'iframe-player' => 'https://$2/videos/embed/$3',
		'id' => '$3',
	],
];
