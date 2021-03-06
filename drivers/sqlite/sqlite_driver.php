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
 * SQLite specific class
 *
 * @package Query
 * @subpackage Drivers
 */
class SQLite extends Abstract_Driver {

	/**
	 * Reference to the last executed sql query
	 *
	 * @var PDOStatement
	 */
	protected $statement;

	/**
	 * Open SQLite Database
	 *
	 * @param string $dsn
	 * @param string $user
	 * @param string $pass
	 * @param array $driver_options
	 */
	public function __construct($dsn, $user=NULL, $pass=NULL, array $driver_options=array())
	{
		// DSN is simply `sqlite:/path/to/db`
		parent::__construct("sqlite:{$dsn}", $user, $pass);
	}

	// --------------------------------------------------------------------------

	/**
	 * Empty a table
	 *
	 * @param string $table
	 */
	public function truncate($table)
	{
		// SQLite has a TRUNCATE optimization,
		// but no support for the actual command.
		$sql = 'DELETE FROM "'.$table.'"';

		$this->statement = $this->query($sql);

		return $this->statement;
	}

	// --------------------------------------------------------------------------

	/**
	 * List tables for the current database
	 *
	 * @return mixed
	 */
	public function get_tables()
	{
		$sql = $this->sql->table_list();

		$res = $this->query($sql);
		return db_filter($res->fetchAll(\PDO::FETCH_ASSOC), 'name');
	}

	// --------------------------------------------------------------------------

	/**
	 * Create sql for batch insert
	 *
	 * @codeCoverageIgnore
	 * @param string $table
	 * @param array $data
	 * @return string
	 */
	public function insert_batch($table, $data=array())
	{
		// If greater than version 3.7.11, supports the same syntax as
		// MySQL and Postgres
		if (version_compare($this->getAttribute(\PDO::ATTR_SERVER_VERSION), '3.7.11', '>='))
		{
			return parent::insert_batch($table, $data);
		}

		// --------------------------------------------------------------------------
		// Otherwise, do a union query as an analogue to a 'proper' batch insert
		// --------------------------------------------------------------------------

		// Each member of the data array needs to be an array
		if ( ! is_array(current($data))) return NULL;

		// Start the block of sql statements
		$table = $this->quote_table($table);
		$sql = "INSERT INTO {$table} \n";

		// Create a key-value mapping for each field
		$first = array_shift($data);
		$cols = array();
		foreach($first as $colname => $datum)
		{
			$cols[] = $this->_quote($datum) . ' AS ' . $this->quote_ident($colname);
		}
		$sql .= "SELECT " . implode(', ', $cols) . "\n";

		foreach($data as $union)
		{
			$vals = array_map(array($this, 'quote'), $union);
			$sql .= "UNION SELECT " . implode(',', $vals) . "\n";
		}

		return array($sql, NULL);
	}
}
//End of sqlite_driver.php