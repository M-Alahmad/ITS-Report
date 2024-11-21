<?php

namespace Core\Services;

use Core\SFTP\SftpClient;

class SftpService {
    private $sftp;

    public function __construct() {
        $host = '185.71.5.20';
        $port = 22;
        $username = 'sftpuser';
        $password = '9h23dMsKOENF5pTJ';

        $this->sftp = new SftpClient($host, $port, $username, $password);
    }

    public function listFiles($directory) {
        return $this->sftp->listFiles($directory);
    }

    public function downloadFile($remoteFile, $localFile) {
        $this->sftp->downloadFile($remoteFile, $localFile);
    }
}
