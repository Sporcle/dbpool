<?php

/**
 * DBPool is a handler for one or more database connections built on top of mysqli.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sporcle\DB;

/**
 * A lightweight extension of PHP's mysqli in which we provide a additional
 * error logging functionality and the ability to ask the connection what instance type
 * it is.
 *
 * @package Sporcle
 * @subpackage Mysqli
 * @author Dan Munro <dan@sporcle.com>
 */

class Mysqli extends \mysqli
{
	/**
	 * @var int $instanceType is used when deterimining if the query can be performed against the connection. This
	 * will be useful primarily when preventing insert or update queries from running on a slave.
	 */
	private $instanceType = 0;

	/**
	 * __construct() over-rides the native mysqli constructor in order to track the instance type of the db, otherwise
	 * the mysqli constructor docs still apply.
	 *
	 * @param string $host The db host
	 * @param string $user The db user
	 * @param string $pass The db passwork
	 * @param string $db The name of the database
	 * @param int $instanceType The type of db connection requested
	 *
	 * @returns Mysqli A new mysqli connection
	 */
	public function __construct($host, $user, $pass, $db, $instanceType)
	{
		$this->instanceType = $instanceType;
		parent::__construct($host, $user, $pass, $db);
	}

	/**
	 * Returns the instance type of the connection passed to the object in its constructor.
	 *
	 * @return int The instance type of the connection.
	 */
	public function getInstanceType()
	{
		return $this->instanceType;
	}

	/**
	 * Queries the database with additional logging functionality.
	 *
	 * @param string $queryStr The SQL query to run against the database.
	 *
	 * @return mysqli_result The result object from the query, or false on failure.
	 */
	public function query($queryStr)
	{
		$success = parent::query($queryStr);
		if($this->errno) {
			error_log("[DBPool error] [Error #".$this->errno."] ".$this->error." -- [".$queryStr."]");
		}
		return $success;
	}
}
