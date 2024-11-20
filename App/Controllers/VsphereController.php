<?php

namespace App\Controllers;

use Exception;
use Core\Api\VsphereApi;
use Core\Database\Database;

class VsphereController extends Controller
{
    
    /**
     * Import VM data from vSphere API.
     */
    public function importVmData()
    {
        try {
            $resourcePools = $this->vsphereApi->getResourcePools();
            if (empty($resourcePools)) {
                throw new Exception("No resource pools found.");
            }

            $allVmData = [];

            foreach ($resourcePools as $pool) {
                $vms = $this->vsphereApi->getVmsByResourcePool($pool['resource_pool']);

                if (empty($vms)) {
                    error_log("No VMs found for resource pool: " . $pool['name']);
                    continue;
                }

                foreach ($vms as $vm) {
                    $vmDetails = $this->vsphereApi->getVmDetails($vm['vm']);

                    $allVmData[] = [
                        'name' => $vm['name'] ?? 'Unknown',
                        'cpu_count' => $vm['cpu_count'] ?? 0,
                        'memory_size_GB' => round(($vm['memory_size_MiB'] ?? 0) / 1024, 2),
                        'disk_capacity_GB' => $vmDetails['disk_capacity_GB'] ?? 0.00,
                        'disk_used_GB' => $vmDetails['disk_used_GB'] ?? 0.00,
                        'resource_pool' => $pool['name'] ?? 'N/A',
                    ];
                }
            }

            if (empty($allVmData)) {
                throw new Exception("No VM data to import. Check the API responses.");
            }

            $this->storeVmData($allVmData);
            echo json_encode(["success" => "VM data imported successfully!"]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Store VM data in the database.
     */
    private function storeVmData(array $vmList)
    {
        $db = new Database();
        $pdo = $db->getConnection();

        if (!$pdo) {
            throw new Exception("Failed to establish a database connection.");
        }

        $stmt = $pdo->prepare("
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
        try {
            $db = new Database();
            $pdo = $db->getConnection();
    
            $stmt = $pdo->prepare("
                SELECT * 
                FROM virtual_machines 
                WHERE resource_pool LIKE :resourcePool
            ");
            $stmt->execute([':resourcePool' => '%' . $resourcePool . '%']);
    
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
            if (!$result) {
                throw new Exception("No virtual machines found for resource pool: $resourcePool");
            }
    
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
}
