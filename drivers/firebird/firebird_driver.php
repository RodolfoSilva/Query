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

use Query\Table\Table_Builder;

// --------------------------------------------------------------------------

/**
 * Firebird Database class
 *
 * PDO-firebird isn't stable, so this is a wrapper of the fbird_ public functions.
 *
 * @package Query
 * @subpackage Drivers
 */
class Firebird extends Abstract_Driver {

	/**
	 * Reference to the last query executed
	 *
	 * @var object
	 */
	protected $statement = NULL;

	/**
	 * Reference to the resource returned by
	 * the last query executed
	 *
	 * @var resource
	 */
	protected $statement_link = NULL;

	/**
	 * Reference to the current transaction
	 *
	 * @var resource
	 */
	protected $trans = NULL;

	/**
	 * Reference to the connection resource
	 *
	 * @var resource
	 */
	protected $conn = NULL;

	/**
	 * Reference to the service resource
	 *
	 * @var resource
	 */
	protected $service = NULL;

	/**
	 * Open the link to the database
	 *
	 * @param string $dbpath
	 * @param string $user
	 * @param string $pass
	 * @param array $options
	 */
	public function __construct($dbpath, $user='SYSDBA', $pass='masterkey', array $options = array())
	{

		$connect_function = (isset($options[\PDO::ATTR_PERSISTENT]) && $options[\PDO::ATTR_PERSISTENT] == TRUE)
			? '\\fbird_pconnect'
			: '\\fbird_connect';

		$this->conn = $connect_function($dbpath, $user, $pass, 'utf-8', 0);
		$this->service = \fbird_service_attach('localhost', $user, $pass);

		// Throw an exception to make this match other pdo classes
		if ( ! \is_resource($this->conn)) throw new \PDOException(\fbird_errmsg(), \fbird_errcode(), NULL);

		// Load these classes here because this
		// driver does not call the constructor
		// of DB_PDO, which defines these
		// class variables for the other drivers

		// Load the sql class
		$class = __CLASS__."_sql";
		$this->sql = new $class();

		// Load the util class
		$class = __CLASS__."_util";
		$this->util = new $class($this);

		// Load the table builder class
		$this->table = new Table_Builder('', array(), $this);
	}

	// --------------------------------------------------------------------------

	/**
	 * Cleanup some loose ends
	 * @codeCoverageIgnore
	 */
	public function __destruct()
	{
		\fbird_service_detach($this->service);
	}

	// --------------------------------------------------------------------------

	/**
	 * Return service handle
	 *
	 * @return resource
	 */
	public function get_service()
	{
		return $this->service;
	}

	// --------------------------------------------------------------------------

	/**
	 * Empty a database table
	 *
	 * @param string $table
	 */
	public function truncate($table)
	{
		// Firebird lacks a truncate command
		$sql = 'DELETE FROM '.$this->quote_table($table);
		$this->statement = $this->query($sql);
	}

	// --------------------------------------------------------------------------

	/**
	 * Execute an sql statement and return number of affected rows
	 *
	 * @param string $sql
	 * @return int
	 */
	public function exec($sql)
	{
		return NULL;
	}

	// --------------------------------------------------------------------------

	/**
	 * Implement for compatibility with PDO
	 *
	 * @param int $attribute
	 * @return mixed
	 */
	public function getAttribute($attribute)
	{
		return NULL;
	}

	// --------------------------------------------------------------------------

	/**
	 * Return whether the current statement is in a transaction
	 *
	 * @return bool
	 */
	public function inTransaction()
	{
		return ! is_null($this->trans);
	}

	// --------------------------------------------------------------------------

	/**
	 * Returns the last value of the specified generator
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function lastInsertId($name = NULL)
	{
		return \fbird_gen_id($name, 0, $this->conn);
	}

	// --------------------------------------------------------------------------

	/**
	 * Wrapper public function to better match PDO
	 *
	 * @param string $sql
	 * @return Firebird_Result
	 * @throws PDOException
	 */
	public function query($sql = '')
	{

		if (empty($sql)) throw new \PDOException("Query method requires an sql query!", 0, NULL);

		$this->statement_link = (isset($this->trans))
			? \fbird_query($this->trans, $sql)
			: \fbird_query($this->conn, $sql);

		// Throw the error as a exception
		$err_string = \fbird_errmsg() . "Last query:" . $this->last_query;
		if ($this->statement_link === FALSE) throw new \PDOException($err_string, \fbird_errcode(), NULL);

		$this->statement = new FireBird_Result($this->statement_link, $this);

		return $this->statement;
	}

	// --------------------------------------------------------------------------

	/**
	 * Emulate PDO prepare
	 *
	 * @param string $query
	 * @param array $options
	 * @return Firebird_Result
	 * @throws PDOException
	 */
	public function prepare($query, $options=array())
	{
		$this->statement_link = \fbird_prepare($this->conn, $query);

		// Throw the error as an exception
		if ($this->statement_link === FALSE) throw new \PDOException(\fbird_errmsg(), \fbird_errcode(), NULL);

		$this->statement = new FireBird_Result($this->statement_link, $this);

		return $this->statement;
	}

	// --------------------------------------------------------------------------

	/**
	 * Start a database transaction
	 *
	 * @return boolean|null
	 */
	public function beginTransaction()
	{
		return (($this->trans = \fbird_trans($this->conn)) !== NULL) ? TRUE : NULL;
	}

	// --------------------------------------------------------------------------

	/**
	 * Commit a database transaction
	 *
	 * @return bool
	 */
	public function commit()
	{
		$res = \fbird_commit($this->trans);
		$this->trans = NULL;
		return $res;
	}

	// --------------------------------------------------------------------------

	/**
	 * Rollback a transaction
	 *
	 * @return bool
	 */
	public function rollBack()
	{
		$res = \fbird_rollback($this->trans);
		$this->trans = NULL;
		return $res;
	}

	// --------------------------------------------------------------------------

	/**
	 * Set a connection attribute
	 * @param int $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute($attribute, $value)
	{
		return FALSE;
	}

	// --------------------------------------------------------------------------

	/**
	 * Prepare and execute a query
	 *
	 * @param string $sql
	 * @param array $args
	 * @return Firebird_Result
	 */
	public function prepare_execute($sql, $args)
	{
		$query = $this->prepare($sql);

		// Set the statement in the class variable for easy later access
		$this->statement_link =& $query;

		return $query->execute($args);
	}

	// --------------------------------------------------------------------------

	/**
	 * Method to emulate PDO->quote
	 *
	 * @param string $str
	 * @param int $param_type
	 * @return string
	 */
	public function quote($str, $param_type = \PDO::PARAM_STR)
	{
		if(is_numeric($str))
		{
			return $str;
		}

		return "'".str_replace("'", "''", $str)."'";
	}

	// --------------------------------------------------------------------------

	/**
	 * Method to emulate PDO->errorInfo / PDOStatement->errorInfo
	 *
	 * @return array
	 */
	public function errorInfo()
	{
		$code = \fbird_errcode();
		$msg = \fbird_errmsg();

		return array(0, $code, $msg);
	}

	// --------------------------------------------------------------------------

	/**
	 * Method to emulate PDO->errorCode
	 *
	 * @return array
	 */
	public function errorCode()
	{
		return fbird_errcode();
	}

	// --------------------------------------------------------------------------

	/**
	 * Bind a prepared query with arguments for executing
	 *
	 * @param string $sql
	 * @param array $params
	 * @return NULL
	 */
	public function prepare_query($sql, $params)
	{
		// You can't bind query statements before execution with
		// the firebird database
		return NULL;
	}

	// --------------------------------------------------------------------------

	/**
	 * Create sql for batch insert
	 *
	 * @param string $table
	 * @param array $data
	 * @return array
	 */
	public function insert_batch($table, $data=array())
	{
		// Each member of the data array needs to be an array
		if ( ! is_array(current($data))) return NULL;

		// Start the block of sql statements
		$sql = "EXECUTE BLOCK AS BEGIN\n";

		$table = $this->quote_table($table);
		$fields = \array_keys(\current($data));

		$insert_template = "INSERT INTO {$table} ("
			. implode(',', $this->quote_ident($fields))
			. ") VALUES (";

		foreach($data as $item)
		{
			// Quote string values
			$vals = array_map(array($this, 'quote'), $item);

			// Add the values in the sql
			$sql .= $insert_template . implode(', ', $vals) . ");\n";
		}

		// End the block of SQL statements
		$sql .= "END";

		// Ruturn a null array value so the query is run as it is,
		// not as a prepared statement, because a prepared statement
		// doesn't work for this type of query in Firebird.
		return array($sql, NULL);
	}
}
// End of firebird_driver.php