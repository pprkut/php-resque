<?php

namespace Resque;

/**
 * Base Resque class.
 *
 * @package		Resque
 * @author		Heinz Wiesinger <pprkut@liwjatan.org>
 * @license		http://www.opensource.org/licenses/mit-license.php
 * 
 * @phpstan-import-type Args from Scheduler
 */
class Manager
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
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
		Resque::setBackend($server, $database, $auth);
	}

	/**
	 * Create a new job and save it to the specified queue.
	 *
	 * @param string $queue The name of the queue to place the job in.
	 * @param class-string $class The name of the class that contains the code to execute the job.
	 * @param Args $args Any optional arguments that should be passed when the job is executed.
	 * @param boolean $trackStatus Set to true to be able to monitor the status of a job.
	 * @param string $prefix The prefix needs to be set for the status key
	 *
	 * @return string|boolean Job ID when the job was created, false if creation was cancelled due to beforeEnqueue
	 */
	public function enqueue($queue, $class, array $args = [], $trackStatus = false, $prefix = "")
	{
		return Resque::enqueue($queue, $class, $args, $trackStatus, $prefix);
	}

	/**
	 * Remove items of the specified queue
	 *
	 * @param string $queue The name of the queue to fetch an item from.
	 * @param array $items
	 * @return integer number of deleted items
	 */
	public function dequeue($queue, $items = array())
	{
		return Resque::dequeue($queue, $items);
	}

	/**
	 * Remove specified queue
	 *
	 * @param string $queue The name of the queue to remove.
	 * @return integer Number of deleted items
	 */
	public function removeQueue($queue)
	{
		return Resque::removeQueue($queue);
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
		return Resque::size($queue);
	}

	/**
	 * Reserve and return the next available job in the specified queue.
	 *
	 * @param string $queue Queue to fetch next available job from.
	 * @return \Resque\JobHandler Instance of Resque\JobHandler to be processed, false if none or error.
	 */
	public function reserve($queue)
	{
		return Resque::reserve($queue);
	}

	/**
	 * Get an array of all known queues.
	 *
	 * @return array Array of queues.
	 */
	public function queues()
	{
		return Resque::queues();
	}

	/**
	 * Retrieve all the items of a queue with Redis
	 *
	 * @return array Array of items.
	 */
	public function items($queue, $start = 0, $stop = -1)
	{
		return Resque::items($queue, $start, $stop);
	}

	/**
	 * Enqueue a job in a given number of seconds from now.
	 *
	 * Identical to Resque::enqueue, however the first argument is the number
	 * of seconds before the job should be executed.
	 *
	 * @param int $in Number of seconds from now when the job should be executed.
	 * @param string $queue The name of the queue to place the job in.
	 * @param class-string $class The name of the class that contains the code to execute the job.
	 * @param Args $args Any optional arguments that should be passed when the job is executed.
	 */
	public function enqueueIn($in, $queue, $class, array $args = array())
	{
		Scheduler::enqueueIn($in, $queue, $class, $args);
	}

	/**
	 * Enqueue a job for execution at a given timestamp.
	 *
	 * Identical to Resque::enqueue, however the first argument is a timestamp
	 * (either UNIX timestamp in integer format or an instance of the DateTime
	 * class in PHP).
	 *
	 * @param \DateTime|int $at Instance of PHP DateTime object or int of UNIX timestamp.
	 * @param string $queue The name of the queue to place the job in.
	 * @param class-string $class The name of the class that contains the code to execute the job.
	 * @param Args $args Any optional arguments that should be passed when the job is executed.
	 */
	public function enqueueAt($at, $queue, $class, $args = array())
	{
		Scheduler::enqueueAt($at, $queue, $class, $args);
	}

	/**
	 * Get the total number of jobs in the delayed schedule.
	 *
	 * @return int Number of scheduled jobs.
	 */
	public function getDelayedQueueScheduleSize()
	{
		return Scheduler::getDelayedQueueScheduleSize();
	}

	/**
	 * Get the number of jobs for a given timestamp in the delayed schedule.
	 *
	 * @param \DateTime|int $timestamp Timestamp
	 * @return int Number of scheduled jobs.
	 */
	public function getDelayedTimestampSize($timestamp)
	{
		return Scheduler::getDelayedTimestampSize($timestamp);
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
	 * @param string $queue
	 * @param class-string $class
	 * @param Args $args
	 * @return int number of jobs that were removed
	 */
	public function removeDelayed($queue, $class, $args)
	{
		return Scheduler::removeDelayed($queue, $class, $args);
	}

	/**
	 * removed a delayed job queued for a specific timestamp
	 *
	 * note: you must specify exactly the same
	 * queue, class and arguments that you used when you added
	 * to the delayed queue
	 *
	 * @param \DateTime|int $timestamp
	 * @param string $queue
	 * @param class-string $class
	 * @param Args $args
	 * @return mixed
	 */
	public function removeDelayedJobFromTimestamp($timestamp, $queue, $class, $args)
	{
		return Scheduler::removeDelayedJobFromTimestamp($timestamp, $queue, $class, $args);
	}
}
