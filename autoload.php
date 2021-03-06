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

/**
 * Autoloader for loading available database classes
 *
 * @package Query
 */

/**
 * Reference to root path
 * @subpackage Core
 */
define('QBASE_PATH', dirname(__FILE__).'/');

/**
 * Path to driver classes
 * @subpackage Core
 */
define('QDRIVER_PATH', QBASE_PATH.'drivers/');

// Require some common functions
require(QBASE_PATH.'common.php');

/**
 * Load query classes
 *
 * @subpackage Core
 * @codeCoverageIgnore
 * @param string $class
 */
function query_autoload($class)
{
	$class_segments = explode('\\', $class);
	$class = strtolower(array_pop($class_segments));

	// Load DB Driver classes
	$driver_path = QDRIVER_PATH . "{$class}";
	if ($class_segments == array('Query', 'Driver') && is_dir($driver_path))
	{

		// Firebird is a special case, since it's not a PDO driver
		if (
			in_array($class, PDO::getAvailableDrivers())
			||  function_exists('fbird_connect') && $class === 'firebird'
		)
		{
			array_map('do_include', glob("{$driver_path}/*.php"));
		}
	}

	// Load other classes
	foreach(array(
		QBASE_PATH . "core/interfaces/{$class}.php",
		QBASE_PATH . "core/abstract/{$class}.php",
		QBASE_PATH . "core/{$class}.php"
	) as $path)
	{
		if (file_exists($path))
		{
			require_once($path);
		}
	}
}

// Set up autoloader
spl_autoload_register('query_autoload');

// End of autoload.php