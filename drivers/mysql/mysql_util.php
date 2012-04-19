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
 * MySQL-specific backup, import and creation methods
 */
class MySQL_Util extends DB_Util {

	public function __construct(&$conn)
	{
		parent::__construct($conn);
	}
	
	/**
 	 * Convienience public function for creating a new MySQL table
 	 *
 	 * @param string $name
 	 * @param array $columns
 	 * @param array $constraints=array()
 	 * @param array $indexes=array()
 	 *
 	 * @return string
 	 */
	public function create_table($name, $columns, array $constraints=array(), array $indexes=array())
	{
		$column_array = array();

		// Reorganize into an array indexed with column information
		// Eg $column_array[$colname] = array(
		// 		'type' => ...,
		// 		'constraint' => ...,
		// 		'index' => ...,
		// )
		foreach($columns as $colname => $type)
		{
			if(is_numeric($colname))
			{
				$colname = $type;
			}

			$column_array[$colname] = array();
			$column_array[$colname]['type'] = ($type !== $colname) ? $type : '';
		}

		if( ! empty($constraints))
		{
			foreach($constraints as $col => $const)
			{
				$column_array[$col]['constraint'] = "{$const} ({$col})";
			}
		}

		// Join column definitons together
		$columns = array();
		foreach($column_array as $n => $props)
		{
			$n = trim($n, '`');

			$str = "`{$n}` ";
			$str .= (isset($props['type'])) ? "{$props['type']} " : "";

			$columns[] = $str;
		}

		// Add constraints
		foreach($column_array as $n => $props)
		{
			if (isset($props['constraint']))
			{
				$columns[] = $props['constraint'];
			}
		}

		// Generate the sql for the creation of the table
		$sql = "CREATE TABLE IF NOT EXISTS `{$name}` (";
		$sql .= implode(", ", $columns);
		$sql .= ")";

		return $sql;
	}

	// --------------------------------------------------------------------------

	/**
	 * Convience public function for droping a MySQL table
	 *
	 * @param string $name
	 * @return  string
	 */
	public function delete_table($name)
	{
		return "DROP TABLE `{$name}`";
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Create an SQL backup file for the current database's structure
	 *
	 * @return string
	 */
	public function backup_structure()
	{
		// @todo Implement Backup function
		return '';
	}

	// --------------------------------------------------------------------------

	/**
	 * Create an SQL backup file for the current database's data
	 *
	 * @param array $exclude
	 * @return string
	 */
	public function backup_data($exclude=array())
	{
		$tables = $this->get_tables();
		
		// Filter out the tables you don't want
		if( ! empty($exclude))
		{
			$tables = array_diff($tables, $exclude);
		}
		
		$output_sql = '';
		
		// Select the rows from each Table
		foreach($tables as &$t)
		{
			$sql = "SELECT * FROM `{$t}`";
			$res = $this->query($sql);
			$rows = $res->fetchAll(PDO::FETCH_ASSOC);
			
			$res = NULL;
			
			// Skip empty tables
			if (count($rows) < 1) continue;
			
			// Nab the column names by getting the keys of the first row
			$columns = @array_keys($rows[0]);

			$insert_rows = array();
			
			// Create the insert statements
			foreach($rows as &$row)
			{
				$row = array_values($row);
				$row = array_map(array(&$this, 'quote'), $row);
				$row = array_map('trim', $row);

				$row_string = 'INSERT INTO `'.trim($t).'` (`'.implode('`,`', $columns).'`) VALUES ('.implode(',', $row).');';

				$row = NULL;

				$insert_rows[] = $row_string;
			}
			
			$obj_res = NULL;

			$output_sql .= "\n\n".implode("\n", $insert_rows)."\n";
		}
	
		return $output_sql;
	}
}
// End of mysql_util.php