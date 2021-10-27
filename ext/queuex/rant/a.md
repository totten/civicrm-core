# Overview

Citges is a PHP process-manager for CiviCRM which:

* Listens for tasks on a SQL queue (and, potentially, listens for HTTP tasks).
* Makes significant use of forked processes to isolate globals and statics.
* Isolates security contexts, running separate workers for different web-domains/Civi-users.
* Re-uses workers for multiple tasks.
* Limits the lifespan of workers, mitigating the risk of resource leaks.

# Glossary

* __`Task`__: A unit of work received from the queue.
* __`Worker`__: A PHP process that executes tasks.
    * A worker fully bootstraps Civi+CMS one time and then executes tasks.
    * A worker is a mortal process. It runs (eg) 1-20 tasks over 0-10 min, and then it dies.
    * A worker is attached to a specific revision of the source-code, a specific domain, and a specific user.
    * There are multiple types of workers:
        * A `ForkWorker` runs in a subprocess. `ForkWorker` requires CLI SAPI.
        * An `HttpWorker` runs with an HTTP request.
* __`Manager`__: A PHP process that starts workers, monitors a queue, and passes the jobs to workers.
    * ~~A manager loads Civi+CMS source code, and it connects to MySQL, but it does not perform a full bootstrap.~~
    * A manager is a mortal process. It runs (eg) 10-30 min, and then it dies.
    * A manager is attached to a specific revision of the source-code and a specific domain.
* __`Governor`__: A PHP process that starts and stops managers.
     * A governor monitors POSIX signals. It optionally tracks PID files.
     * There are multiple types of governors:
         * A `PartyGovernor` runs many managers. It is immortal. It optionally monitors source changes. `PartyGovernor` requires CLI SAPI.
         * A `SoloGovernor` runs a single manager. It is mortal. It does not monitor source changes.
* __`Moribund`__:
    * Any process that is destined to die. It may finish pending work but will decline new work.

# Scenarios

* __Cheap Hosting__: Run a `SoloGovernor` with `HttpWorker` via cron+HTTP
* __Dedicated Hosting__: Run a `SoloGovernor` with `ForkWorker` via cron+CLI
* __Multitenant Hosting__ Run an `ImmortalGovernor` for a multisite deployment.

# Configuration

```
worker_task_limit=(int)		After completing $X tasks, the worker will be moribund.
worker_ttl=(int)		After running for $X seconds, the worker will be moribund.
manager_ttl=(int)		After running for $X seconds, the worker will be moribund.
manager_queue_interval=(int)	After $X seconds, check the queue for new tasks
governor_src_interval=(int)	After $X seconds, check for file updates in the source tree. If $srcId changes, then all workers+templates are moribund.
governor_manager_limit=(int)
governor_worker_limit=(int)
governor_task_limit=(int)
```

==========

```
$ cv api4 Queue.run max_tasks=100 max_lifetime=600
$ curl -X POST 'https://sysuser:syspass@example.com/civicrm/ajax/api4/Queue/run?max_tasks=10&max_lifetime=90'
$ cv queue:start
$ cv queue:stop
```

==========

- Can we rename "Daemon" so that it's clearer that this works with cron-style?
- If you use the `cv api4 Queue.run` to start, then the Daemon has to bootstrap Civi and take on permissions
	==> Maybe we actually want to think of "Queue.run" as starting a "Manager" not a "daemon"

==========

```php
class Task {}
class Worker {
  public string $srcId, $domainId, $userId;
  public bool $moribund, $busy;
  protected $channels;

  public function isOpen() { return !$moribund && !$busy; }
  public function setMoribund() {}
}
class WorkerManager {
  public string $srcId, $domainId;
  public $pid, $moribund;
  protected $channels;

  public function isOpen() { return !$moribund; }
  public function setMoribund() {$this->moribund = true; foreach ($worker) $worker->setMoribund(); }
}
class Daemon {
  public array $workers;
  public array $workerManagers;
  public function useWorker(srcId, $domainId, $userId) {
    if (find $worker with isOpen()=true) { return existing; }
    else { return $this->newWorker();  }
  }
  public function useWorkerManager($srcId, $domainId) {
    if (find $workerManager with isOpen()=true) { return existing }
    else { return $this->newWorkerManager($srcId, $domainId) }
  }
  public function getWorker(srcId, domainId, userId) {}
  public function getWorkerManager(srcId, domainId) {}
  public function newWorker(srcId, domainId, userId) {}
  public function newWorkerManager(srcId, domainId) {}

  public function onPoll() {

  }
}
```