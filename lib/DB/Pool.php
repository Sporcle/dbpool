<?php

/**
 * DBPool is a handler for one or more database connections built on top of mysqli.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sporcle\DB;

/**
 * Pool initializes and returns database connections.
 *
 * @package Sporcle
 * @subpackage DBPool
 * @author Dan Munro <dan@sporcle.com>
 */

class Pool
{
	const MASTER = 1;
	const SLAVE = 2;
	const CRON = 3;
	// define more db types here as needed

	/**
	 * @var DBPool $instance is the single instance of DBPool.
	 */
	private static $instance = null;

	/**
	 * @var array $connections holds up to one active connection for each instance type requested. For a given instance, it will
	 * only cycle a new connection if the first attempt fails or if requested through manualConnectionFailover().
	 */
	private $connections = array();

	/**
	 * @var array $connectionParams represents a pool of available connections, with the first level reserved for the instance type
	 * and the second level for associative arrays defining the connection parameters for the given type.
	 */
	private $connectionParams = array();

	/**
	 * Use this method to request database connections of the given $instanceType.
	 * 
	 * @param int $instanceType The type of database requested
	 *
	 * @return mysqli A mysqli connection
	 */
	public static function instance($instanceType)
	{
		if(!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance->requestConnection($instanceType);
	}

	/**
	 * Initializes DBPool with its pool of connection parameters.
	 *
	 * @access private
	 */
	protected function __construct()
	{
		// Define connection parameters
		$this->connectionParams = array
		(
			self::MASTER => array
			(
				array
				(
					'host' => '',
					'user' => '',
					'pass' => '',
					'db' => ''
				)
			),
			self::SLAVE => array
			(
				array
				(
					'host' => '',
					'user' => '',
					'pass' => '',
					'db' => ''
				),
				array
				(
					'host' => '',
					'user' => '',
					'pass' => '',
					'db' => ''
				)
			),
			self::CRON => array
			(
				array
				(
					'host' => '',
					'user' => '',
					'pass' => '',
					'db' => ''
				)
			)
		);

		// Randomize connection order for slaves
		shuffle($this->connectionParams[self::SLAVE]);
	}

	/**
	 * If needed, a new connection will be instantiated to the given db type,
	 * and the connection will be returned.
	 *
	 * @access private
	 *
	 * @param int $instanceType The type of database requested.
	 *
	 * @return Mysqli An open database connection.
	 */
	public function requestConnection($instanceType)
	{
		// Ensure the instance type is valid (master, slave, etc)
		if(!array_key_exists($instanceType, $this->connectionParams)) {
			throw new Exception("Instance type does not exist: ($instanceType)", Exception::TYPE_NOT_DEFINED);
		}

		// Request to open a connection if one doesn't already exist and return it
		if(!isset($this->connections[$instanceType])) {
			$this->establishConnection($instanceType);
		}

		return $this->connections[$instanceType];
	}

	/**
	 * Opens a new connection of the requested instance type if a server
	 * remains available from the pool. Each request for a new connection will remove the parameters for that
	 * connection from the pool to prevent reconnecting to the same server (a disconnect/new connection should
	 * mean the original connection is no longer accessible or purposely cycled).
	 *
	 * @access private
	 *
	 * @param int $instanceType The type of database requested.
	 *
	 * @return Mysqli An open database connection.
	 */
	private function establishConnection($instanceType)
	{
		// Get connection parameters
		if(sizeof($this->connectionParams[$instanceType])) {
			$c = array_shift($this->connectionParams[$instanceType]);
		} else {
			error_log("[DBPool error] DB pool in exhausted for type ($instanceType)");
			throw new Exception("DB pool in exhausted for type ($instanceType)!", Exception::NO_CONNECTION);
		}

		// Find the right connection object
		if($instanceType === self::SLAVE) {
			$class = 'MysqliRO';
		} else {
			$class = 'Mysqli';
		}
		$connection = new $class($c['host'], $c['user'], $c['pass'], $c['db'], $instanceType);

		// In the event of an error (slave goes down), automatically try a reconnect on another server of the same instance type
		if(isset($connection->connect_error) && $connection->connect_error) {
			error_log("[DBPool error] [Error #".$connection->connect_errno."] [".$c['host']."] ".$connection->connect_error);
			return $this->establishConnection($instanceType);
		}

		// Success
		$this->connections[$instanceType] = $connection;
	}

	/**
	 * Returns The number of servers available to connect to for a given instance type.
	 *
	 * @param int $instanceType The type of database connection to get the pool size for.
	 *
	 * @return int the number of available connections
	 */
	public static function getConnectionPoolSize($instanceType)
	{
		return self::$instance->getPoolSize($instanceType);
	}

	/**
	 * Returns The number of servers available to connect to for a given instance type.
	 *
	 * @access private
	 * 
	 * @param int $instanceType The type of database connection to get the pool size for.
	 *
	 * @return int the number of available connections
	 */
	public function getPoolSize($instanceType)
	{
		return sizeof($this->connectionParams[$instanceType]);
	}

	/**
	 * Can be used if it is somehow preferable to close an active connection and reconnect
	 * to another random server from the pool with the same given instance type.
	 *
	 * @param int $instanceType the type of database to force cycling the connection.
	 *
	 * @return Mysqli A newly opened connection to the database type requested.
	 */
	public static function manualConnectionFailover($instanceType)
	{
		self::$instance->invalidate($instanceType);
		return self::instance($instanceType);
	}

	/**
	 * Force an open database connection to close.
	 *
	 * @param int $instanceType the type of database to force closed.
	 */
	public function invalidate($instanceType)
	{
		if(isset($this->connections[$instanceType])) {
			$this->connections[$instanceType]->close();
			unset($this->connections[$instanceType]);
		}
	}
}
