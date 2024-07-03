<?php
/**
 * Resque test bootstrap file - sets up a test environment.
 *
 * @package		Resque/Tests
 * @author		Chris Boulton <chris@bigcommerce.com>
 * @license		http://www.opensource.org/licenses/mit-license.php
 */

$loader = require __DIR__ . '/../vendor/autoload.php';

define('TEST_MISC', realpath(__DIR__ . '/misc/'));
define('REDIS_CONF', TEST_MISC . '/redis.conf');

// Attempt to start our own redis instance for tesitng.
exec('which redis-server', $output, $returnVar);
if($returnVar != 0) {
	echo "Cannot find redis-server in path. Please make sure redis is installed.\n";
	exit(1);
}

exec('cd ' . TEST_MISC . '; redis-server ' . REDIS_CONF, $output, $returnVar);
usleep(500000);
if($returnVar != 0) {
	echo "Cannot start redis-server.\n";
	exit(1);

}

// Get redis port from conf
$config = file_get_contents(REDIS_CONF);
if(!preg_match('#^\s*port\s+([0-9]+)#m', $config, $matches)) {
	echo "Could not determine redis port from redis.conf";
	exit(1);
}

\Resque\Resque::setBackend('localhost:' . $matches[1]);

// Shutdown
function killRedis($pid)
{
    if (getmypid() !== $pid) {
        return; // don't kill from a forked worker
    }
	$config = file_get_contents(REDIS_CONF);
	if(!preg_match('#^\s*pidfile\s+([^\s]+)#m', $config, $matches)) {
		return;
	}

	$pidFile = TEST_MISC . '/' . $matches[1];
	if (file_exists($pidFile)) {
		$pid = trim(file_get_contents($pidFile));
		posix_kill((int) $pid, 9);

		if(is_file($pidFile)) {
			unlink($pidFile);
		}
	}

	// Remove the redis database
	if(!preg_match('#^\s*dir\s+([^\s]+)#m', $config, $matches)) {
		return;
	}
	$dir = $matches[1];

	if(!preg_match('#^\s*dbfilename\s+([^\s]+)#m', $config, $matches)) {
		return;
	}

	$filename = TEST_MISC . '/' . $dir . '/' . $matches[1];
	if(is_file($filename)) {
		unlink($filename);
	}
}
register_shutdown_function('killRedis', getmypid());

if(function_exists('pcntl_signal')) {
	// Override INT and TERM signals, so they do a clean shutdown and also
	// clean up redis-server as well.
	function sigint()
	{
	 	exit;
	}
	pcntl_signal(SIGINT, 'sigint');
	pcntl_signal(SIGTERM, 'sigint');
}

class Test_Job extends \Resque\Job\Job
{
	public static $called = false;

	public function perform()
	{
		self::$called = true;
	}
}

class Returning_Job extends \Resque\Job\Job
{
	public function perform()
	{
		return $this->args['return'] ?? NULL;
	}
}

class Failing_Job_Exception extends \Exception
{

}

class Failing_Job extends \Resque\Job\Job
{
	public function perform()
	{
		throw new Failing_Job_Exception('Message!');
	}
}

/**
 * This job exits the forked worker process, which simulates the job being (forever) in progress,
 * so that we can verify the state of the system for "running jobs". Does not work on a non-forking OS.
 *
 * CAUTION Use this test job only with Worker::work, i.e. only when you actually trigger the fork in tests.
 */
class InProgress_Job extends \Resque\Job\Job
{
	public function perform()
	{
		if(!function_exists('pcntl_fork')) {
			// We can't lose the worker on a non-forking OS.
			throw new Failing_Job_Exception('Do not use InProgress_Job for tests on non-forking OS!');
		}
		exit(0);
	}
}

class Test_Job_With_SetUp extends \Resque\Job\Job
{
	public static $called = false;
	public $args = [];

	public function setUp(): void
	{
		self::$called = true;
	}

	public function perform()
	{

	}
}


class Test_Job_With_TearDown extends \Resque\Job\Job
{
	public static $called = false;
	public $args = [];

	public function perform()
	{

	}

	public function tearDown(): void
	{
		self::$called = true;
	}
}

class Test_Infinite_Recursion_Job extends \Resque\Job\Job
{
    public function perform()
    {
        $this->perform();
    }
}
