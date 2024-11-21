<?php

namespace App\Models;

use Core\Database\Database;

class CallRecord {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function truncate() {
        $this->db->exec("TRUNCATE TABLE call_records");
    }

    public function importFromCsv($filePath) {
        if (($handle = fopen($filePath, 'r')) !== false) {
            fgetcsv($handle); // Skip header row
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $stmt = $this->db->prepare("
                    INSERT INTO call_records (company_name, report_date, from_number, to_number, duration, user)
                    VALUES (:company_name, :report_date, :from_number, :to_number, :duration, :user)
                ");
                $stmt->execute([
                    ':company_name' => $this->extractCompanyName($filePath),
                    ':report_date' => $this->extractReportDate($filePath),
                    ':from_number' => $data[0],
                    ':to_number' => $data[1],
                    ':duration' => $data[9],
                    ':user' => $data[15],
                ]);
            }
            fclose($handle);
        }
    }

    private function extractCompanyName($fileName) {
        // Logic to extract company name from file name
        // Example: "CDR_2024_11_20_CompanyName.csv"
        $pattern = "/CDR_\d{4}_(.*?)_(\d{4}_\d{2})\.csv/";
        if (preg_match($pattern, $fileName, $matches)) {
            return $matches[1];
        }
        return 'Unknown';
    }

    private function extractReportDate($fileName) {
        // Logic to extract report date from file name
        // Example: "CDR_2024_11_20_CompanyName.csv"
        $pattern = "/CDR_\d{4}_(.*?)_(\d{4}_\d{2})\.csv/";
        if (preg_match($pattern, $fileName, $matches)) {
            return date('Y-m-d', strtotime(str_replace('_', '-', $matches[2])));
        }
        return date('Y-m-d');
    }

    public function getRecordsByCompanyName($companyName) {
        $stmt = $this->db->prepare("
            SELECT from_number, to_number, duration, user, report_date
            FROM call_records
            WHERE company_name LIKE :company_name
        ");
        $stmt->execute([':company_name' => '%' . $companyName . '%']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllCompanies() {
        $stmt = $this->db->query("SELECT DISTINCT company_name FROM call_records");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function searchCompanies($query) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT company_name 
            FROM call_records 
            WHERE company_name LIKE :query 
            LIMIT 10
        ");
        $stmt->execute([':query' => '%' . $query . '%']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
}
