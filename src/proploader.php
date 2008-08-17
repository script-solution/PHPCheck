<?php
/**
 * Contains the prop-loader-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The property-loader for phpcheck
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_PropLoader extends FWS_PropLoader
{
	/**
	 * @see FWS_PropLoader::doc()
	 *
	 * @return PC_Document
	 */
	protected function doc()
	{
		return new PC_Document();
	}
}
?>