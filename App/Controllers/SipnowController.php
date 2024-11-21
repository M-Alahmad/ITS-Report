<?php

namespace App\Controllers;

use App\Models\CallRecord;
use Core\Services\SftpService;
use Core\Helpers\helpers;

class SipnowController extends Controller {
    private $callRecordModel;
    private $sftpService;

    public function __construct() {
        $this->callRecordModel = new CallRecord();
        $this->sftpService = new SftpService();
    }

    public function index() {
        $data = ['user' => $_SESSION['user'] ?? 'Guest'];
        return \Core\Helpers\view('sipnow.html', $data);
    }


    public function importCsvData() {
        try {
            // Truncate the table before importing
            $this->callRecordModel->truncate();

            // List files from SFTP server
            $directory = '/home/sftpuser';
            $files = $this->sftpService->listFiles($directory);

            foreach ($files as $file) {
                if (strpos($file, 'CDR') === 0) {
                    $remoteFilePath = rtrim($directory, '/') . '/' . $file;
                    $localFilePath = sys_get_temp_dir() . '/' . $file;

                    // Download file
                    $this->sftpService->downloadFile($remoteFilePath, $localFilePath);

                    // Parse and insert CSV data
                    $this->callRecordModel->importFromCsv($localFilePath);
                }
            }

            echo json_encode(['message' => 'CSV data imported successfully!']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function downloadCsv() {
        try {
            $companyName = $_GET['companyName'] ?? null;
    
            if (!$companyName) {
                throw new \Exception("Company name is required to download CSV.");
            }
    
            $records = $this->callRecordModel->getRecordsByCompanyName($companyName);
    
            if (empty($records)) {
                throw new \Exception("No records found for the company: " . htmlspecialchars($companyName));
            }
    
            // Use report_date from the first record
            $reportDate = $records[0]['report_date'] ?? date('Y-m-d');
            $filename = $companyName . '_report_' . $reportDate . '.csv';
    
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
    
            $output = fopen('php://output', 'w');
            fputcsv($output, ['From Number', 'To Number', 'Duration', 'User', 'Report Date']);
    
            foreach ($records as $record) {
                fputcsv($output, $record);
            }
    
            fclose($output);
            exit();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function downloadAllCsv() {
        try {
            $companies = $this->callRecordModel->getAllCompanies();
    
            if (empty($companies)) {
                throw new \Exception("No companies found for export.");
            }
    
            $zipFilename = 'all_reports_' . date('Y_m_d') . '.zip';
            $zip = new \ZipArchive();
            $zip->open($zipFilename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    
            foreach ($companies as $company) {
                $companyName = $company['company_name'];
                $records = $this->callRecordModel->getRecordsByCompanyName($companyName);
    
                if (!empty($records)) {
                    $reportDate = $records[0]['report_date'] ?? date('Y-m-d');
                    $csvFilename = $companyName . '_report_' . $reportDate . '.csv';
    
                    $csvContent = fopen('php://temp', 'r+');
                    fputcsv($csvContent, ['From Number', 'To Number', 'Duration', 'User', 'Report Date']);
    
                    foreach ($records as $record) {
                        fputcsv($csvContent, $record);
                    }
    
                    rewind($csvContent);
                    $csvData = stream_get_contents($csvContent);
                    fclose($csvContent);
    
                    $zip->addFromString($csvFilename, $csvData);
                }
            }
    
            $zip->close();
    
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
            readfile($zipFilename);
            unlink($zipFilename);
            exit();
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function getCompanySuggestions() {
        try {
            $query = $_GET['query'] ?? '';
    
            if (strlen($query) < 2) {
                echo json_encode([]); // Return empty array for short input
                return;
            }
    
            $companies = $this->callRecordModel->searchCompanies($query);
            echo json_encode($companies);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
}
