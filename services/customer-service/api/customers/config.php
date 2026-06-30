<?php
declare(strict_types=1);
require_once __DIR__ . '/../../db.php';

jsonResponse(200, [
    'success' => true,
    'gemini_api_key' => getenv('GEMINI_API_KEY') ?: ''
]);
