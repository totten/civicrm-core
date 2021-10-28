<?php

use React\EventLoop\Loop;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../CmsBootstrap.php';

if (!function_exists('queuebench_log_file')) {
  function queuebench_log_file() {
    return '/tmp/queuebench.txt';
  }
}

trait ChattyTrait {

  protected function verbose($msg, ...$args) {
    if (getenv('VERBOSE')) {
      $msg = sprintf('<%.3f> [#%d %s] ', microtime(1), posix_getpid(), static::CLASS) . $msg;
      call_user_func('printf', $msg, ...$args);
    }
  }

}

abstract class BaseBenchmark {

  use ChattyTrait;

  /**
   * @var int|null
   */
  public $maxConcurrentTasks = 1;

  /**
   * @var \React\EventLoop\TimerInterface
   */
  protected $timer;

  /**
   * @var CRM_Queue_Task[]
   */
  protected $pendingTasks = [];

  /**
   * @var CRM_Queue_Task[]
   */
  protected $activeTasks = [];

  /**
   * @var CRM_Queue_Task[]
   */
  protected $finishedTasks = [];

  /**
   * @var array
   */
  protected $expectedOutputs = [];

  /**
   * @var float
   */
  protected $startTime, $endTime;

  public function addTasks(iterable $tasks) {
    foreach ($tasks as $task) {
      $this->pendingTasks[] = $task;
    }
    return $this;
  }

  public function addExpectedOutputs(iterable $outputs) {
    foreach ($outputs as $output) {
      $this->expectedOutputs[] = $output;
    }
    return $this;
  }

  public function bootApp() {
    if (Civi\Core\Container::isContainerBooted()) {
      throw new \RuntimeException('Error: The system somehow booted prematurely.');
    }
    \Civi\Cv\CmsBootstrap::singleton()->bootCms()->bootCivi();
  }

  public function start(array $options = []) {
    @unlink(queuebench_log_file());
    $this->startTime = microtime(1);
    $this->verbose("Start %s\n", get_class($this));
    if (!empty($options['bootApp'])) {
      $this->bootApp();
    }
    $this->timer = Loop::addPeriodicTimer(0.1, [$this, 'checkTasks']);
  }

  public function isComplete() {
    return empty($this->pendingTasks) && empty($this->activeTasks);
  }

  public function checkTasks() {
    if ($this->isComplete()) {
      return $this->stop();
    }

    if (count($this->activeTasks) < $this->maxConcurrentTasks && !empty($this->pendingTasks)) {
      /** @var \CRM_Queue_Task $task */
      $task = array_shift($this->pendingTasks);

      $this->verbose("Running task: %s\n", json_encode($task));
      $this->activeTasks[] = $task;
      $this->runTask($task)->then(
        function () use ($task) {
          unset($this->activeTasks[array_search($task, $this->activeTasks)]);
          $this->finishedTasks[] = $task;
          $this->verbose("Finished task: %s\n", json_encode($task));
        },
        function () use ($task) {
          unset($this->activeTasks[array_search($task, $this->activeTasks)]);
          $this->finishedTasks[] = $task;
          $this->verbose("Failed task: %s\n", json_encode($task));
        }
      );
    }
  }

  abstract public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface;

  public function stop() {
    Loop::cancelTimer($this->timer);
    $this->endTime = microtime(1);

    $actualOutputs = file_exists(queuebench_log_file()) ? explode("\n",  file_get_contents(queuebench_log_file())) : [];
    sort($actualOutputs);
    sort($this->expectedOutputs);
    $actualOutputs = preg_grep('/^$/', $actualOutputs, PREG_GREP_INVERT);
    $common = array_intersect($actualOutputs, $this->expectedOutputs);
    $extra = array_diff($actualOutputs, $this->expectedOutputs);
    $missing = array_diff($this->expectedOutputs, $actualOutputs);
    $isPassing = empty($extra) && empty($missing);

    $report = [
      'outcome' => $isPassing ? 'pass' : 'fail',
      'class' => get_class($this),
      'setup_maxWorkers' => $this->maxConcurrentTasks,
      'setup_taskCount' => count($this->finishedTasks),
      'measure_runTime' => sprintf('%.4f', $this->endTime - $this->startTime),
      'measure_tasksPerSecond' => count($this->finishedTasks) / ($this->endTime - $this->startTime),
      // We give this a funny name. It's not really "time per task", because tasks are parallel and may individually take longer.
      'measure_proratedSecondsPerTask' => sprintf('%.4f', ($this->endTime - $this->startTime) / count($this->finishedTasks)),
    ];
    fputcsv(STDOUT, array_keys($report));
    fputcsv(STDOUT, array_values($report));

    if (!$isPassing) {
      $this->verbose("%s Failure report: %s\n", get_class($this), print_r([
        'common' => implode(", ", $common),
        'extra' => implode(", ", $extra),
        'missing' => implode(", ", $missing),
      ], 1));
    }
  }

}

class SingleThreadBenchmark extends BaseBenchmark {

  public function start(array $options = []) {
    parent::start(['bootApp' => TRUE] /* boot once in local thread */);
  }

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {
    $task->run(new \CRM_Queue_TaskContext());

    $deferred = new React\Promise\Deferred();
    $deferred->resolve();
    return $deferred->promise();
  }

}

class ProcessPerTaskBenchmark extends BaseBenchmark {

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {
    $deferred = new React\Promise\Deferred();

    $cmd = sprintf('cv ev %s', escapeshellarg(
      'unserialize(getenv("BNCH_TASK"))->run(new CRM_Queue_TaskContext());'
    ));
    $this->verbose("Run: %s\n", json_encode($task));
    $this->verbose("   $ %s\n", $cmd);

    $process = new React\ChildProcess\Process($cmd, NULL,
      array_merge(getenv(), ['BNCH_TASK' => serialize($task)])
    );
    $process->start();
    $process->on('exit', function ($exitCode, $termSignal) use ($deferred, $cmd) {
      if ($exitCode === 0) {
        $this->verbose("Command failed: $cmd");
      }
      $deferred->resolve();
    });

    return $deferred->promise();
  }

}

class HttpPerTaskBenchmark extends BaseBenchmark {

  /**
   * @var \React\Http\Browser
   */
  protected $browser;

  public function start(array $options = []) {
    parent::start(['bootApp' => TRUE] /* need well-formed URLs and JWTs */);
    $this->browser = new \React\Http\Browser();
  }

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {
    /** @var \Civi\Crypto\CryptoJwt $jwt */
    $jwt = Civi::service('crypto.jwt');

    $url = CRM_Utils_System::url('civicrm/queuebench/task', NULL, TRUE, NULL, FALSE);
    $data = http_build_query([
      'jwtask' => $jwt->encode([
        'scope' => 'queuebench_task',
        'exp' => time() + 36000,
        'queuebench_task' => (array) $task,
      ]),
    ]);

    $this->verbose("curl -X POST -d %s %s\n", escapeshellarg($data), escapeshellarg($url));

    return $this->browser->post($url, ['Content-type' => 'application/x-www-form-urlencoded'], $data);
  }

}

class ForkPerTaskBenchmark extends BaseBenchmark {

  const INTERVAL = 0.1; // 0.01

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {

    $deferred = new React\Promise\Deferred();

    $childProcess = pcntl_fork();
    if ($childProcess === -1) {
      throw new \RuntimeException('Cannot fork process');
    }
    elseif ($childProcess > 0) {
      // I am the parent. Watch the child.
      $forkTimer = Loop::addPeriodicTimer(self::INTERVAL, function () use ($childProcess, $deferred, &$forkTimer, $task) {
        if (pcntl_waitpid($childProcess, $status, WNOHANG)) {
          if (pcntl_wifexited($status)) {
            // $this->verbose("Cancel timer for task (%d): %s\n", $status, json_encode([$task->callback, $task->arguments]));
            Loop::cancelTimer($forkTimer);
            $deferred->resolve();
          }
        }
      });
      return $deferred->promise();
    }
    else {
      // I am the child.
      Loop::stop();
      $this->bootApp();
      $task->run(new CRM_Queue_TaskContext());
      // flush();
      exit(0);
    }
  }

}

class ForkPoolWorkerMain {

  use ChattyTrait;

  /**
   * @var resource
   */
  protected $stream;

  public function __construct($stream) {
    $this->stream = $stream;
  }

  public function main() {
    register_shutdown_function(function (){
      $this->verbose('Shutdown');
    });

    while (TRUE) {
      if (feof($this->stream)) {
        $this->verbose("Closed\n");
        $this->onQuit();
        return;
      }

      $this->verbose("Get line\n");
      $msg = stream_get_line($this->stream, 4096, "\n");
      $this->verbose("Received: %s\n", $msg);
      [$verb, $args] = $this->parseCmd($msg);
      switch ($verb) {
        case 'QUIT':
          $this->verbose("Quit");
          fwrite($this->stream, serialize("ACK") . "\n");
          $this->onQuit();
          return;

        case 'RUN':
          $task = unserialize($args);
          $this->verbose("Run it! %s\n", print_r($task, 1));
          $task->run(new CRM_Queue_TaskContext());
          fwrite($this->stream, serialize("ACK") . "\n");
          break;

        default:
          fwrite(STDERR, "Unrecognized command: $msg");
      }
    }

    $this->verbose("Done\n");
  }

  protected function parseCmd($msg) {
    $parts = explode(' ', $msg, 2);
    return [$parts[0], $parts[1] ?? NULL];
  }

  public function onQuit() {
    socket_close($this->stream);
    $this->stream = NULL;
  }

}

class ForkPoolWorkerStub {

  use ChattyTrait;

  const INTERVAL = 0.01;

  protected $stream;
  public $pid;
  protected $deferred;

  public function __construct($stream, $pid) {
    $this->stream = new \React\Stream\DuplexResourceStream($stream);
    $this->stream->on('data', [$this, 'onReceive']);
    $this->pid = $pid;
    $this->deferred = NULL;
  }

  public function stop() {
    if ($this->stream === NULL) {
      return;
    }
    $this->stream->write('QUIT');
    usleep(0.01);
    posix_kill($this->pid, SIGTERM);
    $this->stream->close();
    $this->stream = NULL;
  }

  public function isAvailable() {
    return $this->deferred === NULL;
  }

  public function run($cmd): \React\Promise\PromiseInterface {
    if (!$this->isAvailable()) {
      throw new \Exception("Cannot run command. Worker is busy.");
    }

    $msg = serialize($cmd) . "\n";

    $this->deferred = new React\Promise\Deferred();

    if (pcntl_waitpid($this->pid, $status, WNOHANG)) {
      if (pcntl_wifexited($status)) {
        $this->verbose("Worker disappeared. Cannot send: %s\n", $msg);
        return $this->deferred->reject();
      }
    }

    $this->verbose("Send %s\n", $msg);
    $this->stream->write('RUN ' . $msg);
    return $this->deferred->promise();
  }

  public function onReceive($data) {
    if ($data === NULL || $data === '') {
      // $this->verbose("[%s @ %d]: Ignore blank %s\n", static::CLASS, posix_getpid(), $data);
      return;
    }
    if ($this->deferred) {
      $this->verbose("Received %s\n", $data);
      $oldDeferred = $this->deferred;
      $this->deferred = NULL;
      $oldDeferred->resolve();
    }
    else {
      $this->verbose("Ignore unexpected message %s\n", $data);
    }
  }

}

class ForkPoolBenchmark extends BaseBenchmark {

  protected $workerStubs = [];

  public function start(array $options = []) {
    parent::start(['bootApp' => FALSE] /* only child boots */);

    for ($i = 0; $i < $this->maxConcurrentTasks; $i++) {
      $sockets = stream_socket_pair(AF_UNIX, SOCK_STREAM, 0);

      $childProcess = pcntl_fork();
      if ($childProcess === -1) {
        throw new \RuntimeException('Cannot fork process');
      }
      elseif ($childProcess > 0) {
        $this->workerStubs[$i] = new ForkPoolWorkerStub($sockets[0], $childProcess);
      }
      else {
        Loop::stop();
        $this->bootApp();
        (new ForkPoolWorkerMain($sockets[1]))->main();
        exit(0);
      }
    }

  }

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {
    foreach ($this->workerStubs as $worker) {
      /** @var \ForkPoolWorkerStub $worker */
      if ($worker->isAvailable()) {
        return $worker->run($task);
      }
    }
  }

  public function stop() {
    parent::stop();
    $oldWorkers = $this->workerStubs;
    $this->workerStubs = [];
    foreach ($oldWorkers as $id => $worker) {
      /** @var \ForkPoolWorkerStub $worker */
      $this->verbose("Stop worker #%d (pid %d)\n", $id, $worker->pid);
      $worker->stop();
    }
  }

}

eval(`cv php:boot --level=classloader`);
//eval(`cv php:boot`);
switch ($class = getenv('CLASS')) {
  case 'SingleThreadBenchmark':
  case 'ProcessPerTaskBenchmark':
  case 'ForkPerTaskBenchmark':
  case 'ForkPoolBenchmark':
  case 'HttpPerTaskBenchmark':
    /** @var \BaseBenchmark $benchmark */
    $benchmark = new $class();
    $benchmark->maxConcurrentTasks = getenv('MAX_WORKERS') ?: 3;
    $taskCount = getenv('TASK_COUNT') ?: 10;
    $benchmark->addTasks(array_map(
      // `queuebench_doSomething()` has to be defined in the main module file.
      function($num) { return new CRM_Queue_Task('queuebench_doSomething', [$num]); },
      range(1, $taskCount)
    ));
    $benchmark->addExpectedOutputs(range(1, $taskCount));
//    print_r($benchmark);exit();
    $benchmark->start();
//    Loop::run();
    break;
  default:
    throw new \Exception('Unrecognized benchmark class');
}
