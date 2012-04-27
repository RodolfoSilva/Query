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

/**
 * Convienience class for creating sql queries - also the class that
 * instantiates the specific db driver
 *
 * @package Query
 * @subpackage Query
 */
class Query_Builder {

	/**
	 * Compiled 'select' clause
	 *
	 * @var string
	 */
	private $select_string;
	
	/**
	 * Compiled 'from' clause
	 *
	 * @var string
	 */	
	private $from_string;
	
	/**
	 * Compiled arguments for insert / update
	 *
	 * @var string
	 */
	private $set_string;
	
	/**
	 * Order by clause
	 *
	 * @var string
	 */
	private $order_string;
	
	/**
	 * Group by clause
	 *
	 * @var string
	 */
	private $group_string;
	
	// --------------------------------------------------------------------------

	/**
	 * key/val pairs for insert/update statement
	 *
	 * @var array
	 */
	private $set_array;
	
	/**
	 * Keys for insert/update statement
	 *
	 * @var array
	 */
	private $set_array_keys;
	
	/** 
	 * Key/val pairs for order by clause
	 *
	 * @var array
	 */
	private $order_array;
	
	/**
	 * Key/val pairs for group by clause
	 *
	 * @var array
	 */
	private $group_array;
	
	// --------------------------------------------------------------------------

	/**
	 * Values to apply to prepared statements
	 *
	 * @var array
	 */
	private $values;

	/**
	 * Value for limit string
	 *
	 * @var int
	 */
	private $limit;
	
	/**
	 * Value for offset in limit string
	 *
	 * @var int
	 */
	private $offset;

	/**
	 * Alias to $this->db->sql
	 *
	 * @var DB_PDO
	 */
	private $sql;

	/** 
	 * Query component order mapping
	 * for complex select queries
	 *
	 * Format:
	 *
	 * array(
	 * 		'type' => 'where',
	 *		'conjunction' => ' AND ',
	 * 		'string' => 'k=?'
	 * )
	 *
	 * @var array
	 */
	private $query_map;
	
	/**
	 * Map for having clause
	 *
	 * @var array
	 */
	private $having_map;

	/**
	 * Convenience property for connection management
	 *
	 * @var string
	 */
	public $conn_name = "";
	
	// --------------------------------------------------------------------------
	// ! Start of methods
	// --------------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param object $params - the connection parametere
	 */
	public function __construct($params)
	{
		// Convert array to object
		if (is_array($params))
		{
			$p = new StdClass();

			foreach($params as $key => $val)
			{
				$p->$key = $val;
			}

			$params = $p;
		}
		
		// Let the connection work with 'conn_db' or 'database'		
		$params->conn_db = ( ! isset($params->database))
			? @$params->conn_db
			: @$params->database;

		$params->type = strtolower($params->type);
		$dbtype = ($params->type !== 'postgresql') ? $params->type : 'pgsql';

		// Create the dsn for the database to connect to
		switch($dbtype)
		{
			default:
				$dsn = "dbname={$params->conn_db}";

				if ( ! empty($params->host))
				{
					$dsn .= ";host={$params->host}";
				}

				if ( ! empty($params->port))
				{
					$dsn .= ";port={$params->port}";
				}
				
			break;

			case "sqlite":
				$dsn = $params->file;
			break;

			case "firebird":
				$dsn = "{$params->host}:{$params->file}";
			break;
		}

		// Create the database connection
		if ( ! empty($params->user))
		{
			$this->db = new $dbtype($dsn, $params->user, $params->pass);
		}
		else
		{
			$this->db = new $dbtype($dsn);
		}

		if (isset($params->name))
		{
			$this->conn_name = $params->name;
		}


		// Make things just slightly shorter
		$this->sql =& $this->db->sql;
	}

	// --------------------------------------------------------------------------
	// ! Select Queries
	// --------------------------------------------------------------------------

	/**
	 * Specifies rows to select in a query
	 *
	 * @param string $fields
	 * @return $this
	 */
	public function select($fields)
	{
		// Split fields by comma
		$fields_array = explode(",", $fields);
		$fields_array = array_map('trim', $fields_array);

		// Split on 'As'
		foreach ($fields_array as $key => $field)
		{
			if (stripos($field, 'as') !== FALSE)
			{
				$fields_array[$key] = preg_split('`as`i', $field);
				$fields_array[$key] = array_map('trim', $fields_array[$key]);
			}
		}

		// Quote the identifiers
		$safe_array = array_map(array($this->db, 'quote_ident'), $fields_array);

		unset($fields_array);

		// Join the strings back together
		for($i = 0, $c = count($safe_array); $i < $c; $i++)
		{
			if (is_array($safe_array[$i]))
			{
				$safe_array[$i] = implode(' AS ', $safe_array[$i]);
			}
		}

		$this->select_string .= implode(', ', $safe_array);

		unset($safe_array);

		return $this;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Selects the maximum value of a field from a query
	 *
	 * @param string $field
	 * @param string $as
	 * @return $this
	 */
	public function select_max($field, $as=FALSE)
	{
		// Escape the identifiers
		$field = $this->quote_ident($field);
		
		$as = ($as !== FALSE) 
			? $this->quote_ident($as)
			: $field;
			
		// Create the select string
		$this->select_string .= $this->sql->max()."({$field}) AS {$as} ";
		
		return $this;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Selects the minimum value of a field from a query
	 *
	 * @param string $field
	 * @param string $as
	 * @return $this
	 */
	public function select_min($field, $as=FALSE)
	{
		// Escape the identifiers
		$field = $this->quote_ident($field);
		
		$as = ($as !== FALSE) 
			? $this->quote_ident($as)
			: $field;
			
		// Create the select string
		$this->select_string .= $this->sql->min()."({$field}) AS {$as} ";
		
		return $this;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Selects the average value of a field from a query
	 *
	 * @param string $field
	 * @param string $as
	 * @return $this
	 */
	public function select_avg($field, $as=FALSE)
	{
		// Escape the identifiers
		$field = $this->quote_ident($field);
		
		$as = ($as !== FALSE) 
			? $this->quote_ident($as)
			: $field;
			
		// Create the select string
		$this->select_string .= $this->sql->avg()."({$field}) AS {$as} ";
		
		return $this;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Selects the sum of a field from a query
	 *
	 * @param string $field
	 * @param string $as
	 * @return $this
	 */
	public function select_sum($field, $as=FALSE)
	{
		// Escape the identifiers
		$field = $this->quote_ident($field);
		
		$as = ($as !== FALSE) 
			? $this->quote_ident($as)
			: $field;
			
		// Create the select string
		$this->select_string .= $this->sql->sum()."({$field}) AS {$as} ";
		
		return $this;
	}

	// --------------------------------------------------------------------------
	
	/**
	 * Adds the 'distinct' keyword to a query
	 *
	 * @return $this
	 */
	public function distinct()
	{
		// Prepend the keyword to the select string
		$this->select_string = $this->sql->distinct() . $this->select_string;
		
		return $this;
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Specify the database table to select from
	 *
	 * @param string $dbname
	 * @return $this
	 */
	public function from($dbname)
	{
		// Split identifiers on spaces
		$ident_array = explode(' ', trim($dbname));
		$ident_array = array_map('trim', $ident_array);

		// Quote the identifiers
		$ident_array = array_map(array($this->db, 'quote_ident'), $ident_array);

		// Paste it back together
		$this->from_string = implode(' ', $ident_array);

		return $this;
	}

	// --------------------------------------------------------------------------
	// ! 'Like' methods
	// --------------------------------------------------------------------------

	/**
	 * Creates a Like clause in the sql statement
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return $this
	 */
	public function like($field, $val, $pos='both')
	{
		$field = $this->quote_ident($field);

		// Add the like string into the order map
		$l = $field. ' LIKE ?';

		if ($pos == 'before')
		{
			$val = "%{$val}";
		}
		elseif ($pos == 'after')
		{
			$val = "{$val}%";
		}
		else
		{
			$val = "%{$val}%";
		}

		$this->query_map[] = array(
			'type' => 'like',
			'conjunction' => (empty($this->query_map)) ? 'WHERE ' : ' AND ',
			'string' => $l
		);

		// Add to the values array
		$this->values[] = $val;

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Generates an OR Like clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return $this
	 */
	public function or_like($field, $val, $pos='both')
	{
		$field = $this->quote_ident($field);

		// Add the like string into the order map
		$l = $field. ' LIKE ?';

		if ($pos == 'before')
		{
			$val = "%{$val}";
		}
		elseif ($pos == 'after')
		{
			$val = "{$val}%";
		}
		else
		{
			$val = "%{$val}%";
		}

		$this->query_map[] = array(
			'type' => 'like',
			'conjunction' => (empty($this->query_map)) ? 'WHERE ' : ' OR ',
			'string' => $l
		);

		// Add to the values array
		$this->values[] = $val;

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Generates a NOT LIKE clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return $this
	 */
	public function not_like($field, $val, $pos='both')
	{
		$field = $this->quote_ident($field);

		// Add the like string into the order map
		$l = $field. ' NOT LIKE ?';

		if ($pos == 'before')
		{
			$val = "%{$val}";
		}
		elseif ($pos == 'after')
		{
			$val = "{$val}%";
		}
		else
		{
			$val = "%{$val}%";
		}

		$this->query_map[] = array(
			'type' => 'like',
			'conjunction' => (empty($this->query_map)) ? ' WHERE ' : ' AND ',
			'string' => $l
		);

		// Add to the values array
		$this->values[] = $val;

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Generates a OR NOT LIKE clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return $this
	 */
	public function or_not_like($field, $val, $pos='both')
	{
		$field = $this->quote_ident($field);

		// Add the like string into the order map
		$l = $field. ' NOT LIKE ?';

		if ($pos == 'before')
		{
			$val = "%{$val}";
		}
		elseif ($pos == 'after')
		{
			$val = "{$val}%";
		}
		else
		{
			$val = "%{$val}%";
		}

		$this->query_map[] = array(
			'type' => 'like',
			'conjunction' => (empty($this->query_map)) ? ' WHERE ' : ' OR ',
			'string' => $l
		);

		// Add to the values array
		$this->values[] = $val;

		return $this;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Generates a 'Having' clause
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return $this
	 */
	public function having($key, $val=array())
	{
		$where = $this->_where($key, $val);
		
		// Create key/value placeholders
		foreach($where as $f => $val)
		{
			// Split each key by spaces, in case there
			// is an operator such as >, <, !=, etc.
			$f_array = explode(' ', trim($f));

			$item = $this->quote_ident($f_array[0]);

			// Simple key value, or an operator
			$item .= (count($f_array) === 1) ? '= ?' : " {$f_array[1]} ?";

			// Put in the query map for select statements
			$this->having_map[] = array(
				'conjunction' => ( ! empty($this->having_map)) ? ' AND ' : ' HAVING ',
				'string' => $item
			);
		}

		return $this;
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Generates a 'Having' clause prefixed with 'OR'
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return $this
	 */
	public function or_having($key, $val=array())
	{
		$where = $this->_where($key, $val);
		
		// Create key/value placeholders
		foreach($where as $f => $val)
		{
			// Split each key by spaces, in case there
			// is an operator such as >, <, !=, etc.
			$f_array = explode(' ', trim($f));

			$item = $this->quote_ident($f_array[0]);

			// Simple key value, or an operator
			$item .= (count($f_array) === 1) ? '= ?' : " {$f_array[1]} ?";

			// Put in the query map for select statements
			$this->having_map[] = array(
				'conjunction' => ( ! empty($this->having_map)) ? ' OR ' : ' HAVING ',
				'string' => $item
			);
		}

		return $this;
	}

	// --------------------------------------------------------------------------
	// ! 'Where' methods
	// --------------------------------------------------------------------------

	/**
	 * Do all the repeditive stuff for where/having type methods
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return array
	 */
	private function _where($key, $val=array())
	{
		$where = array();

		// Key and value passed? Add them to the where array
		if (is_scalar($key) && is_scalar($val))
		{
			$where[$key] = $val;
			$this->values[] = $val;
		}
		// Array or object, loop through and add to the where array
		elseif ( ! is_scalar($key))
		{
			foreach($key as $k => $v)
			{
				$where[$k] = $v;
				$this->values[] = $v;
			}
		}

		return $where;
	}

	// --------------------------------------------------------------------------

	/**
	 * Specify condition(s) in the where clause of a query
	 * Note: this function works with key / value, or a
	 * passed array with key / value pairs
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return $this
	 */
	public function where($key, $val=array())
	{
		$where = $this->_where($key, $val);

		// Create key/value placeholders
		foreach($where as $f => $val)
		{
			// Split each key by spaces, in case there
			// is an operator such as >, <, !=, etc.
			$f_array = explode(' ', trim($f));

			$item = $this->quote_ident($f_array[0]);

			// Simple key value, or an operator
			$item .= (count($f_array) === 1) ? '= ?' : " {$f_array[1]} ?";

			// Put in the query map for select statements
			$this->query_map[] = array(
				'type' => 'where',
				'conjunction' => ( ! empty($this->query_map)) ? ' AND ' : ' WHERE ',
				'string' => $item
			);
		}

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Where clause prefixed with "OR"
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return $this
	 */
	public function or_where($field, $val=array())
	{
		$where = $this->_where($field, $val);

		// Create key/value placeholders
		foreach($where as $f => $val)
		{
			// Split each key by spaces, incase there
			// is an operator such as >, <, !=, etc.
			$f_array = explode(' ', trim($f));

			// Simple key = val
			if (count($f_array) === 1)
			{
				$item = $this->quote_ident($f_array[0]) . '= ?';
			}
			else // Other operators
			{
				$item = $this->quote_ident($f_array[0]) . " {$f_array[1]} ?";
			}

			// Put in the query map for select statements
			$this->query_map[] = array(
				'type' => 'where',
				'conjunction' => ( ! empty($this->query_map)) ? ' OR ' : ' WHERE ',
				'string' => $item
			);
		}

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Where clause with 'IN' statement
	 *
	 * @param mixed $field
	 * @param mixed $val
	 * @return $this
	 */
	public function where_in($field, $val=array())
	{
		$field = $this->quote_ident($field);
		$params = array_fill(0, count($val), '?');

		foreach($val as $v)
		{
			$this->values[] = $v;
		}

		$string = $field . ' IN ('.implode(',', $params).') ';

		$this->query_map[] = array(
			'type' => 'where_in',
			'conjunction' => ( ! empty($this->query_map)) ? ' AND ' : ' WHERE ',
			'string' => $string
		);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Where in statement prefixed with "or"
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return $this
	 */
	public function or_where_in($field, $val=array())
	{
		$field = $this->quote_ident($field);
		$params = array_fill(0, count($val), '?');

		foreach($val as $v)
		{
			$this->values[] = $v;
		}

		$string = $field . ' IN ('.implode(',', $params).') ';

		$this->query_map[] = array(
			'type' => 'where_in',
			'conjunction' => ( ! empty($this->query_map)) ? ' OR ' : ' WHERE ',
			'string' => $string
		);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * WHERE NOT IN (FOO) clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return $this
	 */
	public function where_not_in($field, $val=array())
	{
		$field = $this->quote_ident($field);
		$params = array_fill(0, count($val), '?');

		foreach($val as $v)
		{
			$this->values[] = $v;
		}

		$string = $field.' NOT IN ('.implode(',', $params).') ';

		$this->query_map[] = array(
			'type' => 'where_in',
			'conjunction' => ( ! empty($this->query_map)) ? ' AND ' : ' WHERE ',
			'string' => $string
		);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * OR WHERE NOT IN (FOO) clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return $this
	 */
	public function or_where_not_in($field, $val=array())
	{
		$field = $this->quote_ident($field);
		$params = array_fill(0, count($val), '?');

		foreach($val as $v)
		{
			$this->values[] = $v;
		}

		$string = $field.' NOT IN ('.implode(',', $params).') ';

		$this->query_map[] = array(
			'type' => 'where_in',
			'conjunction' => ( ! empty($this->query_map)) ? ' OR ' : ' WHERE ',
			'string' => $string
		);

		return $this;
	}

	// --------------------------------------------------------------------------
	// ! Other Query Modifier methods
	// --------------------------------------------------------------------------
	
	/**
	 * Sets values for inserts / updates / deletes
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return $this
	 */
	public function set($key, $val)
	{
		// Plain key, value pair
		if (is_scalar($key) && is_scalar($val))
		{
			$this->set_array[$key] = $val;
			$this->values[] = $val;
		}
		// Object or array
		elseif ( ! is_scalar($key))
		{
			foreach($key as $k => $v)
			{
				$this->set_array[$k] = $v;
				$this->values[] = $val;
			}
		}

		// Use the keys of the array to make the insert/update string
		// Escape the field names
		$this->set_array_keys = array_map(array($this->db, 'quote_ident'), array_keys($this->set_array));

		// Generate the "set" string
		$this->set_string = implode('=?, ', $this->set_array_keys);
		$this->set_string .= '=?';

		return $this;
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Creates a join phrase in a compiled query
	 *
	 * @param string $table
	 * @param string $condition
	 * @param string $type
	 * @return $this
	 */
	public function join($table, $condition, $type='')
	{
		// Paste it back together
		$table = implode(" ", array_map(array($this->db, 'quote_ident'), explode(' ', trim($table))));
		//$condition = preg_replace('`(\W)`', " $1 ", $condition);
		$cond_array = explode(' ', trim($condition));
		$cond_array = array_map('trim', $cond_array);

		$condition = $table . ' ON ' . $this->quote_ident($cond_array[0])  . $cond_array[1] .
				' ' . $this->quote_ident($cond_array[2]);

		$this->query_map[] = array(
			'type' => 'join',
			'conjunction' => strtoupper($type).' JOIN ',
			'string' => $condition,
		);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Group the results by the selected field(s)
	 *
	 * @param mixed $field
	 * @return $this
	 */
	public function group_by($field)
	{
		if ( ! is_scalar($field))
		{
			$this->group_array = array_map(array($this->db, 'quote_ident'), $field);
		}
		else
		{
			$this->group_array[] = $this->quote_ident($field);
		}

		$this->group_string = ' GROUP BY ' . implode(', ', $this->group_array);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Order the results by the selected field(s)
	 *
	 * @param string $field
	 * @param string $type
	 * @return $this
	 */
	public function order_by($field, $type="")
	{
		// Random case
		if (stripos($type, 'rand') !== FALSE)
		{
			$type = (($rand = $this->sql->random()) !== FALSE ) ? $rand : 'ASC';
		}

		// Set fields for later manipulation
		$field = $this->quote_ident($field);
		$this->order_array[$field] = $type;

		$order_clauses = array();

		// Flatten key/val pairs into an array of space-separated pairs
		foreach($this->order_array as $k => $v)
		{
			$order_clauses[] = $k . ' ' . strtoupper($v);
		}

		// Set the final string
		$this->order_string = (empty($rand))
			? ' ORDER BY '.implode(',', $order_clauses)
			: ' ORDER BY'.$rand;

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Set a limit on the current sql statement
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return string
	 */
	public function limit($limit, $offset=FALSE)
	{
		$this->limit = $limit;
		$this->offset = $offset;

		return $this;
	}

	// --------------------------------------------------------------------------
	// ! Query Grouping Methods
	// --------------------------------------------------------------------------

	/**
	 * Adds a paren to the current query for query grouping
	 *
	 * @return $this
	 */
	public function group_start()
	{
		$this->query_map[] = array(
			'type' => 'group_start',
			'conjunction' => '',
			'string' => ' ('
		);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Adds a paren to the current query for query grouping,
	 * prefixed with 'OR'
	 *
	 * @return $this
	 */
	public function or_group_start()
	{
		$this->query_map[] = array(
			'type' => 'group_start',
			'conjunction' => '',
			'string' => ' OR ('
		);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Adds a paren to the current query for query grouping,
	 * prefixed with 'OR NOT'
	 *
	 * @return $this
	 */
	public function or_not_group_start()
	{
		$this->query_map[] = array(
			'type' => 'group_start',
			'conjunction' => '',
			'string' => ' OR NOT ('
		);

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Ends a query group
	 *
	 * @return $this
	 */
	public function group_end()
	{
		$this->query_map[] = array(
			'type' => 'group_end',
			'conjunction' => '',
			'string' => ' ) '
		);

		return $this;
	}
	
	// --------------------------------------------------------------------------
	// ! Query execution methods
	// --------------------------------------------------------------------------

	/**
	 * Select and retrieve all records from the current table, and/or
	 * execute current compiled query
	 *
	 * @param $table
	 * @param int $limit
	 * @param int $offset
	 * @return object
	 */
	public function get($table='', $limit=FALSE, $offset=FALSE)
	{
		// Set the table
		if ( ! empty($table))
		{
			$this->from($table);
		}

		// Set the limit, if it exists
		if ($limit !== FALSE)
		{
			$this->limit($limit, $offset);
		}

		$sql = $this->_compile();

		// Do prepared statements for anything involving a "where" clause
		if ( ! empty($this->query_map) || ! empty($this->having_map))
		{
			$result =  $this->prepare_execute($sql, $this->values);
		}
		else
		{
			// Otherwise, a simple query will do.
			$result =  $this->query($sql);
		}

		// Reset for next query
		$this->_reset();

		return $result;
	}

	// --------------------------------------------------------------------------
	
	/**
	 * Convience method for get() with a where clause
	 *
	 * @param string $table
	 * @param array $where
	 * @param int $limit
	 * @param int $offset
	 * @return object
	 */
	public function get_where($table, $where=array(), $limit=FALSE, $offset=FALSE)
	{
		// Create the where clause
		$this->where($where);
		
		// Return the result
		return $this->get($table, $limit, $offset);
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Retreive the number of rows in the selected table
	 *
	 * @param string $table
	 * @return int
	 */
	public function count_all($table)
	{
		$sql = 'SELECT * FROM '.$this->quote_ident($table);
		$res = $this->query($sql);
		return (int) count($res->fetchAll());
	}
	
	// --------------------------------------------------------------------------
	
	/**
	 * Retrieve the number of results for the generated query - used
	 * in place of the get() method
	 *
	 * @param string $table
	 * @return int
	 */
	public function count_all_results($table='')
	{
		// Set the table
		if ( ! empty($table))
		{
			$this->from($table);
		}
	
		$sql = $this->_compile();

		// Do prepared statements for anything involving a "where" clause
		if ( ! empty($this->query_map))
		{
			$result =  $this->prepare_execute($sql, $this->values);
		}
		else
		{
			// Otherwise, a simple query will do.
			$result =  $this->query($sql);
		}

		// Reset for next query
		$this->_reset();
		
		$rows = $result->fetchAll();
		
		return (int) count($rows);
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Creates an insert clause, and executes it
	 *
	 * @param string $table
	 * @param mixed $data
	 * @return mixed
	 */
	public function insert($table, $data=array())
	{
		// No use duplicating logic!
		if ( ! empty($data))
		{
			$this->set($data);
		}

		$sql = $this->_compile("insert", $table);

		$res = $this->prepare_execute($sql, $this->values);

		$this->_reset();

		return $res;
	}

	// --------------------------------------------------------------------------

	/**
	 * Creates an update clause, and executes it
	 *
	 * @param string $table
	 * @param mixed $data
	 * @return mixed
	 */
	public function update($table, $data=array())
	{
		// No use duplicating logic!
		if ( ! empty($data))
		{
			$this->set($data);
		}

		$sql = $this->_compile('update', $table);

		$res = $this->prepare_execute($sql, $this->values);

		$this->_reset();

		// Run the query
		return $res;
	}

	// --------------------------------------------------------------------------

	/**
	 * Deletes data from a table
	 *
	 * @param string $table
	 * @param mixed $where
	 * @return mixed
	 */
	public function delete($table, $where='')
	{
		// Set the where clause
		if ( ! empty($where))
		{
			$this->where($where);
		}

		// Create the SQL and parameters
		$sql = $this->_compile("delete", $table);

		$res = $this->prepare_execute($sql, $this->values);

		$this->_reset();

		// Delete the table rows, and return the result
		return $res;
	}


	// --------------------------------------------------------------------------
	// ! Miscellaneous Methods
	// --------------------------------------------------------------------------

	/**
	 * Calls a function further down the inheritence chain
	 *
	 * @param string $name
	 * @param array $params
	 * @return mixed
	 */
	public function __call($name, $params)
	{
		if (method_exists($this->db, $name))
		{
			return call_user_func_array(array($this->db, $name), $params);
		}

		return NULL;
	}

	// --------------------------------------------------------------------------

	/**
	 * Clear out the class variables, so the next query can be run
	 */
	private function _reset()
	{
		// Only unset class variables that
		// are not callable. Otherwise, we'll
		// delete class methods!
		foreach($this as $name => $var)
		{
			// Skip properties that are needed for every query
			$save_properties = array(
				'db',
				'sql'
			);

			if (in_array($name, $save_properties))
			{
				continue;
			}

			// Nothing query-generation related is safe!
			if ( ! is_callable($this->$name))
			{
				$this->$name = NULL;
			}

			// Set empty arrays
			$this->values = array();
			
			// Set select string as an empty string, for proper handling
			// of the 'distinct' keyword
			$this->select_string = '';
		}
	}

	// --------------------------------------------------------------------------

	/**
	 * String together the sql statements for sending to the db
	 *
	 * @param string $type
	 * @param string $table
	 * @return $string
	 */
	private function _compile($type='', $table="")
	{
		$sql = '';

		switch($type)
		{
			default:
				$sql = 'SELECT * FROM '.$this->from_string;

				// Set the select string
				if ( ! empty($this->select_string))
				{
					// Replace the star with the selected fields
					$sql = str_replace('*', $this->select_string, $sql);
				}

				// Set the where string
				if ( ! empty($this->query_map))
				{
					foreach($this->query_map as $q)
					{
						$sql .= $q['conjunction'] . $q['string'];
					}
				}

				// Set the group_by string
				if ( ! empty($this->group_string))
				{
					$sql .= $this->group_string;
				}
				
				// Set the having string
				if ( ! empty($this->having_map))
				{
					foreach($this->having_map as $h)
					{
						$sql .= $h['conjunction'] . $h['string'];
					}
				}

				// Set the order_by string
				if ( ! empty($this->order_string))
				{
					$sql .= $this->order_string;
				}

				// Set the limit via the class variables
				if (isset($this->limit) && is_numeric($this->limit))
				{
					$sql = $this->sql->limit($sql, $this->limit, $this->offset);
				}
			break;

			case "insert":
				$param_count = count($this->set_array);
				$params = array_fill(0, $param_count, '?');
				$sql = 'INSERT INTO '. $this->quote_ident($table) .
					' (' . implode(', ', $this->set_array_keys) .
					') VALUES ('.implode(', ', $params).')';
			break;

			case "update":
				$sql = 'UPDATE '.$this->quote_ident($table). ' SET '. $this->set_string;

				// Set the where string
				if ( ! empty($this->query_map))
				{
					foreach($this->query_map as $q)
					{
						$sql .= $q['conjunction'] . $q['string'];
					}
				}
			break;

			case "delete":
				$sql = 'DELETE FROM '.$this->quote_ident($table);

				// Set the where string
				if ( ! empty($this->query_map))
				{
					foreach($this->query_map as $q)
					{
						$sql .= $q['conjunction'] . $q['string'];
					}
				}

			break;
		}
		
		//echo $sql . '<br />';

		return $sql;
	}
}
// End of query_builder.php