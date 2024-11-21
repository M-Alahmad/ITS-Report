<?php

namespace Core\Helpers;

function view($file, $data = []) {
    // Extract data for use in the view
    extract($data);

    // Construct the full path to the view
    $viewPath = __DIR__ . "/../../App/Views/{$file}";
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        throw new \Exception("View not found: {$file}");
    }
}
