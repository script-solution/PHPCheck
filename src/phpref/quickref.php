<?php
/**
 * Contains the quick-reference-parser
 *
 * @version			$Id: dao.php 23 2008-12-13 11:07:36Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Loads the quick-reference and finds all links to sub-pages in it
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_PHPRef_QuickRef
{
	/**
	 * The quick-reference-URL
	 * 
	 * @var string
	 */
	private $url;
	
	/**
	 * Constructor
	 * 
	 * @param string $url the URL with the quick-reference
	 */
	public function __construct($url)
	{
		$this->url = $url;
	}
	
	/**
	 * Fetches the page from the specified URL and parses it for links to functions etc.
	 * 
	 * @param string $prefix the prefix for the generated URLs (e.g. "http://php.net")
	 * @return array an array of URLs
	 * @throws PC_PHPRef_Exception if the it failed
	 */
	public function get_pages($prefix = 'http://php.net')
	{
		$comps = parse_url($this->url);
		$http = new FWS_HTTP($comps['host'],isset($comps['port']) ? $comps['port'] : 80);
		$reply = $http->get($comps['path']);
		if($reply === false)
			throw new PC_PHPRef_Exception($http->get_error_message());
		
		$pages = array();
		preg_match_all('/<a href="(\S*?)\/manual\/en\/(.*?)">.*?<\/a>/',$reply,$matches);
		foreach($matches[0] as $k => $v)
			$pages[] = $prefix.$matches[1][$k].'/manual/en/'.$matches[2][$k];
		return $pages;
	}
}
?>