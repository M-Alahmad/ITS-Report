<?php

require_once 'router.php';
require_once 'vendor/autoload.php';
require_once __DIR__ . '/Core/Helpers/helpers.php';
use App\Controllers\AuthController;
use App\Controllers\SipnowController;
use App\Controllers\AdminController;
use App\Controllers\VsphereController;
use Core\Auth\Auth;

// Start the session at the beginning
session_start();

// Utility function for protected routes
function authRequired($callback) {
    if (Auth::check()) {
        $callback();
    } else {
        header('Location: /login');
        exit;
    }
}

// Authentication routes
get('/login', function () {
    if (Auth::check()) {
        header('Location: /sipnow/download');
        exit;
    }
    require_once __DIR__ . '/App/Views/auth/login.html';
});
get('/', function () {
    if (Auth::check()) {
        header('Location: /sipnow/download');
        exit;
    }
    require_once __DIR__ . '/App/Views/auth/login.html';
});

post('/login', function () {
    $authController = new AuthController();
    $authController->login();
});

get('/logout', function () {
    $authController = new AuthController();
    $authController->logout();
});

// Admin dashboard
get('/admin/add-user', function () {
    authRequired(function () {
        require_once __DIR__ . '/App/Views/admin/add-user.html';
    });
});

post('/admin/add-user', function () {
    authRequired(function () {
        $adminController = new AdminController();
        $adminController->addUser();
    });
});

post('/admin/delete-user', function () {
    authRequired(function () {
        $adminController = new AdminController();
        $userId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
        $adminController->deleteUser($userId);
    });
});

// Sipnow Routes

get('/sipnow', function () {
    authRequired(function () {
        $sipnowController = new SipnowController();
        $sipnowController->index();
    });
});

post('/sipnow/importCsvData', function () {
    authRequired(function () {
        $sipnowController = new SipnowController();
        $sipnowController->importCsvData();
    });
});

get('/sipnow/downloadCsv', function () {
    authRequired(function () {
        $sipnowController = new SipnowController();
        $companyName = isset($_GET['company_name']) ? $_GET['company_name'] : null;
        $sipnowController->downloadCsv($companyName);
    });
});

get('/sipnow/downloadAllCsv', function () {
    authRequired(function () {
        $sipnowController = new SipnowController();
        $sipnowController->downloadAllCsv();
    });
});

get('/sipnow/getCompanySuggestions', function () {
    authRequired(function () {
        $sipnowController = new \App\Controllers\SipnowController();
        $sipnowController->getCompanySuggestions();
    });
});

// Vsphere Routes
// Import vSphere Data
post('/vsphere/import', function () {
    authRequired(function () {
        $vsphereController = new \App\Controllers\VsphereController();
        $vsphereController->importVmData();
    });
});

// View vSphere Dashboard
get('/vsphere/view', function () {
    authRequired(function () {
        $vsphereController = new \App\Controllers\VsphereController();
        $vsphereController->index();
    });
});

// Autocomplete Resource Pools
get('/vsphere/resource-pools', function () {
    authRequired(function () {
        $vsphereController = new \App\Controllers\VsphereController();
        $vsphereController->getResourcePoolSuggestions();
    });
});

// Fetch VMs for a Resource Pool
get('/vsphere/vms', function () {
    authRequired(function () {
        $vsphereController = new \App\Controllers\VsphereController();
        $resourcePool = $_GET['resource_pool'] ?? '';
        $vsphereController->searchByResourcePool($resourcePool);
    });
});
