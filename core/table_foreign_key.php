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
 * Class representing a foreign key
 */
class Table_Foreign_Key extends Abstract_Table {

	/**
	 * Valid options for a foreign key
	 * @var type array
	 */
	protected $valid_options = array(
		'delete',
		'update',
		'constraint'
	);

	/**
	 * String representation of the foreign key
	 */
	public function __toString()
	{

	}
}
// End of table_foreign_key.php
