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
 * Base class for table builder component classes`
 */
abstract class Abstract_Table {

	/**
	 * Valid options for the current class
	 * @var array
	 */
	protected $valid_options;

	// --------------------------------------------------------------------------
	// ! Abstract Methods
	// --------------------------------------------------------------------------

	/**
	 * String representation of the column/index
	 */
	abstract public function __toString();

	// --------------------------------------------------------------------------
	// ! Concrete methods
	// --------------------------------------------------------------------------

	/**
	 * Set options for the current column
	 *
	 * @param array $options
	 * return \Query\Table_Column
	 */
	public function set_options(Array $options)
	{
		$class_segments = explode('_', get_class($this));
		$type = end($class_segments);

		foreach($options as $option => $value)
		{
			if ( ! in_array($option, $this->valid_options))
			{
				throw new \InvalidArgumentException("{$option} is not a valid {$type}");
			}

			$func = "set_{$option}";

			(method_exists($this, "set_{$option}"))
				? $this->$func($value)
				: $this->__set($option, $value);
		}

		return $this;
	}

	// --------------------------------------------------------------------------

	/**
	 * Getters
	 * @param mixed $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if ( ! isset($this->$name)) return NULL;

		return $this->$name;
	}

	// --------------------------------------------------------------------------

	/**
	 * Setters
	 * @param mixed $name
	 * @param mixed $val
	 * @return \Query\Table_Column
	 */
	public function __set($name, $val)
	{
		$this->$name = $val;
		return $this;
	}

}
// End of abstract_table.php