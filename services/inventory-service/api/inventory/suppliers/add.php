<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(200, ['success' => true, 'message' => 'Preflight OK', 'data' => null]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, [
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
        'data' => null,
    ]);
}

$input = getJsonInput();
$supplierName = trim((string) ($input['supplier_name'] ?? $input['name'] ?? ''));
$contactName = trim((string) ($input['contact_name'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$address = trim((string) ($input['address'] ?? ''));

if ($supplierName === '' || mb_strlen($supplierName) < 2 || mb_strlen($supplierName) > 100) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Tên nhà cung cấp là bắt buộc và phải từ 2 đến 100 ký tự.',
        'data' => null,
    ]);
}

if ($contactName === '' || mb_strlen($contactName) < 2 || mb_strlen($contactName) > 100) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Tên người liên hệ là bắt buộc và phải từ 2 đến 100 ký tự.',
        'data' => null,
    ]);
}

if ($phone === '' || !preg_match('/^0(3\d|5\d|7\d|8\d|9\d)\d{7}$/', $phone)) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Số điện thoại không hợp lệ. Ví dụ: 0901234567.',
        'data' => null,
    ]);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Email không hợp lệ hoặc bị bỏ trống.',
        'data' => null,
    ]);
}

if ($address === '' || mb_strlen($address) < 5 || mb_strlen($address) > 255) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'Địa chỉ là bắt buộc và phải từ 5 đến 255 ký tự.',
        'data' => null,
    ]);
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare('INSERT INTO suppliers (supplier_name, contact_name, phone, email, address, is_active) VALUES (:supplier_name, :contact_name, :phone, :email, :address, 1)');
    $stmt->execute([
        'supplier_name' => $supplierName,
        'contact_name' => $contactName !== '' ? $contactName : null,
        'phone' => $phone !== '' ? $phone : null,
        'email' => $email !== '' ? $email : null,
        'address' => $address !== '' ? $address : null,
    ]);

    jsonResponse(201, [
        'success' => true,
        'message' => 'Supplier created successfully.',
        'data' => [
            'supplier_id' => (int) $pdo->lastInsertId(),
            'supplier_name' => $supplierName,
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        'success' => false,
        'message' => 'Internal server error when creating supplier.',
        'error' => $e->getMessage(),
        'data' => null,
    ]);
}
