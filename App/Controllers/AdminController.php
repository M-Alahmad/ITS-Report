<?php
namespace App\Controllers;

use Core\Auth\Auth;
use Core\Database\Database;

class AdminController extends Controller {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function addUser() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

            $stmt = $this->db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $password
            ]);

            echo 'User added successfully';
        }
    }

    public function deleteUser($userId) {
        if (!$userId) {
            echo json_encode(['error' => 'User ID is required']);
            return;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            echo json_encode(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
        }
    }

    public function getUsers() {
        $stmt = $this->db->prepare("SELECT id, username, created_at FROM users");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
