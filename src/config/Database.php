<?php
/**
 * Connect MySQL with PDO class
 */
class Database {
  
  public function connect() {

    // https://www.php.net/manual/en/pdo.connections.php
    $prepare_conn_str = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'];
    $dbConn = new PDO( $prepare_conn_str, $_ENV['DB_USER'], $_ENV['DB_PASS'] );

    // https://www.php.net/manual/en/pdo.setattribute.php
    $dbConn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    return $dbConn;
  }
}