<?php

namespace Core\Services;

use Core\Api\VsphereApi;

class VsphereService {
    private $vsphereApi;

    public function __construct() {
        $this->vsphereApi = new VsphereApi();
    }

    public function fetchVmData() {
        $resourcePools = $this->vsphereApi->getResourcePools();
        if (empty($resourcePools)) {
            throw new \Exception("No resource pools found.");
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

        return $allVmData;
    }
}
