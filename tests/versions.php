<?php
/**
 * Tests arrays
 * 
 * @package			PHPCheck
 * @subpackage	tests
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

class PC_Tests_Versions extends PC_UnitTest
{
	public function test_php_versions()
	{
		$code = '<?php
cyrus_close(null);
?>';

		$options = new PC_Engine_Options();
		$options->set_use_db(true);
		$options->set_use_phpref(true);
		$options->add_min_req('PHP','4.0.0');
		$options->add_max_req('PHP','5.1.0');
		$options->add_min_req('PECL cyrus','1.0.0');
		list(,,,,$errors) = $this->analyze($code,$options);
		
		self::assert_equals(2,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_REQUIRES_NEWER,$error->get_type());
		self::assert_equals(
			'cyrus_close(unknown) in "", line 2 requires PHP >= 4.1.0, but you target PHP >= 4.0.0',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_REQUIRES_OLDER,$error->get_type());
		self::assert_equals(
			'cyrus_close(unknown) in "", line 2 exists only till PHP 5, but you target PHP < 5.1.0',
			$error->get_msg()
		);
	}
	
	public function test_pecl_versions()
	{
		$options = new PC_Engine_Options();
		$options->set_use_db(true);
		$options->set_use_phpref(true);
		
		$code = '<?php
$pdo = new PDO("host","login","pw");

$sth = $pdo->prepare("SELECT * FROM mytable");
$count = $sth->columnCount();
?>';

		$opt = clone $options;
		$opt->add_min_req('PHP','5.2.0');
		$opt->add_max_req('PHP','8.0.0');
		list(,,,,$errors) = $this->analyze($code,$opt);
		
		self::assert_equals(3,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_REQUIRES_NEWER,$error->get_type());
		self::assert_equals(
			'PDO->__construct(string=host, string=login, string=pw) in "", line 2 requires PECL pdo >= 0.1.0',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_REQUIRES_NEWER,$error->get_type());
		self::assert_equals(
			'PDO->prepare(string=SELECT * FROM mytable) in "", line 4 requires PECL pdo >= 0.1.0',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_REQUIRES_NEWER,$error->get_type());
		self::assert_equals(
			'PDOStatement->columnCount() in "", line 5 requires PECL pdo >= 0.2.0',
			$error->get_msg()
		);
		
		$code = '<?php
$pdo = new PDO("host","login","pw");

$sth = $pdo->prepare("SELECT * FROM mytable");
$count = $sth->columnCount();
?>';

		$opt = clone $options;
		$opt->add_min_req('PHP','5.2.0');
		$opt->add_max_req('PHP','8.0.0');
		$opt->add_min_req('PECL pdo','0.1.0');
		list(,,,,$errors) = $this->analyze($code,$opt);
		
		self::assert_equals(1,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_REQUIRES_NEWER,$error->get_type());
		self::assert_equals(
			'PDOStatement->columnCount() in "", line 5 requires PECL pdo >= 0.2.0, but you target PECL pdo >= 0.1.0',
			$error->get_msg()
		);
	}
}
