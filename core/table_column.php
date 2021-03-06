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

namespace Query\Table;

/**
 * Class representing a column when creating a table
 */
class Table_Column extends Abstract_Table {

	/**
	 * The name of the column
	 * @var string
	 */
	protected $name;

	/**
	 * The type of the column
	 * @var string
	 */
	protected $type;

	/**
	 * Valid column options
	 * @var type array
	 */
	protected $valid_options = array(
		'limit',
		'length',
		'default',
		'null',
		'precision',
		'scale',
		'after',
		'update',
		'comment'
	);

	// --------------------------------------------------------------------------

	/**
	 * Set the attributes for the column
	 *
	 * @param string $name
	 * @param [string] $type
	 * @param [array] $options
	 */
	public function __construct($name, $type = NULL, $options = array())
	{
		$this->name = $name;
		$this->type = $type;
		$this->options = ( ! empty($options))
			? $this->validate_options($options)
			: array();
	}

	// --------------------------------------------------------------------------

	/**
	 * Return the string to create the column
	 *
	 * @return string
	 */
	public function __toString()
	{
		$num_args = func_num_args();
	}

}
// End of table_column.php
