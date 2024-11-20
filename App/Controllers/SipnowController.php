<?php
namespace App\Controllers;

use Core\Database\Database;
use Core\SFTP\SftpClient;

class SipnowController extends Controller {
    private $db;
    private $sftp;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        // SFTP connection settings
        $host = '185.71.5.20';
        $port = 22; // SFTP port
        $username = 'sftpuser';
        $password = '9h23dMsKOENF5pTJ';

        $this->sftp = new SftpClient($host, $port, $username, $password);
    }

    public function importCsvData() {
        try {
            // Step 1: Clear old data from the table
            $this->db->exec("TRUNCATE TABLE call_records");
    
            // Directory containing CSV files on the SFTP server
            $directory = '/home/sftpuser';
            
            // Get the list of files in the directory
            $files = $this->sftp->listFiles($directory);
    
            // Local data directory
            $localDataDir = __DIR__ . '/../../data';
    
            // Ensure the local data directory exists
            if (!is_dir($localDataDir)) {
                if (!mkdir($localDataDir, 0777, true) && !is_dir($localDataDir)) {
                    throw new \Exception('Failed to create local data directory');
                }
            }
    
            foreach ($files as $file) {
                // Process only files that start with "CDR"
                if (strpos($file, 'CDR') === 0) {
                    // Properly join the directory and file name
                    $remoteFilePath = rtrim($directory, '/') . '/' . $file;
                    $localFilePath = $localDataDir . '/' . $file;
    
                    // Download the file
                    $this->sftp->downloadFile($remoteFilePath, $localFilePath);
    
                    // Parse and insert CSV data into the database
                    $this->parseAndInsertCsv($localFilePath, $file);
                }
            }
    
            echo json_encode(['message' => 'CSV data imported successfully!']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function parseAndInsertCsv($filePath, $fileName) {
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Skip the header row
            fgetcsv($handle, 1000, ',');

            // Extract company name and report date
            $fileInfo = $this->extractCompanyNameAndDate($fileName);
            $companyName = $fileInfo['company_name'];
            $reportDate = $fileInfo['report_date'];

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($data) {
                    // Extract needed data
                    $fromNumber = $data[0];
                    $toNumber = $data[1];
                    $duration = $data[9];
                    $userName = $data[15];

                    // Insert data into the database
                    $stmt = $this->db->prepare("
                        INSERT INTO call_records (company_name, report_date, from_number, to_number, duration, user)
                        VALUES (:company_name, :report_date, :from_number, :to_number, :duration, :user)
                    ");

                    $stmt->execute([
                        ':company_name' => $companyName,
                        ':report_date' => $reportDate,
                        ':from_number' => $fromNumber,
                        ':to_number' => $toNumber,
                        ':duration' => $duration,
                        ':user' => $userName
                    ]);
                }
            }

            fclose($handle);
        } else {
            throw new \Exception("Failed to open CSV file: $filePath");
        }
    }

    private function extractCompanyNameAndDate($fileName) {
        $pattern = "/CDR_\d{4}_(.*?)_(\d{4}_\d{2})\.csv/";
        if (preg_match($pattern, $fileName, $matches)) {
            return [
                'company_name' => $matches[1],
                'report_date' => date('Y-m-d', strtotime(str_replace('_', '-', $matches[2])))
            ];
        }
        return [
            'company_name' => 'Unknown',
            'report_date' => '0000-00-00'
        ];
    }

    public function downloadCsv($companyName) {
        if (!$companyName) {
            throw new \Exception("Company name is required to download CSV.");
        }
    
        // Perform a fuzzy search using SQL LIKE for partial match
        $stmt = $this->db->prepare("
            SELECT from_number, to_number, duration, user 
            FROM call_records 
            WHERE company_name LIKE :company_name
        ");
        $stmt->execute([':company_name' => '%' . $companyName . '%']);
        $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
        if (!$records) {
            throw new \Exception("No records found for the company: " . htmlspecialchars($companyName));
        }
    
        // Extract the full company name from the first result (for naming the file)
        $fullCompanyName = $records[0]['company_name'] ?? $companyName;
    
        // Prepare the CSV file for download
        $filename = $fullCompanyName . '_report_' . date('Y_m_d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
    
        $output = fopen('php://output', 'w');
        fputcsv($output, ['From Number', 'To Number', 'Duration', 'User']);
    
        foreach ($records as $record) {
            fputcsv($output, $record);
        }
    
        fclose($output);
        exit();
    }

    public function downloadAllCsv() {
        // Retrieve all distinct company names
        $stmt = $this->db->prepare("SELECT DISTINCT company_name FROM call_records");
        $stmt->execute();
        $companies = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Prepare a ZIP file to contain all CSVs
        $zipFilename = 'all_reports_' . date('Y_m_d') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFilename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($companies as $company) {
            $companyName = $company['company_name'];

            // Retrieve records for this company
            $stmt = $this->db->prepare("SELECT from_number, to_number, duration, user FROM call_records WHERE company_name = :company_name");
            $stmt->execute([':company_name' => $companyName]);
            $records = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!$records) {
                continue;
            }

            // Create a temporary CSV for this company
            $csvFilename = $companyName . '_report_' . date('Y_m_d') . '.csv';
            $csvContent = fopen('php://temp', 'r+');
            fputcsv($csvContent, ['From Number', 'To Number', 'Duration', 'User']);

            foreach ($records as $record) {
                fputcsv($csvContent, $record);
            }

            rewind($csvContent);
            $csvData = stream_get_contents($csvContent);
            fclose($csvContent);

            // Add the CSV file to the ZIP
            $zip->addFromString($csvFilename, $csvData);
        }

        $zip->close();

        // Send the ZIP file to the browser for download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($zipFilename));
        readfile($zipFilename);

        // Delete the ZIP file after download
        unlink($zipFilename);
        exit();
    }
    
}
