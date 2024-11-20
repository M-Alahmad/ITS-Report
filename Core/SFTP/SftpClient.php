<?php
namespace Core\SFTP;

use phpseclib3\Net\SFTP;

class SftpClient {
    private $sftp;

    public function __construct($host, $port, $username, $password) {
        $this->sftp = new SFTP($host, $port);
        if (!$this->sftp->login($username, $password)) {
            throw new \Exception("Cannot connect to $host:$port. Login failed.");
        }
    }

    public function listFiles($directory) {
        $files = $this->sftp->nlist($directory);
        if ($files === false) {
            throw new \Exception("Failed to list files in directory: $directory");
        }
        return $files;
    }

    public function downloadFile($remoteFilePath, $localFilePath) {
        if (!$this->sftp->get($remoteFilePath, $localFilePath)) {
            throw new \Exception("Failed to download file: $remoteFilePath");
        }
    }
}