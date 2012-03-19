<?php
/**
 * Query
 *
 * Free Query Builder / Database Abstraction Layer
 *
 * @author 		Timothy J. Warren
 * @copyright	Copyright (c) 2012
 * @link 		https://github.com/aviat4ion/Query
 * @license 	http://philsturgeon.co.uk/code/dbad-license 
 */

// --------------------------------------------------------------------------

class MySQLQBTest extends QBTest {

	function __construct()
 	{
 		parent::__construct();
 		
 		// Attempt to connect, if there is a test config file
		if (is_file("../test_config.json"))
		{
			$params = json_decode(file_get_contents("../test_config.json"));
			$params = $params->mysql;
			$params->type = "mysql";
			
			$this->db = new Query_Builder($params);
			
			// echo '<hr /> MySQL Queries <hr />';	
		}
 	}

	
	function TestExists()
	{
		$this->assertTrue(in_array('mysql', pdo_drivers()));
	}
}