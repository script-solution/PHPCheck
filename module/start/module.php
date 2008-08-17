<?php
/**
 * TODO: describe the file
 *
 * @version			$Id$
 * @package			Boardsolution
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

final class PC_Module_Start extends FWS_Module
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		$doc->use_default_renderer();
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		
	}
}
?>