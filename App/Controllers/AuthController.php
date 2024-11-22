<?php
namespace App\Controllers;

use Core\Auth\Auth;
use Core\Database\Database;

class AuthController extends Controller {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function login() {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            Auth::login($user['id']);
            header('Location: /sipnow/view');
            exit();
        } else {
            echo 'Invalid credentials';
        }
    }

    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit();
    }

    public function isAuthenticated() {
        return Auth::check();
    }
}
