<?php

namespace MediaEmbed\Object;

/**
 * ObjectInterface must be implemented by classes that are a specific type of media.
 */
interface ObjectInterface {

	/**
	 * @api
	 *
	 * @param string|Text $content
	 * @param array       $options
	 *
	 * @return string
	 */
	public function id();

	/**
	 * @api
	 *
	 * @param string|Text $content
	 * @param array       $options
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * @api
	 *
	 * @param string|Text $content
	 * @param array       $options
	 *
	 * @return string
	 */
	public function name();

	/**
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedCode();
}
