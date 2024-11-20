<?php
namespace App\Models;

use Core\Database\Database;
use PDO;

class Customer {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getOrCreateCustomer($name) {
        $stmt = $this->db->prepare("SELECT id FROM customers WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $customer = $stmt->fetch();

        if ($customer) {
            return $customer['id'];
        } else {
            $stmt = $this->db->prepare("INSERT INTO customers (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            return $this->db->lastInsertId();
        }
    }
}
