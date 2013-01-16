<?php

class DB {
  public  $res    = null;
  public  $conn   = false;
  public  $mysqli = false;
  private $config = null;
  
  public function __construct($config){
    if(!empty($config['db_ip']) && !empty($config['db_user']) && !empty($config['db_pass']) && !empty($config['db_name'])) {
      $this->config = $config;
      $this->connect();
    } else {
	throw new Exception("DB config information is incomplete.");
    }
  }
    
  protected function connect(){
    try {
      $this->res  = new mysqli($this->config['db_ip'], $this->config['db_user'], $this->config['db_pass'], $this->config['db_name']);
      $this->conn = true;
      if ($this->res->connect_error){
	$this->conn = false;
	throw new Exception("DB error: " . $this->res->connect_error);
      }
    } catch (Exception $e) {
      throw new Exception("DB error: " . $e->getMessage());
    }
  }

  public function close() {
    try {
      $this->res->close();
      $this->conn = false;
    } catch (Exception $e) {
      throw new Exception("DB error: " . $e->getMessage());
    }
  }
}