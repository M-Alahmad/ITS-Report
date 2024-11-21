<?php
namespace Core\Services;

class FileService {
    public function generateCsv($records, $companyName) {
        $filename = "$companyName.csv";
        $handle = fopen($filename, 'w');
        fputcsv($handle, ['From Number', 'To Number', 'Duration', 'User']);

        foreach ($records as $record) {
            fputcsv($handle, $record);
        }

        fclose($handle);
        return $filename;
    }

    public function generateCompanyReportsZip($companies, $callRecordModel) {
        $zipFilename = 'all_reports.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFilename, \ZipArchive::CREATE);

        foreach ($companies as $company) {
            $records = $callRecordModel->getRecordsByCompany($company);
            $csvFile = $this->generateCsv($records, $company);
            $zip->addFile($csvFile, basename($csvFile));
        }

        $zip->close();
        return $zipFilename;
    }

    public function sendFile($file, $mimeType) {
        header("Content-Type: $mimeType");
        header("Content-Disposition: attachment; filename=" . basename($file));
        readfile($file);
        unlink($file); // Clean up
    }
}
