<?php
/**
 * Contains the file-storage-class
 * 
 * @package			PHPCheck
 * @subpackage	src.session
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
 * The session-storage implementation for a file
 * Note that we can't use PHP-sessions in this case because PHP locks a session until a request
 * is done. I.e. the job-stuff would not work because we have a job-control-request that runs
 * until all is finished and many job-state-requests that ask for the current state.
 *
 * @package			PHPCheck
 * @subpackage	src.session
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Session_Storage_File extends FWS_Object implements FWS_Session_Storage
{
	/**
	 * The file for the session-data
	 */
	const SESS_FILE = 'cache/session.txt';
	
	/**
	 * @see FWS_Session_Storage::load_list()
	 *
	 * @return array
	 */
	public function load_list()
	{
		if(!is_file(self::SESS_FILE))
			return array();
		
		$content = file_get_contents(self::SESS_FILE);
		if($content == '')
			return array();
		$data = unserialize($content);
		return array(new FWS_Session_Data(
			$data['sid'],$data['uid'],$data['uip'],$data['uname'],$data['date'],$data['uagent'],$data['data']
		));
	}

	/**
	 * @see FWS_Session_Storage::add_user()
	 *
	 * @param FWS_Session_Data $user
	 */
	public function add_user($user)
	{
		$this->update_user($user);
	}

	/**
	 * @see FWS_Session_Storage::get_new_user()
	 *
	 * @return FWS_Session_Data
	 */
	public function get_new_user()
	{
		return new FWS_Session_Data();
	}

	/**
	 * @see FWS_Session_Storage::remove_user()
	 *
	 * @param array $ids
	 */
	public function remove_user($ids)
	{
		file_put_contents(self::SESS_FILE,'');
	}

	/**
	 * @see FWS_Session_Storage::update_user()
	 *
	 * @param FWS_Session_Data $user
	 */
	public function update_user($user)
	{
		$data = array(
			'sid' => $user->get_session_id(),
			'uid' => $user->get_user_id(),
			'uip' => $user->get_user_ip(),
			'uname' => $user->get_user_name(),
			'date' => $user->get_date(),
			'uagent' => $user->get_user_agent(),
			'data' => $user->get_session_data()
		);
		file_put_contents(self::SESS_FILE,serialize($data));
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
