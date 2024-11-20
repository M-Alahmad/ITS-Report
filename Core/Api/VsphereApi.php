<?php

namespace Core\Api;

use Exception;

class VsphereApi
{
    private $host = 'vvcsa01.wfc365.vsphere';
    private $username = 'read@wfc365.vsphere';
    private $password = 'Read!4321';
    private $sessionId;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Establish a session with the vSphere API.
     */
    private function connect()
    {
        $url = "https://{$this->host}/rest/com/vmware/cis/session";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('Connection error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception("Failed to connect to vSphere API. HTTP Code: {$httpCode}");
        }

        $data = json_decode($response, true);
        $this->sessionId = $data['value'] ?? null;

        if (!$this->sessionId) {
            throw new Exception("Failed to retrieve session ID.");
        }

        curl_close($ch);
    }

    /**
     * Send a request to the vSphere API.
     */
    private function sendRequest($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "vmware-api-session-id: {$this->sessionId}",
                "Content-Type: application/json",
            ],
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception("Request error: " . curl_error($ch));
        }

        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Fetch the list of resource pools.
     */
    public function getResourcePools()
    {
        $url = "https://{$this->host}/api/vcenter/resource-pool";
        $response = $this->sendRequest($url);

        if (!is_array($response)) {
            throw new Exception("Invalid response for resource pools.");
        }

        return $response;
    }

    /**
     * Fetch the VMs for a given resource pool.
     */
    public function getVmsByResourcePool($resourcePoolId)
    {
        $url = "https://{$this->host}/api/vcenter/vm?resource_pools=" . urlencode($resourcePoolId);
        $response = $this->sendRequest($url);

        if (!is_array($response)) {
            error_log("Invalid or empty response for resource pool ID: $resourcePoolId");
            return [];
        }

        return $response;
    }

    /**
     * Fetch detailed information for a specific VM.
     */
    public function getVmDetails($vmId)
    {
        $url = "https://{$this->host}/rest/vcenter/vm/{$vmId}";
        $response = $this->sendRequest($url);

        $vmDetails = $response['value'] ?? [];

        // Calculate disk capacity (if available)
        $diskCapacity = 0;
        foreach ($vmDetails['disks'] ?? [] as $disk) {
            $diskCapacity += $disk['value']['capacity'] ?? 0;
        }

        return [
            'disk_capacity_GB' => round($diskCapacity / 1073741824, 2), // Convert bytes to GB
            'disk_used_GB' => 0, // Placeholder, as usage data isn't available
        ];
    }
}
