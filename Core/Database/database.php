<?php
namespace Core\Database;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost'; // Database host
    private $db_name = 'its_projekt'; // Database name
    private $username = 'root'; // Database username
    private $password = ''; // Database password
    public $conn; // Connection property

    // Constructor
    public function __construct() {
        // Initialize the connection when the class is instantiated
        $this->connect();
    }

    // Establish the database connection
    private function connect() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name}", $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
            }
        }
    }

    // Get the database connection
    public function getConnection() {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }
}
// $db= new Database;
// $pdo= $db->conn;
// var_dump($pdo);

