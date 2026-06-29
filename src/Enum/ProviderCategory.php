<?php

declare(strict_types=1);

namespace MediaEmbed\Enum;

/**
 * Content category of a provider.
 */
enum ProviderCategory: string {

	case ThreeD = '3d';

	case Audio = 'audio';

	case Social = 'social';

	case Streaming = 'streaming';

	case Video = 'video';

}
