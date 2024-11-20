<?php

require_once 'router.php';
require_once 'vendor/autoload.php';

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

get('/sipnow/download', function () {
    authRequired(function () {
        require_once __DIR__ . '/App/Views/sipnow/download.html';
    });
});

post('/import-sipnow-data', function () {
    authRequired(function () {
        $sipnowController = new SipnowController();
        $sipnowController->importCsvData();
    });
});

get('/download-csv', function () {
    authRequired(function () {
        $sipnowController = new SipnowController();
        $companyName = isset($_GET['company_name']) ? $_GET['company_name'] : null;
        $sipnowController->downloadCsv($companyName);
    });
});

get('/download-all-csv', function () {
    authRequired(function () {
        $sipnowController = new SipnowController();
        $sipnowController->downloadAllCsv();
    });
});

get('/search-companies', function () {
    $db = (new \Core\Database\Database())->getConnection();

    $query = isset($_GET['query']) ? $_GET['query'] : '';
    if ($query) {
        $stmt = $db->prepare("SELECT DISTINCT company_name FROM call_records WHERE company_name LIKE :query LIMIT 10");
        $stmt->execute([':query' => "%$query%"]);
        $companies = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode($companies);
    } else {
        echo json_encode([]);
    }
});
// Vsphere Routes
get('/import-vsphere-data', function () {
    authRequired(function () {
        $vsphereController = new VsphereController();
        $vsphereController->importVmData();
    });
});

get('/vsphere/view', function () {
    authRequired(function () {
        require_once __DIR__ . '/App/Views/vsphere/view.html';
    });
});

// Search resource pools
get('/search-resource-pools', function () {
    $db = (new \Core\Database\Database())->getConnection();
    $query = isset($_GET['query']) ? $_GET['query'] : '';

    if ($query) {
        $stmt = $db->prepare("SELECT DISTINCT resource_pool FROM virtual_machines WHERE resource_pool LIKE :query LIMIT 10");
        $stmt->execute([':query' => "%$query%"]);
        $resourcePools = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($resourcePools);
    } else {
        echo json_encode([]);
    }
});

// Fetch VM details by resource pool
get('/vsphere/search-vms', function () {
    $db = (new \Core\Database\Database())->getConnection();
    $resourcePool = isset($_GET['resource_pool']) ? $_GET['resource_pool'] : '';

    if ($resourcePool) {
        $stmt = $db->prepare("SELECT name, cpu_count, memory_size_GB, disk_capacity_GB, disk_used_GB FROM virtual_machines WHERE resource_pool = :resource_pool");
        $stmt->execute([':resource_pool' => $resourcePool]);
        $vms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($vms) {
            echo json_encode($vms);
        } else {
            echo json_encode(['error' => 'No VMs found for the selected resource pool.']);
        }
    } else {
        echo json_encode(['error' => 'Resource pool name is required.']);
    }
});
