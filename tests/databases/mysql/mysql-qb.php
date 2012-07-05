<?php
/**
 * Query
 *
 * Free Query Builder / Database Abstraction Layer
 *
 * @package		Query
 * @author		Timothy J. Warren
 * @copyright	Copyright (c) 2012
 * @link 		https://github.com/aviat4ion/Query
 * @license		http://philsturgeon.co.uk/code/dbad-license
 */

// --------------------------------------------------------------------------

class MySQLQBTest extends QBTest {

	public function __construct()
 	{
 		parent::__construct();

 		// Attempt to connect, if there is a test config file
		if (is_file(QBASE_DIR . "test_config.json"))
		{
			$params = json_decode(file_get_contents(QBASE_DIR . "test_config.json"));
			$params = $params->mysql;
			$params->type = "MySQL";

			$this->db = new Query_Builder($params);

			// echo '<hr /> MySQL Queries <hr />';
		}
		elseif (($var = getenv('CI')))
		{
			$params = array(
				'host' => '127.0.0.1',
				'port' => '3306',
				'database' => 'test',
				'user' => 'root',
				'pass' => NULL,
				'type' => 'mysql'
			);

			$this->db = new Query_Builder($params);
		}
		else
		{
			die("Error with mysql credentials");
		}
 	}

	// --------------------------------------------------------------------------

	public function TestExists()
	{
		$this->assertTrue(in_array('mysql', PDO::getAvailableDrivers()));
	}
}