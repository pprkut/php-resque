<?php

namespace Resque\Job;

use Resque\JobHandler;

/**
 * Base Resque Job class.
 *
 * @package		Resque\Job
 * @author		Heinz Wiesinger <pprkut@liwjatan.org>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
abstract class Job
{
	/**
	 * Job arguments
	 * @var array
	 */
	public array $args;

	/**
	 * Associated JobHandler instance
	 * @var JobHandler
	 */
	public JobHandler $job;

	/**
	 * Name of the queue the job was in
	 * @var string
	 */
	public string $queue;

	/**
	 * Unique job ID
	 * @var string
	 */
	public string $resque_job_id;

	/**
	 * @return void
	 */
	abstract public function perform();
}
