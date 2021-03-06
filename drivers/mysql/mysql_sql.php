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
 * MySQL specifc SQL
 *
 * @package Query
 * @subpackage Drivers
 */
class MySQL_SQL extends Abstract_SQL {

	/**
	 * Limit clause
	 *
	 * @param string $sql
	 * @param int $limit
	 * @param int $offset
	 * @return string
	 */
	public function limit($sql, $limit, $offset=FALSE)
	{
		if ( ! is_numeric($offset))
		{
			return $sql." LIMIT {$limit}";
		}

		return $sql." LIMIT {$offset}, {$limit}";
	}

	// --------------------------------------------------------------------------

	/**
	 * Get the query plan for the sql query
	 *
	 * @param string $sql
	 * @return string
	 */
	public function explain($sql)
	{
		return "EXPLAIN EXTENDED {$sql}";
	}

	// --------------------------------------------------------------------------

	/**
	 * Random ordering keyword
	 *
	 * @return string
	 */
	public function random()
	{
		return ' RAND() DESC';
	}

	// --------------------------------------------------------------------------

	/**
	 * Returns sql to list other databases
	 *
	 * @return string
	 */
	public function db_list()
	{
		return "SHOW DATABASES WHERE `Database` NOT IN ('information_schema','mysql')";
	}

	// --------------------------------------------------------------------------

	/**
	 * Returns sql to list tables
	 *
	 * @param string $database
	 * @return string
	 */
	public function table_list($database='')
	{
		if ( ! empty($database)) return "SHOW TABLES FROM `{$database}`";

		return 'SHOW TABLES';
	}

	// --------------------------------------------------------------------------

	/**
	 * Overridden in MySQL class
	 *
	 * @return string
	 */
	public function system_table_list()
	{
		return 'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES`
			WHERE `TABLE_SCHEMA`=\'information_schema\'';
	}

	// --------------------------------------------------------------------------

	/**
	 * Returns sql to list views
	 *
	 * @return string
	 */
	public function view_list()
	{
		return 'SELECT `table_name` FROM `information_schema`.`views`';
	}

	// --------------------------------------------------------------------------

	/**
	 * Returns sql to list triggers
	 *
	 * @return string
	 */
	public function trigger_list()
	{
		return 'SHOW TRIGGERS';
	}

	// --------------------------------------------------------------------------

	/**
	 * Return sql to list functions
	 *
	 * @return string
	 */
	public function function_list()
	{
		return 'SHOW FUNCTION STATUS';
	}

	// --------------------------------------------------------------------------

	/**
	 * Return sql to list stored procedures
	 *
	 * @return string
	 */
	public function procedure_list()
	{
		return 'SHOW PROCEDURE STATUS';
	}

	// --------------------------------------------------------------------------

	/**
	 * Return sql to list sequences
	 *
	 * @return NULL
	 */
	public function sequence_list()
	{
		return NULL;
	}

	// --------------------------------------------------------------------------

	/**
	 * SQL to show list of field types
	 *
	 * @return string
	 */
	public function type_list()
	{
		return "SELECT DISTINCT `DATA_TYPE` FROM `information_schema`.`COLUMNS`";
	}

	// --------------------------------------------------------------------------

	/**
	 * SQL to show infromation about columns in a table
	 *
	 * @param string $table
	 * @return string
	 */
	public function column_list($table)
	{
		return "SHOW FULL COLUMNS FROM {$table}";
	}

	// --------------------------------------------------------------------------

	/**
	 * Get the list of foreign keys for the current
	 * table
	 *
	 * @parma string $table
	 * @return string
	 */
	public function fk_list($table)
	{
		return <<<SQL
			SELECT `TABLE_NAME`,`COLUMN_NAME`,`CONSTRAINT_NAME`,
			`REFERENCED_TABLE_NAME`,`REFERENCED_COLUMN_NAME`
			FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
			WHERE `REFERENCED_TABLE_NAME` = '{$table}';
SQL;
	}

	// --------------------------------------------------------------------------

	/**
	 * Get the list of indexes for the current table
	 *
	 * @param string $table
	 * @return array
	 */
	public function index_list($table)
	{
		return "SHOW INDEX IN {$table}";
	}
}
//End of mysql_sql.php