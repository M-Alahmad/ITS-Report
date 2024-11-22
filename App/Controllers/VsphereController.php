<?php

namespace App\Controllers;

use App\Models\VirtualMachine;
use Core\Services\VsphereService;

class VsphereController extends Controller {
    private $virtualMachineModel;
    private $vsphereService;

    public function __construct() {
        $this->virtualMachineModel = new VirtualMachine();
        $this->vsphereService = new VsphereService();
    }

    /**
     * Display the vSphere dashboard view.
     */
    public function index() {
        return \Core\Helpers\view('vsphere/vsphere.html', ['user' => $_SESSION['user'] ?? 'Guest']);
    }

    /**
     * Import VM data from the vSphere API into the database.
     */
    public function importVmData() {
        try {
            // Clear existing data from the table
            $this->virtualMachineModel->truncate();
    
            // Fetch new data from the vSphere API
            $allVmData = $this->vsphereService->fetchVmData();
    
            if (empty($allVmData)) {
                throw new \Exception("No VM data to import. Check the API responses.");
            }
    
            // Store the new data
            $this->virtualMachineModel->storeVmData($allVmData);
            echo json_encode(["success" => "VM data imported successfully!"]);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getMessage());
        }
    }

    /**
     * Search for virtual machines by resource pool name.
     * 
     * @param string $resourcePool
     */
    public function searchByResourcePool($resourcePool) {
        try {
            $vms = $this->virtualMachineModel->getVmsByResourcePool($resourcePool);

            if (empty($vms)) {
                throw new \Exception("No virtual machines found for resource pool: $resourcePool");
            }

            echo json_encode($vms);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getMessage());
        }
    }

    /**
     * Fetch resource pool suggestions for autocomplete.
     */
    public function getResourcePoolSuggestions() {
        try {
            $query = $_GET['query'] ?? '';

            if (strlen($query) < 2) {
                echo json_encode([]); // Return empty suggestions for short input
                return;
            }

            $suggestions = $this->virtualMachineModel->getResourcePools($query);
            echo json_encode($suggestions);
        } catch (\Exception $e) {
            $this->sendErrorResponse($e->getMessage());
        }
    }

    /**
     * Send a JSON error response.
     * 
     * @param string $message
     */
    private function sendErrorResponse($message) {
        http_response_code(500);
        echo json_encode(['error' => $message]);
    }
}
