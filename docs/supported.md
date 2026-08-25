# Supported Media Services

46 services (45 active, 1 legacy)

Provider example URLs are covered by the release fixture matrix in `tests/Fixture/provider_urls.php`.

| Service | Status | Category | Capabilities | Notes |
|---------|--------|----------|--------------|-------|
| [Aparat](https://www.aparat.com) | active | video | iframe |  |
| [Apple Music](https://music.apple.com) | active | audio | iframe |  |
| [Apple Podcasts](https://podcasts.apple.com) | active | audio | iframe |  |
| [Audiomack](https://audiomack.com) | active | audio | iframe, oEmbed |  |
| [Bandcamp](https://bandcamp.com) | active | audio | iframe |  |
| [Bilibili](https://www.bilibili.com) | active | video | iframe |  |
| [Bilibili (Legacy)](https://www.bilibili.com) | legacy | video | iframe | Legacy URL format kept for existing links. |
| [BitChute](https://www.bitchute.com) | active | video | iframe |  |
| [Bluesky](https://bsky.app) | active | social | oEmbed | Uses provider-generated oEmbed HTML for social posts. |
| [Coub](https://coub.com) | active | video | iframe |  |
| [Dailymotion](https://www.dailymotion.com) | active | video | iframe, thumbnail |  |
| [Deezer](https://www.deezer.com) | active | audio | iframe | Music content only (track, album, playlist, artist); podcasts are not supported. |
| [Facebook](https://www.facebook.com) | active | social | iframe |  |
| [Flickr](https://www.flickr.com) | active | social | oEmbed | Supports provider-generated oEmbed markup for photos and videos. |
| [Giphy](https://giphy.com) | active | video | iframe, thumbnail |  |
| [Instagram](https://www.instagram.com) | active | social | iframe |  |
| [Kick](https://kick.com) | active | streaming | iframe |  |
| [Loom](https://loom.com) | active | video | iframe |  |
| [Mastodon](https://joinmastodon.org) | active | social | iframe | Federated: matches any instance host serving the /@user/id status format. |
| [Matterport](https://matterport.com) | active | 3d | iframe |  |
| [Metatube](https://www.metatube.com) | active | video | iframe |  |
| [Mixcloud](https://www.mixcloud.com) | active | audio | iframe, oEmbed |  |
| [Niconico](https://www.nicovideo.jp) | active | video | iframe |  |
| [Odysee](https://odysee.com) | active | video | iframe |  |
| [PeerTube](https://joinpeertube.org) | active | video | iframe |  |
| [Pinterest](https://www.pinterest.com) | active | social | iframe |  |
| [Reddit](https://www.reddit.com) | active | social | iframe |  |
| [Rumble](https://rumble.com) | active | video | iframe |  |
| [RuTube](https://www.rutube.ru) | active | video | iframe |  |
| [Screencast](https://www.screencast.com) | active | video | iframe, fetch |  |
| [Sketchfab](https://sketchfab.com) | active | 3d | iframe, oEmbed |  |
| [SoundCloud](https://soundcloud.com) | active | audio | iframe, oEmbed |  |
| [Speaker Deck](https://speakerdeck.com) | active | social | oEmbed | Uses provider-generated oEmbed HTML for presentations. |
| [Spotify](https://open.spotify.com) | active | audio | iframe, oEmbed |  |
| [Spreaker](https://www.spreaker.com) | active | audio | iframe, oEmbed | Episode URLs only; show pages expose no numeric ID in the URL. |
| [Streamable](https://streamable.com) | active | video | iframe |  |
| [TED](https://www.ted.com) | active | video | iframe, oEmbed |  |
| [TikTok](https://www.tiktok.com) | active | social | iframe, oEmbed |  |
| [Tumblr](https://www.tumblr.com) | active | social | oEmbed | Uses oEmbed HTML because Tumblr post embeds require provider-generated markup. |
| [Twitch Clip](https://clips.twitch.tv) | active | streaming | iframe |  |
| [Twitch Video](https://www.twitch.tv) | active | streaming | iframe |  |
| [Twitter](https://twitter.com) | active | social | iframe |  |
| [Vimeo](https://www.vimeo.com) | active | video | iframe, oEmbed |  |
| [Vooplayer](https://vooplayer.com/) | active | video | iframe |  |
| [Wistia](https://www.wistia.com) | active | video | iframe |  |
| [YouTube](https://www.youtube.com) | active | video | iframe, oEmbed, thumbnail, timestamp |  |
