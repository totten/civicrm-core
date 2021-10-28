<?php

use React\EventLoop\Loop;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../CmsBootstrap.php';

trait ChattyTrait {

  protected function verbose($msg, ...$args) {
    $msg = sprintf('[#%d %s] ', posix_getpid(), static::CLASS) . $msg;
    call_user_func('printf', $msg, ...$args);
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
   * @var float
   */
  protected $startTime, $endTime;

  public function addTasks(iterable $tasks) {
    foreach ($tasks as $task) {
      $this->pendingTasks[] = $task;
    }
  }

  public function start() {
    $this->startTime = microtime(1);
    $this->verbose("Start %s\n", get_class($this));
    $this->timer = Loop::addPeriodicTimer(0.1, [$this, 'checkTasks']);
  }

  public function checkTasks() {
    if (empty($this->pendingTasks) && empty($this->activeTasks)) {
      return $this->stop();
    }

    if (count($this->activeTasks) < $this->maxConcurrentTasks && !empty($this->pendingTasks)) {
      /** @var \CRM_Queue_Task $task */
      $task = array_shift($this->pendingTasks);

      $this->verbose("Running task: %s\n", json_encode($task));
      $this->activeTasks[] = $task;
      $this->runTask($task)->then(function () use ($task) {
        unset($this->activeTasks[array_search($task, $this->activeTasks)]);
        $this->finishedTasks[] = $task;
        $this->verbose("Finished task: %s\n", json_encode($task));
      });
    }
  }

  abstract public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface;

  public function stop() {
    Loop::cancelTimer($this->timer);
    $this->endTime = microtime(1);
    printf("Report (%s)\n", get_class($this));
    printf("- Total runtime: %.4f\n", $this->endTime - $this->startTime);
    // Misleading printf("- Time per item: %.4f\n", ($this->endTime - $this->startTime) / count($this->finishedTasks));
    printf("- Items per second: %.4f\n", count($this->finishedTasks) / ($this->endTime - $this->startTime));
  }

}

class ThreadLocalBenchmark extends BaseBenchmark {

  public function start() {
    \Civi\Cv\CmsBootstrap::singleton()->bootCms()->bootCivi();
    parent::start();
  }

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {
    $task->run(new CRM_Queue_TaskContext());

    $deferred = new React\Promise\Deferred();
    $deferred->resolve();
    return $deferred->promise();
  }

}

class ShellProcessBenchmark extends BaseBenchmark {

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {
    $deferred = new React\Promise\Deferred();

    $cmd = sprintf('cv ev %s', escapeshellarg(
      sprintf('unserialize(%s)->run(new CRM_Queue_TaskContext())', serialize($task))
    ));
    $this->verbose("Run: %s\n", json_encode($task));
    $this->verbose("   $ %s\n", $cmd);

    $process = new React\ChildProcess\Process($cmd);
    $process->start();
    $process->on('exit', function ($exitCode, $termSignal) use ($deferred) {
      $deferred->resolve();
    });

    return $deferred->promise();
  }

}

class ForkPerTaskBenchmark extends BaseBenchmark {

  const INTERVAL = 0.1;

  public function runTask(CRM_Queue_Task $task): \React\Promise\PromiseInterface {

    $deferred = new React\Promise\Deferred();

    $childProcess = pcntl_fork();
    if ($childProcess === -1) {
      throw new \RuntimeException('Cannot fork process');
    }
    elseif ($childProcess > 0) {
      // I am the parent. Watch the child.
      $forkTimer = Loop::addPeriodicTimer(self::INTERVAL, function () use ($childProcess, $deferred, &$forkTimer) {
        pcntl_waitpid($childProcess, $status, WNOHANG);
        if (pcntl_wifexited($status)) {
          Loop::cancelTimer($forkTimer);
          $deferred->resolve();
        }
      });
      return $deferred->promise();
    }
    else {
      // I am the child.
      Loop::stop();
      \Civi\Cv\CmsBootstrap::singleton()->bootCms()->bootCivi();
      $task->run(new CRM_Queue_TaskContext());
      exit(0);
    }
  }

}

class ForkPoolWorkerMain {

  use ChattyTrait;

  protected $rx, $tx;

  public function __construct($rx, $tx) {
    $this->rx = $rx;
    $this->tx = $tx;
  }

  public function main() {
    register_shutdown_function(function (){
      $this->verbose('Shutdown');
    });
    $this->verbose("Booting\n");
//    \Civi\Cv\CmsBootstrap::singleton()->bootCms()->bootCivi();

    while (TRUE) {
//      if (feof($this->rx)) {
//        $this->verbose("Closed\n");
//        $this->onQuit();
//        return;
//      }

      $this->verbose("Get line\n");
      $msg = stream_get_line($this->rx, 4096);
      $this->verbose("Received: %s\n", $msg);
//      [$verb, $args] = $this->parseCmd($msg);
//      switch ($verb) {
//        case 'QUIT':
//          printf("[%s @ %d]: Quit\n", static::CLASS, posix_getpid());
//          fwrite($this->tx, serialize("ACK") . "\n");
//          $this->onQuit();
//          return;
//
//        case 'RUN':
//          // TODO: unserialize, execute, respond
//          //      sleep(1);
//          $task = unserialize($args);
//          print_r($task);
//          printf("[%s @ %d]: Run it!\n", static::CLASS, posix_getpid());
//          fwrite($this->tx, serialize("ACK") . "\n");
//          break;
//
//        default:
//          fwrite(STDERR, "Unrecognized command: $msg");
//      }
    }

    $this->verbose("Done\n");
  }

  protected function parseCmd($msg) {
    $parts = explode(' ', $msg, 2);
    return [$parts[0], $parts[1] ?? NULL];
  }

  public function onQuit() {
    socket_close($this->tx);
    socket_close($this->rx);
    $this->tx = $this->rx = NULL;
  }

}

class ForkPoolWorkerStub {

  use ChattyTrait;

  const INTERVAL = 0.1;

  protected $rx, $tx, $pid;

  protected $deferred;

  public function __construct($rx, $tx, $pid) {
    $this->rx = new \React\Stream\ReadableResourceStream($rx);
    $this->rx->on('data', [$this, 'onReceive']);
    $this->tx = new \React\Stream\WritableResourceStream($tx);
    $this->pid = $pid;
    $this->deferred = NULL;
  }

  public function stop() {
    if ($this->tx === NULL) {
      return;
    }
//    posix_kill($this->pid, SIGTERM);
//    $this->tx->write('QUIT');
//    $this->rx->close();
//    $this->tx->close();
//    $this->tx = $this->rx = NULL;
//    sleep(0.5);
//    posix_kill($this->pid, SIGKILL);
//    pcntl_signal()
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

    pcntl_waitpid($this->pid, $status, WNOHANG);
    if (pcntl_wifstopped($status)) {
      $this->verbose("Worker disappeared. Cannot send: %s\n", $msg);
      return $this->deferred->reject();
    }

    $this->verbose("Send %s\n", $msg);
    $this->tx->write('RUN ' . $msg);
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

  public function start() {

    for ($i = 0; $i < $this->maxConcurrentTasks; $i++) {
      $sockets = stream_socket_pair(AF_UNIX, SOCK_STREAM, 0);

      $childProcess = pcntl_fork();
      if ($childProcess === -1) {
        throw new \RuntimeException('Cannot fork process');
      }
      elseif ($childProcess > 0) {
        $this->workerStubs[$i] = new ForkPoolWorkerStub($sockets[1], $sockets[1], $childProcess);
      }
      else {
        Loop::stop();
        (new ForkPoolWorkerMain($sockets[1], $sockets[1]))->main();
        exit(0);
      }
    }

    parent::start();
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
    foreach ($oldWorkers as $worker) {
      /** @var \ForkPoolWorkerStub $worker */
      $worker->stop();
    }
  }

}

eval(`cv php:boot --level=classloader`);
//eval(`cv php:boot`);
switch ($class = getenv('CLASS')) {
  case 'ThreadLocalBenchmark':
  case 'ShellProcessBenchmark':
  case 'ForkPerTaskBenchmark':
  case 'ForkPoolBenchmark':
    /** @var \BaseBenchmark $benchmark */
    $benchmark = new $class();
    $benchmark->maxConcurrentTasks = getenv('MAX_WORKERS') ?: 3;
    $benchmark->addTasks(array_map(
      // `queuebench_doSomething()` has to be defined in the main module file.
      function($num) { return new CRM_Queue_Task('queuebench_doSomething', [1+$num]); },
      range(0, getenv('TASK_COUNT') ?: 10)
    ));
    $benchmark->start();
    Loop::run();
    break;
  default:
    throw new \Exception('Unrecognized benchmark class');
}
