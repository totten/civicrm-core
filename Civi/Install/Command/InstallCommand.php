<?php
namespace Civi\Install\Command;

use Civi\Install\Installer;
use Civi\Install\Requirements;
use Civi\Install\Settings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallCommand
 * @package Civi\Install\Command
 *
 * The advanced CiviCRM installer allows fine-grained control over the installation settings.
 */
class InstallCommand extends Command {
  protected function configure() {
    $this
      ->setName('install')
      ->setDescription('Advanced CiviCRM installer')
      ->setHelp('The advanced CiviCRM installer allows fine-grained control over the installation settings.');
    $settings = $this->createDefaultSettings();
    $this->addOptionsFromObject($settings);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $settings = $this->createDefaultSettings();
    $this->mergeOptionsIntoObject($input, $settings);
    $settings->fill();

    if ($output->getVerbosity() > 1) {
      $output->writeln("<info>[[ Settings ]]</info>");
      $output->writeln(print_r($settings, TRUE));
    }

    $installer = new Installer($settings);

    $output->writeln("<info>[[ Internationalize ]]</info>");
    $installer->configI18n();
    $this->printMessages($output, $installer->getMessages());
    if ($installer->hasError()) {
      return 2;
    }
    $installer->clearMessages();

    foreach ($installer->getSteps() as $step => $stepLabel) {
      if ($step === 'configI18n') {
        continue;
      }

      $output->writeln("<info>[[ $stepLabel ]]</info>");
      $installer->$step();
      $this->printMessages($output, $installer->getMessages());
      if ($installer->hasError()) {
        return 2;
      }
      $installer->clearMessages();
    }
  }

  /**
   * @param OutputInterface $output
   * @param array $messages
   */
  protected function printMessages(OutputInterface $output, $messages) {
    foreach ($messages as $message) {
      $this->printMessage($output, $message);
    }
  }

  protected function printMessage(OutputInterface $output, $message) {
    switch ($message['severity']) {
      case Requirements::REQUIREMENT_WARNING:
        $output->writeln("<comment>" . $message['title'] . ': ' . $message['details'] . "</comment>");
        break;

      case Requirements::REQUIREMENT_ERROR:
        $output->writeln("<error>" . $message['title'] . ': ' . $message['details'] . "</error>");
        break;

      case Requirements::REQUIREMENT_OK:
      default:
        $output->writeln($message['title'] . ': ' . $message['details']);

    }
  }

  /**
   * Generate a series of CLI options based on the docblocks
   * from a model object.
   *
   * @param object $model
   *   The model object to inspect.
   */
  protected function addOptionsFromObject($model) {
    $settingsClass = new \ReflectionClass(get_class($model));
    $props = get_object_vars($model);
    ksort($props);
    foreach ($props as $key => $value) {
      $prop = $settingsClass->getProperty($key);
      list ($propType, $propDesc) = $this->parseDocComment($prop->getDocComment());
      switch ($propType) {
        case 'string':
        case 'int':
          $this->addOption($key, NULL, InputOption::VALUE_REQUIRED, $propDesc, $value);
          break;

        case 'bool':
          $this->addOption($key, NULL, InputOption::VALUE_NONE, $propDesc, $value);
          break;

        default:
          $this->addOption($key, NULL, InputOption::VALUE_NONE, '(Unrecognized)', $value);
      }
    }
  }

  protected function mergeOptionsIntoObject(InputInterface $input, $model) {
    $settingsClass = new \ReflectionClass(get_class($model));
    $props = get_object_vars($model);
    foreach ($props as $key => $value) {
      $prop = $settingsClass->getProperty($key);
      list ($propType, $propDesc) = $this->parseDocComment($prop->getDocComment());
      switch ($propType) {
        case 'string':
          $model->{$key} = $input->getOption($key);
          break;

        case 'int':
          $model->{$key} = (int) $input->getOption($key);
          break;

        case 'bool':
          $model->{$key} = (bool) $input->getOption($key);
          break;

        default:
      }
    }
  }

  /**
   * Quick-n-dirty parsing of '@var' docblocks.
   *
   * It would be nice if we had Doctrine Annotations or a
   * similar library available.
   *
   * @param string $comment
   *   Doc block.
   * @return array
   *   Array(0 => string $type, 1 => string $description).
   */
  public function parseDocComment($comment) {
    $comment = trim($comment);

    $comment = preg_replace("/\r\n/", "\n", $comment);
    $comment = preg_replace(":^/\*\*:", "", $comment);
    $comment = preg_replace(":\*/$:", "", $comment);
    $comment = preg_replace(":^ *\* :m", "", $comment);

    $type = 'unknown';

    $lines = explode("\n", $comment);
    $grep = preg_grep('/@var ([^\W]+)/', $lines);
    if (count($grep) == 1) {
      foreach ($grep as $key => $line) {
        preg_match('/@var ([^\W]+)/', $line, $matches);
        $type = $matches[1];
        unset($lines[$key]);
        $comment = implode("\n", $lines);
      }
    }

    $comment = trim($comment, " \r\n");

    return array($type, $comment);
  }

  /**
   * @return Settings
   */
  protected function createDefaultSettings() {
    $settings = new Settings(array(
      'root' => dirname(dirname(dirname(__DIR__))),
    ));
    return $settings;
  }

}
