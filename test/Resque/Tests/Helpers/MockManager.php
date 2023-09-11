<?php

namespace Resque\Tests\Helpers;

use DateTime;
use Resque\JobHandler;
use Resque\Manager;
use Resque\ManagerInterface;

/**
 * Manager that performs no actions
 *
 * @package		Resque
 * @author		Sean Molenaar <sean.molenaar@moveagency.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class MockManager extends Manager
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		//NO-OP
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
		//NO-OP
	}

	/**
	 * Given a host/port combination separated by a colon, set it as
	 * the redis server that Resque will talk to.
	 *
	 * @param mixed $server Host/port combination separated by a colon, DSN-formatted URI, or
	 *                      a callable that receives the configured database ID
	 *                      and returns a Resque\Redis instance, or
	 *                      a nested array of servers with host/port pairs.
	 * @param int $database
	 * @param string $auth
	 */
	public function setBackend($server, $database = 0, $auth = null)
	{
		//NO-OP
	}

	/**
	 * Create a new job and save it to the specified queue.
	 *
	 * @param string $queue The name of the queue to place the job in.
	 * @param string $class The name of the class that contains the code to execute the job.
	 * @param array $args Any optional arguments that should be passed when the job is executed.
	 * @param boolean $trackStatus Set to true to be able to monitor the status of a job.
	 * @param string $prefix The prefix needs to be set for the status key
	 *
	 * @return string|boolean Job ID when the job was created, false if creation was cancelled due to beforeEnqueue
	 */
	public function enqueue($queue, $class, $args = null, $trackStatus = false, $prefix = "")
	{
		return ''; //NO-OP
	}

	/**
	 * Remove items of the specified queue
	 *
	 * @param string $queue The name of the queue to fetch an item from.
	 * @param array $items
	 *
	 * @return integer number of deleted items
	 */
	public function dequeue($queue, $items = array())
	{
		return 0; //NO-OP
	}

	/**
	 * Remove specified queue
	 *
	 * @param string $queue The name of the queue to remove.
	 *
	 * @return integer Number of deleted items
	 */
	public function removeQueue($queue)
	{
		return 0; //NO-OP
	}

	/**
	 * Return the size (number of pending jobs) of the specified queue.
	 *
	 * @param string $queue name of the queue to be checked for pending jobs
	 *
	 * @return int The size of the queue.
	 */
	public function size($queue)
	{
		return 0; //NO-OP
	}

	/**
	 * Reserve and return the next available job in the specified queue.
	 *
	 * @param string $queue Queue to fetch next available job from.
	 *
	 * @return JobHandler|false Instance of Resque\JobHandler to be processed, false if none or error.
	 */
	public function reserve($queue)
	{
		return FALSE; //NO-OP
	}

	/**
	 * Get an array of all known queues.
	 *
	 * @return array Array of queues.
	 */
	public function queues()
	{
		return [];
	}

	/**
	 * Retrieve all the items of a queue with Redis
	 *
	 * @return array Array of items.
	 */
	public function items($queue, $start = 0, $stop = -1)
	{
		return [];
	}

	/**
	 * Enqueue a job in a given number of seconds from now.
	 *
	 * Identical to Resque::enqueue, however the first argument is the number
	 * of seconds before the job should be executed.
	 *
	 * @param int $in Number of seconds from now when the job should be executed.
	 * @param string $queue The name of the queue to place the job in.
	 * @param string $class The name of the class that contains the code to execute the job.
	 * @param array $args Any optional arguments that should be passed when the job is executed.
	 */
	public function enqueueIn($in, $queue, $class, array $args = array())
	{
		//NO-OP
	}

	/**
	 * Enqueue a job for execution at a given timestamp.
	 *
	 * Identical to Resque::enqueue, however the first argument is a timestamp
	 * (either UNIX timestamp in integer format or an instance of the DateTime
	 * class in PHP).
	 *
	 * @param DateTime|int $at Instance of PHP DateTime object or int of UNIX timestamp.
	 * @param string $queue The name of the queue to place the job in.
	 * @param string $class The name of the class that contains the code to execute the job.
	 * @param array $args Any optional arguments that should be passed when the job is executed.
	 */
	public function enqueueAt($at, $queue, $class, $args = array())
	{
		//NO-OP
	}

	/**
	 * Get the total number of jobs in the delayed schedule.
	 *
	 * @return int Number of scheduled jobs.
	 */
	public function getDelayedQueueScheduleSize()
	{
		return 0;
	}

	/**
	 * Get the number of jobs for a given timestamp in the delayed schedule.
	 *
	 * @param DateTime|int $timestamp Timestamp
	 * @return int Number of scheduled jobs.
	 */
	public function getDelayedTimestampSize($timestamp)
	{
		return 0;
	}

	/**
	 * Remove a delayed job from the queue
	 *
	 * note: you must specify exactly the same
	 * queue, class and arguments that you used when you added
	 * to the delayed queue
	 *
	 * also, this is an expensive operation because all delayed keys have tobe
	 * searched
	 *
	 * @param $queue
	 * @param $class
	 * @param $args
	 * @return int number of jobs that were removed
	 */
	public function removeDelayed($queue, $class, $args)
	{
		return 0;
	}

	/**
	 * removed a delayed job queued for a specific timestamp
	 *
	 * note: you must specify exactly the same
	 * queue, class and arguments that you used when you added
	 * to the delayed queue
	 *
	 * @param $timestamp
	 * @param $queue
	 * @param $class
	 * @param $args
	 * @return mixed
	 */
	public function removeDelayedJobFromTimestamp($timestamp, $queue, $class, $args)
	{
		return 0;
	}
}
