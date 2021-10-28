<?php
use CRM_Queuebench_ExtensionUtil as E;

class CRM_Queuebench_Page_QueueJson extends CRM_Core_Page {

  public function run() {
    /** @var \Civi\Crypto\CryptoJwt $jwt */
    $jwt = Civi::service('crypto.jwt');
    $props = $jwt->decode($_REQUEST['jwtask']);
    if ($props['scope'] !== 'queuebench_task') {
      throw new \RuntimeException("Wrong kind of token");
    }

    $task = new CRM_Queue_Task(NULL, NULL);
    foreach ($props['queuebench_task'] as $key => $value) {
      $task->{$key} = $value;
    }

    $task->run(new CRM_Queue_TaskContext());

//    echo 'OK';
    CRM_Utils_System::sendResponse(new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'text/plain'], "OK\n"));
  }

}
