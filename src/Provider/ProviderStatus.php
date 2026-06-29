<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

/**
 * Lifecycle status of a provider.
 */
enum ProviderStatus: string {

	case Active = 'active';

	case Legacy = 'legacy';

	case Deprecated = 'deprecated';

}
