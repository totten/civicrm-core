<?php

namespace Civi\Test;

use Civi\Test\LocalHttpClient\ClassProps;
use Civi\Test\LocalHttpClient\SuperGlobal;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * (Experimental) Send HTTP-style requests directly to CRM_Core_Invoke (PSR-18 ClientInterface).
 * This allows you to process many requests within the same "page", which can be useful for
 * headless unit-testing.
 *
 * $c = new LocalHttpClient(['reboot' => FALSE]);
 * $response = $c->sendRequest(new Request('GET', '/civicrm/foo?reset=1&bar=100'));
 * $response = $c->sendRequest(new Request('GET', '/civicrm/whiz?reset=1&bang=200'));
 *
 * Many CiviCRM classes predate the PSR HTTP OOP conventions -- they're built around $_GET, $_REQUEST, etc.
 * To simulate an HTTP request to these, we will need to swap-in and swap-out values for $_GET, $_REQUEST, etc.
 * Consequently, there is some limited isolation between the parent/caller and child/request.
 *
 * You can improve the isolation more with `reboot=>TRUE`. This will swap (and reinitialize)
 * the CiviCRM runtime-config and service-container. However, there is no comprehensive option to
 * swap all static properties throughout the system.
 *
 * In theory, this could be the basis for headless HTTP testing with client-libraries like Guzzle, Mink, or BrowserKit.
 *
 * @link https://www.php-fig.org/psr/psr-18/
 */
class LocalHttpClient implements ClientInterface {

  protected array $scopes;

  protected array $inherit;

  /**
   * @param array $options
   *   - reboot (bool): TRUE if you want to re-bootstrap CiviCRM (config/container) on each request
   *     Default: FALSE
   *   - globals (string[]): List of (super)globals that should be backed-up, populated, used, and restored.
   *     Default: ['_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_REQUEST']
   *   - inherit (string[]): When populating these (super)globals, build on top of the existing values.
   *     Default: ['_COOKIE', '_SERVER']
   */
  public function __construct(array $options = []) {
    $defaultOptions = [
      'reboot' => FALSE,
      'globals' => ['_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_REQUEST'],
      'inherit' => ['_COOKIE', '_SERVER'],
    ];
    $options = array_merge($defaultOptions, $options);

    $this->inherit = $options['inherit'];
    $this->scopes = [];

    foreach ($options['globals'] as $scopeName) {
      $this->scopes[$scopeName] = new SuperGlobal($scopeName);
    }

    if ($options['reboot']) {
      $this->scopes['Civi'] = new ClassProps('Civi');
      $this->scopes['CRM_Core_Config'] = new ClassProps('Civi');
    }
  }

  public function sendRequest(RequestInterface $request): ResponseInterface {
    if (CIVICRM_UF !== 'UnitTests') {
      throw new \RuntimeException(sprintf("%s is only supported within headless tests", static::CLASS));
    }

    $backup = $this->getAllValues();
    try {
      $this->initScopes($request);

      $body = $this->invoke($_GET['q']);
      // FIXME: There's probably a way to instrument CRM_Utils_System_UnitTests to do this better.
      return new Response(200, [], $body);
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      if (isset($e->errorData['response'])) {
        return $e->errorData['response'];
      }
      // FIXME: There are some things which emit PrematureExitException but don't provide the $response object.
      // We should probably revise \CRM_Utils_System::redirect() and returnJsonResponse()
      else {
        throw $e;
      }
    }
    finally {
      $this->restoreAllValues($backup);
    }
  }

  protected function initScopes(RequestInterface $request) {
    foreach ($this->scopes as $scopeName => $scope) {
      if (!in_array($scope, $this->inherit)) {
        $scope->unsetKeys(array_keys($scope->getValues()));
      }

      $method = 'initValues' . $scopeName;
      $initValues = is_callable([$this, $method]) ? $this->$method($request) : [];
      $scope->setValues($initValues);
    }
    if (in_array('CRM_Core_Config', $this->scopes)) {
      \CRM_Core_Config::singleton();
    }
  }

  /**
   * Map data from the request to $_GET.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   * @return array
   */
  protected function initValues_GET(RequestInterface $request): array {
    $result = [];
    parse_str($request->getUri()->getQuery() ?: '', $result);
    if (!isset($result['q'])) {
      $result['q'] = ltrim($request->getUri()->getPath(), '/');
    }
    return $result;
  }

  /**
   * Map data from the request to $_POST.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   * @return array
   */
  protected function initValues_POST(RequestInterface $request): array {
    $result = [];
    if ($request->getMethod() === 'POST') {
      if (in_array('application/x-www-form-urlencoded', $request->getHeader('Content-Type'))) {
        $body = (string) $request->getBody();
        parse_str($body, $result);
      }
    }
    return $result;
  }

  /**
   * Map data from the request to $_REQUEST.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   * @return array
   */
  protected function initValues_REQUEST(RequestInterface $request): array {
    $sources = ['g' => '_GET', 'p' => '_POST', 'c' => '_COOKIE'];

    if (ini_get('request_order')) {
      $order = strtolower(ini_get('request_order'));
    }
    elseif (ini_get('variables_order')) {
      $order = strtolower(ini_get('variables_order'));
    }
    else {
      $order = 'gpc';
    }

    $result = [];
    for ($i = 0; $i < strlen($order); $i++) {
      if (isset($sources[$order[$i]])) {
        $scope = $this->scopes[$sources[$order[$i]]];
        $result = array_merge($result, $scope->getValues());
      }
    }
    return $result;
  }

  protected function initValues_SERVER(RequestInterface $request): array {
    $result = [];
    $result['REQUEST_METHOD'] = $request->getMethod();
    return $result;
  }

  protected function invoke(string $route): ?string {
    \CRM_Core_Resources::singleton()->addCoreResources('html-header');

    ob_start();
    try {
      $pageContent = \CRM_Core_Invoke::_invoke(explode('/', $route));
    }
    finally {
      $printedContent = ob_get_clean();
    }

    if (empty($pageContent) && !empty($printedContent)) {
      $pageContent = $printedContent;
    }

    $locale = \CRM_Core_I18n::getLocale();
    $lang = substr($locale, 0, 2);
    $dir = \CRM_Core_I18n::isLanguageRTL($locale) ? 'rtl' : 'ltr';
    $head = \CRM_Core_Region::instance('html-header')->render('');

    return <<<PAGETPL
<!DOCTYPE html>
<html lang="$lang" dir="$dir">
<head>$head</head>
<body class="civicrm-unittest-body">$pageContent</body>
</html>
PAGETPL;
  }

  public function getAllValues(): array {
    $backup = [];
    foreach ($this->scopes as $scopeName => $scope) {
      $backup[$scopeName] = $scope->getValues();
    }
    return $backup;
  }

  protected function restoreAllValues(array &$backup): void {
    foreach ($this->scopes as $scopeName => $scope) {
      $extraKeys = array_diff(array_keys($scope->getValues()), array_keys($backup[$scopeName]));
      $scope->unsetKeys($extraKeys);
      $scope->setValues($backup[$scopeName]);
    }
  }

}
