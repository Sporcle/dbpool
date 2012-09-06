<?php

require_once(__DIR__.'/../lib/autoloader.php');

/**
 * Define a child class of Sporcle\DB\Pool with a set of connection parameters
 * for your database(s).
 */
class MyDB extends Sporcle\DB\Pool
{
	const MASTER = 'master';
	const SLAVE = 'slave';

	protected function __construct()
	{
		$this->connectionParams = [
			self::MASTER => [
				['host' => '127.0.0.1',
				'user' => 'root',
				'pass' => 'password',
				'db' => 'dbname']
			],
			self::SLAVE => [
				['host' => '127.0.0.1',
				'user' => 'root',
				'pass' => 'password',
				'db' => 'dbname'],
				['host' => '127.0.0.1',
				'user' => 'root',
				'pass' => 'password',
				'db' => 'dbname']
			]
		];
		shuffle($this->connectionParams[self::SLAVE]);
	}
}

$sampleUserID = 1;

/**
 * Connect to a database
 */
$dbslave = MyDB::instance(MyDB::SLAVE);

/**
 * A simple query -- note susceptibility to sql injection
 */
$result = $dbslave->query("SELECT user_id, user_name FROM users WHERE user_id = $sampleUserID");
$rows = [];
while($row = $result->fetch_assoc()) {
	$rows[] = $row;
}
$result->free();

/**
 * An equivalent prepared statement -- no more susceptibility
 */
$stmt = $dbslave->prepare("SELECT user_id, user_name FROM users where user_id = ?");
$stmt->bind_param('i', $sampleUserID);
$stmt->bind_result($gameID, $gameName);
$stmt->execute();
$stmt->fetch();
$stmt->free_result();
$stmt->close();
