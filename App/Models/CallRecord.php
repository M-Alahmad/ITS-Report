<?php
namespace App\Models;

use Core\Database\Database;
use PDO;

class CallRecord {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function insert($callData) {
        $stmt = $this->db->prepare("
            INSERT INTO call_records (customer_id, from_number, to_number, call_started, call_answered, call_disposition, duration, cost, profit, channel_name, caller_id)
            VALUES (:customer_id, :from_number, :to_number, :call_started, :call_answered, :call_disposition, :duration, :cost, :profit, :channel_name, :caller_id)
        ");
        $stmt->execute($callData);
    }
}
