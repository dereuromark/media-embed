<?php

declare(strict_types=1);

namespace MediaEmbed\Provider\Enum;

/**
 * Content category of a provider.
 */
enum Category: string {

	/**
	 * Interactive 3D / model viewers (e.g. Sketchfab).
	 */
	case ThreeD = '3d';

	/**
	 * Audio-only players (music, podcasts).
	 */
	case Audio = 'audio';

	/**
	 * Social network posts/embeds.
	 */
	case Social = 'social';

	/**
	 * Live or on-demand streaming platforms.
	 */
	case Streaming = 'streaming';

	/**
	 * Video players (the default content type).
	 */
	case Video = 'video';

}
