<?php
/**
 * Tests try-catch statements
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

class PC_Tests_TryCatch extends PC_UnitTest
{
	public function testTryCatch()
	{
		$code = '<?php
try {
	echo "foo";
}
catch(Exception $e) {
	myfunc($e);
	echo "bar";
}
?>';
		
		list(,,$vars,$calls,,) = $this->analyze($code);
		
		self::assertEquals('myfunc(Exception)',(string)$calls[0]->get_call(false,false));
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$global['e']->get_type());
	}
}
