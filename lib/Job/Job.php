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
class Job
{
	/**
	 * Job arguments
	 * @var array
	 */
	public $args;

	/**
	 * Associated JobHandler instance
	 * @var JobHandler
	 */
	public $job;

	/**
	 * Name of the queue the job was in
	 * @var string
	 */
	public $queue;

	/**
	 * Unique job ID
	 * @var string
	 */
	public $resque_job_id;
}
