<?php

namespace FilerDB;

use FilerDB\Core\Utilities\Error;
use FilerDB\Core\Utilities\Timestamp;

use FilerDB\Core\Libraries\Database;
use FilerDB\Core\Libraries\Databases;
use FilerDB\Core\Libraries\Collection;

class Instance
{

  /**
   * Default configuration
   */
  private $config = [];

  /**
   * Database Statuses
   */
  private $status = [
    'DATABASE_IS_WRITABLE' => false
  ];

  /**
   * Database instance holder
   * @var FilerDB\Core\Libraries\Database
   */
  public $databases = null;

  public $defaultDatabase = null;

  /**
   * Collection instance holder
   * NOTE: This is only available if a database is selected in
   * the configuration
   * @var FilerDB\Core\Libraries\Collection
   */
  public $collection = null;

  /**
   * Timestamp instance holder
   * @var FilerDB\Core\Libraries\Timestamp
   */
  public $timestamp = null;

  /**
   * Class constructor
   */
  public function __construct($config = null) {

    /**
     * Set the initial configuration variables.
     */
    $this->_setInitialConfig([
      'DATABASE_PATH' => false
    ], $config);

    /**
     * Initialize everything
     */
    $this->_initialize();
  }

  /**
   * Initializes the database
   */
  private function _initialize() {
    if (!$this->config->DATABASE_PATH) Error::throw('NO_DATABASE_PATH');
    $this->databases  = new Databases($this->config);
    $this->timestamp = new Timestamp($this->config);

    $this->_checkDefaultDatabase();
  }

  /**
   * Start the chain with database.
   */
  public function database ($database) {
    return new Database($this->config, $database);
  }

  /**
   * FilerDB now supports the ability to select a default
   * database. So we can now instantiate the collection class
   * below so we can skip the ->database portion of the logic.
   *
   * NOTE: You can still access the ->database logic if you need
   * to pull from a different database within the same code.
   */
  public function collection($collection) {

    // If default database is not set, error.
    if (!$this->defaultDatabase)
      Error::throw('DATABASE_NOT_SELECTED', "A default database must be selected");

    // If the collection does not exist
    if (!$this->defaultDatabase->collectionExists($collection))
      Error::throw('COLLECTION_NOT_EXIST', "$collection does not exist");

    // Return a new collection instantiation.
    return new Collection($this->config, $this->config->database, $collection);
  }

  /**
   * Selects a default database for all collection calls
   * to go to.
   */
  public function selectDatabase ($database) {
    $exists = $this->databases->exists($this->config->database);
    if (!$exists) Error::throw('DATABASE_NOT_FOUND', "Database not found");
    $this->defaultDatabase = new Database($this->config, $this->config->database);
  }

  /**
   * Ran from instantiation. Will select a default database
   * if one is provided in the configuration
   */
  private function _checkDefaultDatabase () {
    if (isset($this->config->database)) {
      if (!is_null($this->config->database) && !empty($this->config->database)) {
        $this->selectDatabase($this->config->database);
      }
    }
  }

  /**
   * Get a configuration variable from the
   * class configuration.
   */
  private function _get ($var) {
    if (isset($this->config->{$var})) return $var;
    return false;
  }

  /**
   * Sets the default class configuration
   */
  private function _setInitialConfig ($initialConfig, $config) {
    $this->config = (object) $initialConfig;

    if (!is_null($config)) {
      if (!is_array($config)) return false;

      foreach ($config as $key => $val) {
        if ($key === 'path') {
          $this->set('DATABASE_PATH', $val);
        } else {
          $this->set($key, $val);
        }
      }
    }

    return $this;
  }

  /**
   * Set the class configuration variable.
   */
  public function set ($var, $val) {
    $this->config->{$var} = $val;
  }

}
