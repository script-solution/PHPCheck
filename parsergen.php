<?php
if($argc != 2)
	exit("Usage: php ".$argv[0]." <file>\n");

require_once 'PHP/ParserGenerator.php';
$a = new PHP_ParserGenerator;

$_SERVER['argv'] = array('lemon', '-s', $argv[1]);

$a->main();
?>