<?php

/**
 * DBPool is a handler for one or more database connections built on top of mysqli.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sporcle\DB;

/**
 * Handles exceptions in the DBPool classes.
 *
 * @package Sporcle
 * @subpackage Mysqli
 * @author Dan Munro <dan@sporcle.com>
 */

class Exception extends \Exception
{
	const TYPE_NOT_DEFINED = 1;
	const WRITING_TO_SLAVE = 2;
	const NO_CONNECTION = 3;
}
