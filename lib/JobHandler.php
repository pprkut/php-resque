<?php

namespace Resque;

use Resque\Job\Status;
use Resque\Exceptions\DoNotPerformException;
use Resque\Job\FactoryInterface;
use Resque\Job\Factory;
use Resque\Job\Job;
use Resque\Worker\ResqueWorker;
use Error;

/**
 * Resque job.
 *
 * @package		Resque/JobHandler
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 * 
 * @phpstan-import-type Args from Scheduler
 */
class JobHandler
{
	/**
	 * @var string The name of the queue that this job belongs to.
	 */
	public string $queue;

	/**
	 * @var \Resque\Worker\ResqueWorker Instance of the Resque worker running this job.
	 */
	public ResqueWorker $worker;

	/**
	 * @var array<string, mixed> Array containing details of the job.
	 */
	public array $payload;

	/**
	 * @var float Timestamp of when the data was popped from redis.
	 */
	public float $pop_time;

	/**
	 * @var float Timestamp of when the job started processing.
	 */
	public float $start_time;

	/**
	 * @var float Timestamp of when the job finished processing.
	 */
	public float $end_time;

	/**
	 * @var Job Instance of the class performing work for this job.
	 */
	private Job $instance;

	/**
	 * @var \Resque\Job\FactoryInterface
	 */
	private FactoryInterface $jobFactory;

	/**
	 * Instantiate a new instance of a job.
	 *
	 * @param string $queue The queue that the job belongs to.
	 * @param array<string, mixed> $payload array containing details of the job.
	 */
	public function __construct($queue, $payload)
	{
		$this->queue = $queue;
		$this->payload = $payload;
		$this->pop_time = microtime(true);

		if (!isset($this->payload['id'])) {
			$this->payload['id'] = Resque::generateJobId();
		}
	}

	/**
	 * Create a new job and save it to the specified queue.
	 *
	 * @param string $queue The name of the queue to place the job in.
	 * @param class-string $class The name of the class that contains the code to execute the job.
	 * @param Args $args Any optional arguments that should be passed when the job is executed.
	 * @param boolean $monitor Set to true to be able to monitor the status of a job.
	 * @param string $id Unique identifier for tracking the job. Generated if not supplied.
	 * @param string $prefix The prefix needs to be set for the status key
	 *
	 * @return string Job ID
	 */
	public static function create($queue, $class, array $args = [], $monitor = false, $id = null, $prefix = "")
	{
		if (is_null($id)) {
			$id = Resque::generateJobId();
		}

		Resque::push($queue, array(
			'class'	     => $class,
			'args'	     => array($args),
			'id'	     => $id,
			'prefix'     => $prefix,
			'queue_time' => microtime(true),
		));

		if ($monitor) {
			Status::create($id, $prefix);
		}

		return $id;
	}

	/**
	 * Find the next available job from the specified queue and return an
	 * instance of JobHandler for it.
	 *
	 * @param string $queue The name of the queue to check for a job in.
	 * @return false|JobHandler False when there aren't any waiting jobs, instance of Resque\JobHandler when a job was found.
	 */
	public static function reserve($queue)
	{
		$payload = Resque::pop($queue);
		if (!is_array($payload)) {
			return false;
		}

		return new JobHandler($queue, $payload);
	}

	/**
	 * Find the next available job from the specified queues using blocking list pop
	 * and return an instance of JobHandler for it.
	 *
	 * @param array             $queues
	 * @param int               $timeout
	 * @return false|JobHandler False when there aren't any waiting jobs, instance of Resque\JobHandler when a job was found.
	 */
	public static function reserveBlocking(array $queues, $timeout = null)
	{
		$item = Resque::blpop($queues, $timeout);

		if (!is_array($item)) {
			return false;
		}

		return new JobHandler($item['queue'], $item['payload']);
	}

	/**
	 * Update the status of the current job.
	 *
	 * @param int       $status Status constant from Resque\Job\Status indicating the current status of a job.
	 * @param bool|null $result Result from the job's perform() method
	 */
	public function updateStatus($status, $result = null)
	{
		if (empty($this->payload['id'])) {
			return;
		}

		$statusInstance = new Status($this->payload['id'], $this->getPrefix());
		$statusInstance->update($status, $result);
	}

	/**
	 * Return the status of the current job.
	 *
	 * @return int|null The status of the job as one of the Resque\Job\Status constants
	 *                  or null if job is not being tracked.
	 */
	public function getStatus()
	{
		if (empty($this->payload['id'])) {
			return null;
		}

		$status = new Status($this->payload['id'], $this->getPrefix());
		return $status->get();
	}

	/**
	 * Get the arguments supplied to this job.
	 *
	 * @return Args Array of arguments.
	 */
	public function getArguments(): array
	{
		if (!isset($this->payload['args'])) {
			return array();
		}

		return $this->payload['args'][0];
	}

	/**
	 * Get the instantiated object for this job that will be performing work.
	 * @return \Resque\Job\Job Instance of the object that this job belongs to.
	 * @throws \Resque\Exceptions\ResqueException
	 */
	public function getInstance(): Job
	{
		if (isset($this->instance)) {
			return $this->instance;
		}

		$this->instance = $this->getJobFactory()->create($this->payload['class'], $this->getArguments(), $this->queue);
		$this->instance->job = $this;
		$this->instance->resque_job_id = $this->payload['id'];
		return $this->instance;
	}

	/**
	 * Actually execute a job by calling the perform method on the class
	 * associated with the job with the supplied arguments.
	 *
	 * @return bool
	 * @throws \Resque\Exceptions\ResqueException When the job's class could not be found
	 * 											  or it does not contain a perform method.
	 */
	public function perform()
	{
		$result = true;
		try {
			Event::trigger('beforePerform', $this);

			$this->start_time = microtime(true);

			$instance = $this->getInstance();
			if (is_callable([$instance, 'setUp'])) {
				$instance->setUp();
			}

			$instance->perform();

			if (is_callable([$instance, 'tearDown'])) {
				$instance->tearDown();
			}

			$this->end_time = microtime(true);

			Event::trigger('afterPerform', $this);
		} catch (DoNotPerformException $e) {
			// beforePerform/setUp have said don't perform this job. Return.
			$result = false;
		}

		return $result;
	}

	/**
	 * Mark the current job as having failed.
	 *
	 * @param \Throwable $exception
	 */
	public function fail($exception)
	{
		$this->end_time = microtime(true);

		Event::trigger('onFailure', array(
			'exception' => $exception,
			'job' => $this,
		));

		$this->updateStatus(Status::STATUS_FAILED);
		if ($exception instanceof Error) {
			FailureHandler::createFromError(
				$this->payload,
				$exception,
				$this->worker,
				$this->queue
			);
		} else {
			FailureHandler::create(
				$this->payload,
				$exception,
				$this->worker,
				$this->queue
			);
		}
		Stat::incr('failed');
		Stat::incr('failed:' . $this->worker);
	}

	/**
	 * Re-queue the current job.
	 * @return string new Job ID
	 */
	public function recreate()
	{
		$monitor = false;
		if (!empty($this->payload['id'])) {
			$status = new Status($this->payload['id'], $this->getPrefix());
			if ($status->isTracking()) {
				$monitor = true;
			}
		}

		return self::create(
			$this->queue,
			$this->payload['class'],
			$this->getArguments(),
			$monitor,
			null,
			$this->getPrefix()
		);
	}

	/**
	 * Generate a string representation used to describe the current job.
	 *
	 * @return string The string representation of the job.
	 */
	public function __toString()
	{
		$name = array(
			'Job{' . $this->queue . '}'
		);
		if (!empty($this->payload['id'])) {
			$name[] = 'ID: ' . $this->payload['id'];
		}
		$name[] = $this->payload['class'];
		if (!empty($this->payload['args'])) {
			$name[] = json_encode($this->payload['args']);
		}
		return '(' . implode(' | ', $name) . ')';
	}

	/**
	 * @param \Resque\Job\FactoryInterface $jobFactory
	 * @return \Resque\JobHandler
	 */
	public function setJobFactory(FactoryInterface $jobFactory)
	{
		$this->jobFactory = $jobFactory;

		return $this;
	}

	/**
	 * @return \Resque\Job\FactoryInterface
	 */
	public function getJobFactory(): FactoryInterface
	{
		if (!isset($this->jobFactory)) {
			$this->jobFactory = new Factory();
		}
		return $this->jobFactory;
	}

	/**
	 * @return string
	 */
	private function getPrefix()
	{
		if (isset($this->payload['prefix'])) {
			return $this->payload['prefix'];
		}

		return '';
	}
}
