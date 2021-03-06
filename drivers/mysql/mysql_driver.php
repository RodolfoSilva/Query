<?php
/**
 * Query
 *
 * Free Query Builder / Database Abstraction Layer
 *
 * @package		Query
 * @author		Timothy J. Warren
 * @copyright	Copyright (c) 2012 - 2014
 * @link 		https://github.com/aviat4ion/Query
 * @license		http://philsturgeon.co.uk/code/dbad-license
 */

// --------------------------------------------------------------------------

namespace Query\Driver;

/**
 * MySQL specific class
 *
 * @package Query
 * @subpackage Drivers
 */
class MySQL extends Abstract_Driver {

	/**
	 * Set the backtick as the MySQL escape character
	 *
	 * @var string
	 */
	protected $escape_char = '`';

	/**
	 * Connect to MySQL Database
	 *
	 * @param string $dsn
	 * @param string $username
	 * @param string $password
	 * @param array $options
	 */
	public function __construct($dsn, $username=null, $password=null, array $options=array())
	{
		// Set the charset to UTF-8
		if (defined('\\PDO::MYSQL_ATTR_INIT_COMMAND'))
		{
			$options = array_merge($options, array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF-8 COLLATE 'UTF-8'",
			));
		}

		if (strpos($dsn, 'mysql') === FALSE) $dsn = 'mysql:'.$dsn;

		parent::__construct($dsn, $username, $password, $options);
	}

	// --------------------------------------------------------------------------

	/**
	 * Empty a table
	 *
	 * @param string $table
	 */
	public function truncate($table)
	{
		$table = $this->prefix_table($table);
		$this->query("TRUNCATE `{$table}`");
	}
}
//End of mysql_driver.php