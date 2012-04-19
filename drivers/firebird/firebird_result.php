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

/**
 * Firebird result class to emulate PDOStatement Class - only implements
 * data-fetching methods
 *
 */
class Firebird_Result extends PDOStatement {

	/**
	 * Reference to fbird resource
	 */
	private $statement;

	/**
	 * Create the object by passing the resource for
	 * the query
	 *
	 * @param resource $link
	 */
	public function __construct($link)
	{
		$this->statement = $link;
		$this->setFetchMode(PDO::FETCH_ASSOC);
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Invalidate method for data consistency
	 *
	 * @param mixed $column
	 * @param mixed &$param
	 * @param int $type
	 * @param mixed $maxlen
	 * @param array $driverdata
	 * @return FALSE
	 */
	public function bindColumn($column, &$param, $type=NULL, $maxlen=NULL, $driverdata=NULL)
	{
		return FALSE;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Invalidate method for data consistency
	 *
	 * @param mixed $parameter
	 * @param mixed &$variable
	 * @param int $data_type
	 * @param mixed $maxlen
	 * @param array $driverdata
	 * @return FALSE
	 */
	public function bindParam($parameter, &$variable, $data_type=NULL, $maxlen=NULL, $driverdata=NULL)
	{
		return FALSE;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Invalidate method for data consistency
	 *
	 * @param mixed $parameter
	 * @param mixed &$variable
	 * @param int $data_type
	 * @return FALSE
	 */
	public function bindValue($parameter, $variable, $data_type=NULL)
	{
		return FALSE;
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Run a prepared statement query
	 *
	 * @param  array $args
	 * @return bool
	 */
	public function execute($args = NULL)
	{
		//Add the prepared statement as the first parameter
		array_unshift($args, $this->statement);

		// Let php do all the hard stuff in converting
		// the array of arguments into a list of arguments
		// Then pass the resource to the constructor
		$this->__construct(call_user_func_array('fbird_execute', $args));

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Emulate PDO fetch public function
	 *
	 * @param int $fetch_style
	 * @param mixed $statement
	 * @param mixed $offset
	 * @return mixed
	 */
	public function fetch($fetch_style=PDO::FETCH_ASSOC, $statement=NULL, $offset=NULL)
	{
		if ( ! is_null($statement))
		{
			$this->statement = $statement;
		}

		switch($fetch_style)
		{
			case PDO::FETCH_OBJ:
				return fbird_fetch_object($this->statement, IBASE_FETCH_BLOBS);
			break;

			case PDO::FETCH_NUM:
				return fbird_fetch_row($this->statement, IBASE_FETCH_BLOBS);
			break;

			default:
				return fbird_fetch_assoc($this->statement, IBASE_FETCH_BLOBS);
			break;
		}
	}

	// --------------------------------------------------------------------------

	/**
	 * Emulate PDO fetchAll public function
	 *
	 * @param int  $fetch_style
	 * @param mixed $statement
	 * @param mixed $ctor_args
	 * @return mixed
	 */
	public function fetchAll($fetch_style=PDO::FETCH_ASSOC, $statement=NULL, $ctor_args=NULL)
	{
		$all = array();

		while($row = $this->fetch($fetch_style, $statement))
		{
			$all[] = $row;
		}

		$this->result = $all;

		return $all;
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Emulate PDOStatement::fetchColumn
	 * 
	 * @param int $column_num
	 * @return mixed 
	 */
	public function fetchColumn($column_num=0)
	{
		$row = $this->fetch(PDO::FETCH_NUM);
		return $row[$column_num];
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Emulate PDOStatement::fetchObject, but only for the default use
	 * 
	 * @param string $class_name
	 * @param array $ctor_args
	 * @return mixed 
	 */
	public function fetchObject($class_name='stdClass', $ctor_args=array())
	{
		return $this->fetch(PDO::FETCH_OBJ);
	}

	// --------------------------------------------------------------------------

	/**
	 * Return the number of rows affected by the previous query
	 *
	 * @return int
	 */
	public function rowCount()
	{
		return fbird_affected_rows();
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Return the number of rows for the select query
	 * 
	 * @return int
	 */
	public function num_rows()
	{
		return count($this->fetchAll());
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Method to emulate PDOStatement->errorCode
	 *
	 * @return string
	 */
	public function errorCode()
	{
		return fbird_errcode();
	}

	// --------------------------------------------------------------------------

	/**
	 * Method to emulate PDO->errorInfo / PDOStatement->errorInfo
	 *
	 * @return array
	 */
	public function errorInfo()
	{
		$code = fbird_errcode();
		$msg = fbird_errmsg();

		return array(0, $code, $msg);
	}
}
// End of firebird_result.php