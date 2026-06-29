<?php

declare(strict_types=1);

namespace MediaEmbed\Provider\Enum;

/**
 * Lifecycle status of a provider.
 */
enum Status: string {

	/**
	 * Fully supported, current URL formats. The default for new providers.
	 */
	case Active = 'active';

	/**
	 * Older URL format kept working for existing links, but not promoted for new use.
	 */
	case Legacy = 'legacy';

	/**
	 * Slated for removal; still embeds for now but may stop working in a future release.
	 */
	case Deprecated = 'deprecated';

}
