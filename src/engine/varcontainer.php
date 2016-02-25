<?php
/**
 * Contains the var-container-class
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
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
 * Manages the variables for the statement-scanner. Additionally it keeps track of conditions
 * and loops and adjusts the types of the variables in the affected scope accordingly.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_VarContainer extends FWS_Object
{
	/**
	 * The variables
	 * 
	 * @var array
	 */
	private $vars = array(
		PC_Obj_Variable::SCOPE_GLOBAL => array()
	);
	/**
	 * Will be > 0 if we're in a loop
	 * 
	 * @var int
	 */
	private $loopdepth = 0;
	/**
	 * Will be > 0 if we're in a condition
	 * 
	 * @var int
	 */
	private $conddepth = 0;
	/**
	 * For each condition and loop a list of variables we should mark as unknown as soon
	 * as we leave the condition.
	 * 
	 * @var array
	 */
	private $layers = array();
	
	/**
	 * @return array all variables from all scopes
	 */
	public function get_all()
	{
		return $this->vars;
	}
	
	/**
	 * Checks wether $name exists in $scope
	 * 
	 * @param string $scope the scope-name
	 * @param string $name the variable-name
	 * @return bool true if so
	 */
	public function exists($scope,$name)
	{
		return isset($this->vars[$scope][$name]);
	}
	
	/**
	 * Returns the variable with given name in given scope. Assumes that it exists
	 * 
	 * @param string $scope the scope-name
	 * @param string $name the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public function get($scope,$name)
	{
		assert(isset($this->vars[$scope][$name]));
		return $this->vars[$scope][$name];
	}
	
	/**
	 * Sets the given variable in given scope
	 * 
	 * @param string $scope the scope-name
	 * @param PC_Obj_Variable $var the variable
	 */
	public function set($scope,$var)
	{
		$this->vars[$scope][$var->get_name()] = $var;
	}
	
	/**
	 * Unsets the given variable in given scope
	 * 
	 * @param string $scope the scope-name
	 * @param PC_Obj_Variable $var the variable
	 */
	public function remove($scope,$var)
	{
		unset($this->vars[$scope][$var->get_name()]);
	}
	
	/**
	 * @return bool if we're in a loop
	 */
	public function is_in_loop()
	{
		return $this->loopdepth > 0;
	}
	
	/**
	 * Enters a loop
	 */
	public function enter_loop()
	{
		array_push($this->layers,array(
			'blockno' => 0,
			'haseelse' => false,
			'elseifs' => 0,
			'vars' => array(array())
		));
		$this->loopdepth++;
	}
	
	/**
	 * Leaves a loop
	 * 
	 * @param string $file the current file
	 * @param int $line the current line
	 * @param PC_Obj_Scope $scope the current scope
	 */
	public function leave_loop($file,$line,$scope)
	{
		assert($this->loopdepth > 0);
		$this->loopdepth--;
		$this->perform_pending_changes($file,$line,$scope);
	}
	
	/**
	 * Enters a condition
	 * 
	 * @param bool $newblock wether a new block is opened in the current layer
	 * @param bool $is_else wether an T_ELSE opened this block
	 */
	public function enter_cond($newblock = false,$is_else = false)
	{
		if($newblock)
		{
			$layer = &$this->layers[count($this->layers) - 1];
			$layer['haselse'] = $is_else;
			$layer['blockno']++;
			$layer['vars'][] = array();
		}
		else
		{
			array_push($this->layers,array(
				'blockno' => 0,
				'elseifs' => 0,
				'haselse' => false,
				'vars' => array(array())
			));
			$this->conddepth++;
		}
	}
	
	/**
	 * Leaves a condition
	 * 
	 * @param string $file the current file
	 * @param int $line the current line
	 * @param PC_Obj_Scope $scope the current scope
	 */
	public function leave_cond($file,$line,$scope)
	{
		assert($this->conddepth > 0);
		if($this->layers[count($this->layers) - 1]['elseifs']-- == 0)
		{
			$this->conddepth--;
			$this->perform_pending_changes($file,$line,$scope);
		}
	}
	
	/**
	 * Increases the number of else-ifs for the current layer
	 */
	public function set_elseif()
	{
		$this->layers[count($this->layers) - 1]['elseifs']++;
		$this->layers[count($this->layers) - 1]['haselse'] = false;
	}
	
	/**
	 * Creates a backup of the given variable, if necessary
	 * 
	 * @param PC_Obj_Variable $var the variable
	 * @param PC_Obj_Scope $scope the current scope
	 */
	public function backup($var,$scope)
	{
		if(count($this->layers) > 0)
		{
			$varname = $var->get_name();
			if($varname)
			{
				$layer = &$this->layers[count($this->layers) - 1];
				$blockno = $layer['blockno'];
				if(!isset($layer['vars'][$blockno][$varname]))
				{
					// don't use null because isset() is false if the value is null
					$clone = isset($this->vars[$scope->get_name()][$varname]) ? clone $var : 0;
					$layer['vars'][$blockno][$varname] = $clone;
				}
			}
			else
			{
				// if its an array-element, simply set it to unknown
				$var->set_type(new PC_Obj_MultiType());
			}
		}
	}
	
	/**
	 * Performs the required actions when leaving a loop/condition
	 * 
	 * @param string $file the current file
	 * @param int $line the current line
	 * @param PC_Obj_Scope $scope the current scope
	 */
	private function perform_pending_changes($file,$line,$scope)
	{
		$layer = array_pop($this->layers);
		// if there is only one block (loops, if without else)
		if(count($layer['vars']) == 1)
		{
			// its never present in all blocks here since we never have an else-block
			foreach($layer['vars'][0] as $name => $var)
				$this->change_var($file,$line,$scope,$layer,$name,$var,false);
		}
		else
		{
			// otherwise there were multiple blocks (if-elseif-else, ...)
			// we start with the variables in the first block; vars that are not present there, will
			// be added later
			$changed = array();
			foreach($layer['vars'] as $blockno => $vars)
			{
				foreach($vars as $name => $var)
				{
					if(!isset($changed[$name]))
					{
						// check if the variable is present in all blocks. this is not the case if we have no
						// else-block or if has not been assigned in at least one block
						$present = false;
						// we need to check this only in the first block, since if we're in the second block
						// and don't have changed this var yet (see isset above), it is at least not present
						// in the first block.
						if($blockno == 0 && $layer['haselse'])
						{
							$present = true;
							for($i = 1; $i <= $layer['blockno']; $i++)
							{
								if(!isset($layer['vars'][$i][$name]))
								{
									$present = false;
									break;
								}
							}
						}
						$this->change_var($file,$line,$scope,$layer,$name,$var,$present);
						$changed[$name] = true;
					}
				}
			}
		}
	}
	
	/**
	 * Changes the variable with given name in the current scope
	 * 
	 * @param string $file the current file
	 * @param int $line the current line
	 * @param PC_Obj_Scope $scope the current scope
	 * @param array $layer the current layer
	 * @param string $name the var-name
	 * @param PC_Obj_Variable $backup the backup (0 if not present before the layer)
	 * @param bool $present wether its present in all blocks in this layer
	 */
	private function change_var($file,$line,$scope,$layer,$name,$backup,$present)
	{
		$scopename = $scope->get_name();
		// if its present in all blocks, merge the types
		if($present)
		{
			// start with the type in scope; thats the one from the last block
			$mtype = $this->vars[$scopename][$name]->get_type();
			// don't include the first block since thats the backup from the previous layer
			for($i = 1; $i <= $layer['blockno']; $i++)
				$mtype->merge($layer['vars'][$i][$name]->get_type());
			// note that this may discard the old value, if the variable was present
			$this->vars[$scopename][$name] = new PC_Obj_Variable(
				$file,$line,$name,$mtype,$scope->get_name_of(T_FUNC_C),$scope->get_name_of(T_CLASS_C)
			);
		}
		// if it was present before, we know that it is either the old or one of the new ones
		else if($backup !== 0)
		{
			$mtype = $this->vars[$scopename][$name]->get_type();
			for($i = 0; $i <= $layer['blockno']; $i++)
			{
				if(isset($layer['vars'][$i][$name]))
					$mtype->merge($layer['vars'][$i][$name]->get_type());
			}
		}
		// otherwise the type is unknown
		else
		{
			if(!isset($this->vars[$scopename][$name]))
			{
				$this->vars[$scopename][$name] = new PC_Obj_Variable(
					$file,$line,$name,new PC_Obj_MultiType(),
					$scope->get_name_of(T_FUNC_C),$scope->get_name_of(T_CLASS_C)
				);
			}
			else
				$this->vars[$scopename][$name]->set_type(new PC_Obj_MultiType());
		}
		
		// if there is a previous layer and the var is not known there in the last block, put
		// the first backup from this block in it. because this is the previous value for the previous
		// block, if it hasn't been assigned there
		if(count($this->layers) > 0)
		{
			$prevlayer = &$this->layers[count($this->layers) - 1];
			if(!isset($prevlayer['vars'][$prevlayer['blockno']][$name]))
				$prevlayer['vars'][$prevlayer['blockno']][$name] = $backup;
		}
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
