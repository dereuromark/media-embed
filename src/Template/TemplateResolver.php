<?php

declare(strict_types=1);

namespace MediaEmbed\Template;

/**
 * Template resolver for media embed URLs.
 *
 * This class handles the resolution of template placeholders like `$1`, `$2`
 * with actual values from regex matches or IDs.
 */
final class TemplateResolver {

	/**
	 * Resolve template placeholders with regex match values.
	 *
	 * Replaces `$1`, `$2`, `$3`, etc. with corresponding match values.
	 * Match index 0 is the full match, so `$1` corresponds to `$matches[0]`.
	 *
	 * @param string $template The template string containing placeholders.
	 * @param array<string> $matches Regex match array.
	 * @return string The resolved string.
	 */
	public function resolve(string $template, array $matches): string {
		$count = count($matches);

		for ($i = 1; $i <= $count; $i++) {
			$template = str_ireplace('$' . $i, $matches[$i - 1], $template);
		}

		return $template;
	}

	/**
	 * Resolve template for reverse lookup (using ID instead of match).
	 *
	 * Handles `$r2` (explicit reverse placeholder), compound IDs (when an ID template
	 * such as `$2/$3` is known, the individual capture groups are recovered from the ID
	 * value) and the simple `$2` fallback.
	 *
	 * @param string $template The template string containing placeholders.
	 * @param string $id The ID value to substitute.
	 * @param string|null $idTemplate The original ID template (e.g. `$2/$3`) when known.
	 * @return string The resolved string.
	 */
	public function resolveReverse(string $template, string $id, ?string $idTemplate = null): string {
		if (str_contains($template, '$r2')) {
			return str_replace('$r2', $id, $template);
		}

		if ($idTemplate !== null) {
			$groups = $this->extractReverseGroups($idTemplate, $id);
			if ($groups !== null) {
				// Replace higher placeholder numbers first to avoid partial overlaps.
				krsort($groups);
				foreach ($groups as $number => $value) {
					$template = str_replace('$' . $number, $value, $template);
				}

				return $template;
			}
		}

		return str_replace('$2', $id, $template);
	}

	/**
	 * Recover individual capture-group values from a compound ID using its template.
	 *
	 * Given an ID template like `$2/$3/$4` and the value `artist/album/song`, returns
	 * `[2 => 'artist', 3 => 'album', 4 => 'song']`. Returns null for single-placeholder
	 * templates (handled by the simple path) or when the value does not match the template.
	 *
	 * @param string $idTemplate The ID template containing placeholders.
	 * @param string $id The concrete ID value.
	 * @return array<int, string>|null
	 */
	protected function extractReverseGroups(string $idTemplate, string $id): ?array {
		$segments = preg_split('/(\$\d+)/', $idTemplate, -1, PREG_SPLIT_DELIM_CAPTURE);
		if ($segments === false) {
			return null;
		}

		$order = [];
		$regex = '';
		foreach ($segments as $segment) {
			if ($segment === '') {
				continue;
			}
			if (preg_match('/^\$(\d+)$/', $segment, $matches)) {
				$order[] = (int)$matches[1];
				$regex .= '(.+?)';

				continue;
			}

			$regex .= preg_quote($segment, '~');
		}

		if (count($order) < 2) {
			return null;
		}

		if (!preg_match('~^' . $regex . '$~', $id, $values)) {
			return null;
		}

		$groups = [];
		foreach ($order as $index => $number) {
			$groups[$number] = $values[$index + 1];
		}

		return $groups;
	}

	/**
	 * Resolve template with custom key-value replacements.
	 *
	 * @param string $template The template string.
	 * @param array<string, string> $replacements Key-value pairs for replacement.
	 * @return string The resolved string.
	 */
	public function resolveReplacements(string $template, array $replacements): string {
		foreach ($replacements as $search => $replace) {
			$template = str_replace($search, $replace, $template);
		}

		return $template;
	}

	/**
	 * Check if a template contains unresolved placeholders.
	 *
	 * @param string $template The template string to check.
	 * @return bool True if placeholders remain.
	 */
	public function hasUnresolvedPlaceholders(string $template): bool {
		return (bool)preg_match('/\$\d+/', $template);
	}

	/**
	 * Check if a template uses reverse placeholders.
	 *
	 * @param string $template The template string to check.
	 * @return bool True if reverse placeholder `$r2` is present.
	 */
	public function usesReversePlaceholder(string $template): bool {
		return str_contains($template, '$r2');
	}

	/**
	 * Extract placeholder numbers from a template.
	 *
	 * @param string $template The template string.
	 * @return array<int> Array of placeholder numbers found.
	 */
	public function extractPlaceholders(string $template): array {
		preg_match_all('/\$(\d+)/', $template, $matches);

		if (empty($matches[1])) {
			return [];
		}

		return array_map('intval', $matches[1]);
	}

}
