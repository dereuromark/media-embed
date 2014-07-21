<?php

namespace MediaEmbed\Object;

/**
 * ObjectInterface must be implemented by classes that are a specific type of media.
 */
interface ObjectInterface {

	/**
	 * @api
	 *
	 * @return string
	 */
	public function id();

	/**
	 * @api
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * @api
	 *
	 * return string
	 */
	public function name();

	/**
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedCode();
}
