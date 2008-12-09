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

/**
 * Is used to store the properties of a method / function
 *
 */
class PC_Method extends PC_Modifiable implements PC_Visible
{
	/**
	 * The visibility
	 *
	 * @var string
	 */
	private $visibility = self::V_PUBLIC;
	
	/**
	 * Wether the method is static
	 *
	 * @var boolean
	 */
	private $static = false;
	
	/**
	 * The return-type
	 *
	 * @var PC_Type
	 */
	private $return;
	
	/**
	 * An array of parameter
	 * 
	 * @var array
	 */
	private $params;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 */
	public function __construct($file,$line)
	{
		parent::__construct($file,$line);
		
		$this->params = array();
		$this->return = PC_Type::$UNKNOWN;
	}
	
	/**
	 * @return boolean wether the method is static
	 */
	public function is_static()
	{
		return $this->static;
	}
	
	/**
	 * Sets wether the method is static
	 *
	 * @param boolean $static the new value
	 */
	public function set_static($static)
	{
		$this->static = (bool)$static;
	}
	
	/**
	 * @see PC_Visible::get_visibility()
	 * 
	 * @return string
	 */
	public function get_visibility()
	{
		return $this->visibility;
	}
	
	/**
	 * @see PC_Visible::set_visibity()
	 *
	 * @param string $visibility
	 */
	public function set_visibity($visibility)
	{
		$valid = array(self::V_PUBLIC,self::V_PROTECTED,self::V_PRIVATE);
		if(!in_array($visibility,$valid))
			FWS_Helper::def_error('inarray','visibility',$valid,$visibility);
		
		$this->visibility = $visibility;
	}
	
	/**
	 * @return PC_Type the return-type
	 */
	public function get_return_type()
	{
		return $this->return;
	}
	
	/**
	 * Sets the return-type of this method
	 *
	 * @param PC_Type $type the new value
	 */
	public function set_return_type($type)
	{
		if(!($type instanceof PC_Type))
			FWS_Helper::def_error('instance','type','PC_Type',$type);
		
		$this->return = $type;
	}
	
	/**
	 * Puts the parameter to the method
	 *
	 * @param PC_Parameter $param the param
	 */
	public function put_param($param)
	{
		if(!($param instanceof PC_Parameter))
			FWS_Helper::def_error('instance','param','PC_Parameter',$param);
		
		$this->params[$param->get_name()] = $param;
	}
	
	/**
	 * @return int the total number of params (including optional ones)
	 */
	public function get_param_count()
	{
		return count($this->params);
	}
	
	/**
	 * Determines the number of required parameters
	 *
	 * @return int the number
	 */
	public function get_required_param_count()
	{
		$n = 0;
		foreach($this->params as $param)
		{
			if(!$param->is_optional())
				$n++;
		}
		return $n;
	}
	
	/**
	 * @return array associative array with the parameter
	 */
	public function get_params()
	{
		return $this->params;
	}
	
	/**
	 * Returns the parameter with given name
	 *
	 * @param string $name the param-name
	 * @return PC_Parameter the param or null
	 */
	public function get_param($name)
	{
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * Checks wether the parameter with given name exists
	 *
	 * @param string $name the param-name
	 * @return boolean true if so
	 */
	public function contains_param($name)
	{
		return isset($this->params[$name]);
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
	
	public function __ToString()
	{
		$str = $this->get_visibility().' ';
		if($this->is_static())
			$str .= 'static ';
		if($this->is_abstract())
			$str .= 'abstract ';
		if($this->is_final())
			$str .= 'final ';
		$str .= 'function <b>'.$this->get_name().'</b>(';
		$str .= implode(', ',$this->get_params());
		$str .= ')';
		return $str;
	}
}
?>