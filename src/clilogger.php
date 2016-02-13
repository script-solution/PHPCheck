<?php
/**
 * Contains the logger-class for the CLI-stuff
 * 
 * @package			PHPCheck
 * @subpackage	src
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Implements a logger that reports the errors through the shared data so that the users
 * can see them.
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLILogger implements FWS_Error_Logger
{
	/**
	 * @see FWS_Error_Logger::log()
	 *
	 * @param int $no
	 * @param string $msg
	 * @param string $file
	 * @param int $line
	 * @param array $backtrace
	 */
	public function log($no,$msg,$file,$line,$backtrace)
	{
		// build error-message
		$msg .= ' in file "'.$file.'", line '.$line.'<br />';
		$btpr = new FWS_Error_BTPrinter_HTML();
		$msg .= $btpr->print_backtrace($backtrace);

		// write stuff to shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->add_error($msg);
		$mutex->write(serialize($data));
		$mutex->close();
	}
}
