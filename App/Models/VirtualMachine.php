<?php
namespace App\Models;

use Core\Database\Database;
use PDO;

class VirtualMachine {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    public function insert($vmData) {
        $stmt = $this->db->prepare("
            INSERT INTO virtual_machines (name, resource_pool, cpu, ram, hard_disk, used_disk)
            VALUES (:name, :resource_pool, :cpu, :ram, :hard_disk, :used_disk)
        ");
        $stmt->execute($vmData);
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM virtual_machines");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByResourcePool($resourcePool) {
        $stmt = $this->db->prepare("SELECT * FROM virtual_machines WHERE resource_pool = :resource_pool");
        $stmt->execute([':resource_pool' => $resourcePool]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
