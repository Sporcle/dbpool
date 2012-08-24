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
 * it is. Additionally this class will prevent any writing queries (insert/update) from executing.
 * 
 * @package Sporcle
 * @subpackage MysqliRO
 * @author Dan Munro <dan@sporcle.com>
 */

class MysqliRO extends Mysqli
{

	/**
	 * Queries the database with additional logging functionality and a check that the
	 * isn't attempting to write to a slave.
	 *
	 * @param string $queryStr The SQL query to run against the database.
	 *
	 * @return mysqli_result The result object from the query, or false on failure.
	 */
	public function query($queryStr)
	{
		// Check first for a writing call on slave
		if($this->isQueryWriting($queryStr)) {
			throw new DBException("Cannot perform query on slave: ($queryStr)", DBException::WRITING_TO_SLAVE);
		}
		return parent::query($queryStr);
	}

	/**
	 * Checks if the query that is about to be performed will write to the database.
	 * If so, that is a problem because this is a read only connection.
	 *
	 * @access private
	 *
	 * @param string $queryStr The SQL query to run against the database.
	 */
	private function isQueryWriting($queryStr)
	{
		$queryStr = trim($queryStr);
		return stripos($queryStr, 'insert') === 0 || stripos($queryStr, 'update') === 0;
	}
}
