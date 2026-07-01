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
$supplierId = isset($input['supplier_id']) ? (int) $input['supplier_id'] : 0;
if ($supplierId <= 0) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'supplier_id is required.',
        'data' => null,
    ]);
}

$fields = [];
$params = ['id' => $supplierId];

if (array_key_exists('supplier_name', $input) || array_key_exists('name', $input)) {
    $supplierName = trim((string) ($input['supplier_name'] ?? $input['name'] ?? ''));
    if ($supplierName === '' || mb_strlen($supplierName) < 2 || mb_strlen($supplierName) > 100) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Tên nhà cung cấp là bắt buộc và phải từ 2 đến 100 ký tự.',
            'data' => null,
        ]);
    }
    $fields[] = 'supplier_name = :supplier_name';
    $params['supplier_name'] = $supplierName;
}

if (array_key_exists('contact_name', $input)) {
    $contact = trim((string) $input['contact_name']);
    if ($contact === '' || mb_strlen($contact) < 2 || mb_strlen($contact) > 100) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Tên người liên hệ là bắt buộc và phải từ 2 đến 100 ký tự.',
            'data' => null,
        ]);
    }
    $fields[] = 'contact_name = :contact_name';
    $params['contact_name'] = $contact;
}

if (array_key_exists('phone', $input)) {
    $phone = trim((string) $input['phone']);
    if ($phone === '' || !preg_match('/^0(3\d|5\d|7\d|8\d|9\d)\d{7}$/', $phone)) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Số điện thoại không hợp lệ. Ví dụ: 0901234567.',
            'data' => null,
        ]);
    }
    $fields[] = 'phone = :phone';
    $params['phone'] = $phone;
}

if (array_key_exists('email', $input)) {
    $email = trim((string) $input['email']);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Email không hợp lệ hoặc bị bỏ trống.',
            'data' => null,
        ]);
    }
    $fields[] = 'email = :email';
    $params['email'] = $email;
}

if (array_key_exists('address', $input)) {
    $address = trim((string) $input['address']);
    if ($address === '' || mb_strlen($address) < 5 || mb_strlen($address) > 255) {
        jsonResponse(400, [
            'success' => false,
            'message' => 'Địa chỉ là bắt buộc và phải từ 5 đến 255 ký tự.',
            'data' => null,
        ]);
    }
    $fields[] = 'address = :address';
    $params['address'] = $address;
}

if (array_key_exists('is_active', $input)) {
    $fields[] = 'is_active = :is_active';
    $params['is_active'] = (int) ((int) $input['is_active'] > 0);
}

if (count($fields) === 0) {
    jsonResponse(400, [
        'success' => false,
        'message' => 'No fields to update.',
        'data' => null,
    ]);
}

try {
    $pdo = getPDO();

    $existsStmt = $pdo->prepare('SELECT id FROM suppliers WHERE id = :id LIMIT 1');
    $existsStmt->execute(['id' => $supplierId]);
    if (!$existsStmt->fetch()) {
        jsonResponse(404, [
            'success' => false,
            'message' => 'Supplier not found.',
            'data' => null,
        ]);
    }

    $sql = 'UPDATE suppliers SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(200, [
        'success' => true,
        'message' => 'Supplier updated successfully.',
        'data' => [
            'supplier_id' => $supplierId,
            'updated_rows' => $stmt->rowCount(),
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(500, [
        'success' => false,
        'message' => 'Internal server error when updating supplier.',
        'error' => $e->getMessage(),
        'data' => null,
    ]);
}
