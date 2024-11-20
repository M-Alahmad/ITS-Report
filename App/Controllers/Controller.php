<?php

namespace App\Controllers;

use Core\Api\VsphereApi;
use Core\database;

class Controller {
    // protected $model;
    protected $authController;
    protected $vsphereApi;
    public function __construct() {
        // $this->model = $model;
        $this->authController = new AuthController();
        $this->vsphereApi= new VsphereApi();
    }

    protected function render($view, $data = []) {
        // Extract the data array into variables
        extract($data);
        include "../app/views/{$view}.php"; // Assuming views are in app/views
    }
}
