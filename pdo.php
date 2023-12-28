<?php
class Database {
    private $pdo;
    private $db_host;
    private $db_name;
    private $db_user;
    private $db_pw;
    private $db_charset;

    public function __construct($db_host, $db_name, $db_user, $db_pw, $db_charset) {
        $this->db_host = $db_host;
        $this->db_name = $db_name;
        $this->db_user = $db_user;
        $this->db_pw = $db_pw;
        $this->db_charset = $db_charset;

        try {
          $dsn = "mysql:host={$this->db_host};dbname={$this->db_name};charset={$this->db_charset}";
          $this->pdo = new PDO($dsn, $this->db_user, $this->db_pw);
          $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      } catch (PDOException $e) {
          echo "Connection failed: " . $e->getMessage();
      }
    }

    public function getPdo() {
        return $this->pdo;
    }
}

$database = new Database（);
$pdo = $database->getPdo();
?>