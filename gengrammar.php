<?php
/**
 * Generates the base grammar from the official PHP grammar.
 * 
 * @package			PHPCheck
 * @subpackage	main
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

$in = fopen('php://stdin','r');
if(!$in)
	error("Unable to open stdin");

$out = fopen('php://stdout','w');
if(!$out)
	error("Unable to open stdout");

$map = array(
	',' => 'COMMA',
	'=' => 'EQUALS',
	'?' => 'QUESTION',
	':' => 'COLON',
	'|' => 'BAR',
	'^' => 'CARAT',
	'&' => 'AMPERSAND',
	'<' => 'LESSTHAN',
	'>' => 'GREATERTHAN',
	'+' => 'PLUS',
	'-' => 'MINUS',
	'.' => 'DOT',
	'*' => 'TIMES',
	'/' => 'DIVIDE',
	'%' => 'PERCENT',
	'!' => 'EXCLAM',
	'~' => 'TILDE',
	'@' => 'AT',
	'[' => 'LBRACKET',
	'(' => 'LPAREN',
	')' => 'RPAREN',
	';' => 'SEMI',
	'{' => 'LCURLY',
	'}' => 'RCURLY',
	'`' => 'BACKQUOTE',
	'$' => 'DOLLAR',
	']' => 'RBRACKET',
	'"' => 'DOUBLEQUOTE',
	"'" => 'SINGLEQUOTE',
);

function get_token($f)
{
	$str = '';
	while(ctype_space($c = fgetc($f)))
		;
	$str .= $c;
	$last = $c;
	while(!ctype_space($c = fgetc($f)))
	{
		// they don't need to be separated by whitespace
		if($last != "'" && ($c == '{' || $c == '}' || $c == ';' || $c == '|'))
			break;
		$str .= $c;
		$last = $c;
	}
	return $str;
}

$sec = 0;
while($sec < 2 && ($line = fgets($in)) !== false)
{
	if(preg_match('/^%(left|right|nonassoc)(.*)$/',$line,$m))
	{
		fputs($out,'%'.$m[1]);
		
		$tokens = preg_split('/\\s+/',$m[2]);
		foreach($tokens as $tok)
		{
			if(preg_match("/^'(.)'$/",$tok,$m))
				fputs($out,$map[$m[1]].' ');
			else
				fputs($out,$tok.' ');
		}
		fputs($out,".\n");
	}
	else if(preg_match('/^%%/',$line))
		$sec++;
	else if(preg_match('/^([a-z0-9_]+):/',$line,$m))
	{
		$in_rule = true;
		$rule_name = $m[1];
		fputs($out,"\n".$rule_name.' ::= ');
		
		$depth = 0;
		$next_prec = false;
		$prec_sym = false;
		while(true)
		{
			$tok = get_token($in);
			
			// ignore everything between { ... }
			if($tok == '{')
			{
				$depth++;
				continue;
			}
			if($depth > 0 && $tok == '}')
			{
				$depth--;
				continue;
			}
			else if($depth > 0)
				continue;
			
			if($tok == ';')
				break;
			
			if($tok == '|')
			{
				fputs($out,".");
				if($prec_sym)
					fputs($out,' ['.$prec_sym.']');
				fputs($out,"\n");
				$prec_sym = false;
				
				fputs($out,$rule_name.' ::= ');
			}
			else
			{
				if($tok == '%prec')
				{
					$next_prec = true;
					continue;
				}
				else if(preg_match("/^'(.)'$/",$tok,$m))
					fputs($out,$map[$m[1]]);
				else
					fputs($out,$tok);
				
				if($next_prec)
				{
					$prec_sym = $tok;
					$next_prec = false;
				}
				fputs($out,' ');
			}
		}
		
		fputs($out,".\n");
	}
}

fclose($in);
?>
