<?php

require_once __DIR__ . '/../../../db.php';

// Get customer ID from URL or parameter
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('/\/api\/customers\/(\d+)\/profile/', $path, $matches);
$requestedCustomerId = isset($matches[1]) ? (int)$matches[1] : 0;

if (!$requestedCustomerId) {
    jsonResponse(400, ['success' => false, 'message' => 'Customer ID required']);
}

$customerId = requireCustomerSession($requestedCustomerId);

try {
    $pdo = getPDO();
    
    if ($method === 'GET') {
        // Get profile
        $stmt = $pdo->prepare('
            SELECT ID, HOTEN, EMAIL, SODIENTHOAI, DIACHI, NGAYSINH, GIOITINH, TICHDIEM, EMAIL_VERIFIED, CREATED_AT 
            FROM khachhang 
            WHERE ID = ?
        ');
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();
        
        if (!$customer) {
            jsonResponse(404, ['success' => false, 'message' => 'Customer not found']);
        }
        
        jsonResponse(200, [
            'success' => true,
            'data' => [
                'id' => (int)$customer['ID'],
                'full_name' => $customer['HOTEN'],
                'email' => $customer['EMAIL'],
                'phone' => $customer['SODIENTHOAI'],
                'address' => $customer['DIACHI'],
                'dob' => $customer['NGAYSINH'],
                'gender' => (int)$customer['GIOITINH'],
                'loyalty_points' => (int)$customer['TICHDIEM'],
                'email_verified' => (bool)$customer['EMAIL_VERIFIED'],
                'created_at' => $customer['CREATED_AT']
            ]
        ]);
        
    } elseif ($method === 'PUT') {
        // Update profile
        $input = getJsonInput();
        $input = is_array($input) ? $input : [];

        $errors = [];
        $updates = [];
        $params = [];

        if (array_key_exists('full_name', $input)) {
            $fullName = trim((string) ($input['full_name'] ?? ''));
            if ($fullName === '') {
                $errors[] = 'Họ tên không được để trống';
            } elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
                $errors[] = 'Họ tên phải từ 2 đến 100 ký tự';
            } elseif (!preg_match('/^[\p{L}\p{M}\s\'.-]+$/u', $fullName)) {
                $errors[] = 'Họ tên chỉ được chứa chữ cái, khoảng trắng và dấu';
            } else {
                $updates[] = 'HOTEN = ?';
                $params[] = $fullName;
            }
        }

        if (array_key_exists('phone', $input)) {
            $phone = trim((string) ($input['phone'] ?? ''));
            if ($phone === '') {
                $errors[] = 'Số điện thoại không được để trống';
            } elseif (!preg_match('/^0\d{9}$/', $phone)) {
                $errors[] = 'Số điện thoại phải bắt đầu bằng 0 và có đúng 10 chữ số';
            } else {
                $updates[] = 'SODIENTHOAI = ?';
                $params[] = $phone;
            }
        }

        if (array_key_exists('address', $input)) {
            $address = trim((string) ($input['address'] ?? ''));
            if ($address === '') {
                $errors[] = 'Địa chỉ không được để trống';
            } elseif (mb_strlen($address) < 5 || mb_strlen($address) > 255) {
                $errors[] = 'Địa chỉ phải từ 5 đến 255 ký tự';
            } else {
                $updates[] = 'DIACHI = ?';
                $params[] = $address;
            }
        }

        if (array_key_exists('dob', $input)) {
            $dob = trim((string) ($input['dob'] ?? ''));
            if ($dob === '') {
                $errors[] = 'Ngày sinh không được để trống';
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                $errors[] = 'Ngày sinh phải đúng định dạng YYYY-MM-DD';
            } else {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
                if (!$date || $date->format('Y-m-d') !== $dob) {
                    $errors[] = 'Ngày sinh không hợp lệ';
                } else {
                    $today = new DateTimeImmutable('today');
                    if ($date > $today) {
                        $errors[] = 'Ngày sinh không được lớn hơn ngày hiện tại';
                    } else {
                        $updates[] = 'NGAYSINH = ?';
                        $params[] = $dob;
                    }
                }
            }
        }

        if (array_key_exists('gender', $input)) {
            $gender = (int) $input['gender'];
            if (!in_array($gender, [0, 1], true)) {
                $errors[] = 'Giới tính không hợp lệ';
            } else {
                $updates[] = 'GIOITINH = ?';
                $params[] = $gender;
            }
        }

        if (!empty($errors)) {
            jsonResponse(422, ['success' => false, 'message' => 'Dữ liệu không hợp lệ', 'errors' => $errors]);
        }

        if (empty($updates)) {
            jsonResponse(400, ['success' => false, 'message' => 'No fields to update']);
        }

        $params[] = $customerId;
        $sql = 'UPDATE khachhang SET ' . implode(', ', $updates) . ' WHERE ID = ?';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        jsonResponse(200, [
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        jsonResponse(405, ['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    jsonResponse(500, ['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
