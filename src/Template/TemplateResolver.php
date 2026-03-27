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
	 * Handles both `$r2` (explicit reverse placeholder) and `$2` (fallback).
	 *
	 * @param string $template The template string containing placeholders.
	 * @param string $id The ID to substitute.
	 * @return string The resolved string.
	 */
	public function resolveReverse(string $template, string $id): string {
		if (strpos($template, '$r2') !== false) {
			return str_replace('$r2', $id, $template);
		}

		return str_replace('$2', $id, $template);
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
		return strpos($template, '$r2') !== false;
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
