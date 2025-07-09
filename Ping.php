<?php
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(405); // Method Not Allowed
    header('Allow: GET, HEAD');
    exit;
}

http_response_code(200);

if ($method === 'GET') {
    echo "pong";
}
