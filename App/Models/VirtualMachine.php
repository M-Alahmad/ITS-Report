<?php

namespace App\Models;

use Core\Database\Database;

class VirtualMachine {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }


    /**
     * Clear all data from the virtual_machines table.
     */
    public function truncate() {
        $this->db->exec("TRUNCATE TABLE virtual_machines");
    }

    
    public function storeVmData(array $vmList) {
        $stmt = $this->db->prepare("
            INSERT INTO virtual_machines (name, cpu_count, memory_size_GB, disk_capacity_GB, disk_used_GB, resource_pool)
            VALUES (:name, :cpu_count, :memory_size_GB, :disk_capacity_GB, :disk_used_GB, :resource_pool)
        ");

        foreach ($vmList as $vm) {
            $stmt->execute([
                ':name' => $vm['name'],
                ':cpu_count' => $vm['cpu_count'],
                ':memory_size_GB' => $vm['memory_size_GB'],
                ':disk_capacity_GB' => $vm['disk_capacity_GB'],
                ':disk_used_GB' => $vm['disk_used_GB'],
                ':resource_pool' => $vm['resource_pool'],
            ]);
        }
    }

    public function searchByResourcePool($resourcePool) {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM virtual_machines 
            WHERE resource_pool LIKE :resourcePool
        ");
        $stmt->execute([':resourcePool' => '%' . $resourcePool . '%']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getResourcePools($query) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT resource_pool 
            FROM virtual_machines 
            WHERE resource_pool LIKE :query 
            LIMIT 10
        ");
        $stmt->execute([':query' => '%' . $query . '%']);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getVmsByResourcePool($resourcePool) {
        $stmt = $this->db->prepare("
            SELECT name, cpu_count, memory_size_GB, disk_capacity_GB, disk_used_GB 
            FROM virtual_machines 
            WHERE resource_pool = :resource_pool
        ");
        $stmt->execute([':resource_pool' => $resourcePool]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
