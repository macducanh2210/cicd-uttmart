<?php

require_once __DIR__ . '/../../../db.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Parse customer ID and address ID from URL
preg_match('/\/api\/customers\/(\d+)\/addresses(?:\/(\d+))?/', $path, $matches);
$requestedCustomerId = isset($matches[1]) ? (int)$matches[1] : 0;
$addressId = isset($matches[2]) ? (int)$matches[2] : 0;

if (!$requestedCustomerId) {
    jsonResponse(400, ['success' => false, 'message' => 'Customer ID required']);
}

$customerId = requireCustomerSession($requestedCustomerId);

try {
    $pdo = getPDO();
    
    if ($method === 'GET') {
        // List all addresses for customer
        $stmt = $pdo->prepare('
            SELECT ID, TEN_DIA_CHI, THANH_PHO, HUYEN, XA_PHUONG, DIA_CHI_CHI_TIET, SDT_NHA, MA_BCHC, LA_MAC_DINH, CREATED_AT 
            FROM dia_chi_khachhang 
            WHERE IDKHACHHANG = ? 
            ORDER BY LA_MAC_DINH DESC, CREATED_AT DESC
        ');
        $stmt->execute([$customerId]);
        $addresses = $stmt->fetchAll();
        
        jsonResponse(200, [
            'success' => true,
            'data' => array_map(function($addr) {
                return [
                    'id' => (int)$addr['ID'],
                    'full_address' => $addr['DIA_CHI_CHI_TIET'],
                    'name' => $addr['TEN_DIA_CHI'],
                    'city' => $addr['THANH_PHO'],
                    'district' => $addr['HUYEN'],
                    'ward' => $addr['XA_PHUONG'],
                    'address_detail' => $addr['DIA_CHI_CHI_TIET'],
                    'phone' => $addr['SDT_NHA'],
                    'ma_bchc' => $addr['MA_BCHC'],
                    'is_default' => (bool)$addr['LA_MAC_DINH'],
                    'created_at' => $addr['CREATED_AT']
                ];
            }, $addresses)
        ]);
        
    } elseif ($method === 'POST') {
        // Add new address
        $input = getJsonInput();

        $errors = [];

        // Cho phép nhập địa chỉ đầy đủ (full_address) thay vì các trường rời
        $fullAddress = trim((string)($input['full_address'] ?? ''));
        if ($fullAddress === '') {
            $errors['full_address'] = 'address is required';
        }

        $phone = trim((string)($input['phone'] ?? ''));
        if ($phone === '') {
            $errors['phone'] = 'phone is required';
        } elseif (!preg_match('/^\d+$/', $phone)) {
            $errors['phone'] = 'Phone must contain only digits';
        } elseif (strlen($phone) !== 10) {
            $errors['phone'] = 'Phone must contain exactly 10 digits';
        }

        if (count($errors) > 0) {
            jsonResponse(400, [
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ thông tin',
                'errors' => $errors
            ]);
        }

        // Check address count limit
        $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM dia_chi_khachhang WHERE IDKHACHHANG = ?');
        $stmt->execute([$customerId]);
        $result = $stmt->fetch();
        $count = (int)($result['cnt'] ?? 0);
        if ($count >= 10) {
            jsonResponse(400, [
                'success' => false,
                'message' => 'Cannot add more addresses',
                'errors' => ['limit' => 'mỗi tài khoản chỉ được phép thêm tối đa 10 địa chỉ'],
            ]);
        }

        // Check if this is the first address
        $isFirstAddress = $count === 0;
        
        $stmt = $pdo->prepare('
            INSERT INTO dia_chi_khachhang 
            (IDKHACHHANG, TEN_DIA_CHI, THANH_PHO, HUYEN, XA_PHUONG, DIA_CHI_CHI_TIET, SDT_NHA, MA_BCHC, LA_MAC_DINH) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $customerId,
            '',           // TEN_DIA_CHI - không dùng
            '',           // THANH_PHO - không dùng
            '',           // HUYEN - không dùng
            '',           // XA_PHUONG - không dùng
            $fullAddress, // DIA_CHI_CHI_TIET - lưu toàn bộ địa chỉ
            $phone,
            $input['ma_bchc'] ?? null,
            $isFirstAddress ? 1 : 0
        ]);
        
        $addressId = (int)$pdo->lastInsertId();
        
        jsonResponse(201, [
            'success' => true,
            'message' => 'Address added',
            'data' => ['id' => $addressId]
        ]);
        
    } elseif ($method === 'PUT' && $addressId) {
        // Update address
        $input = getJsonInput();
        
        $updates = [];
        $params = [];
        
        $errors = [];

        // Xử lý full_address
        if (array_key_exists('full_address', $input)) {
            $fullAddress = trim((string)$input['full_address']);
            if ($fullAddress === '') {
                $errors['full_address'] = 'address is required';
            } else {
                $updates[] = 'DIA_CHI_CHI_TIET = ?';
                $params[] = $fullAddress;
            }
        }

        if (array_key_exists('phone', $input)) {
            $phone = trim((string)$input['phone']);
            if ($phone === '') {
                $errors['phone'] = 'phone is required when provided';
            } elseif (!preg_match('/^\d+$/', $phone)) {
                $errors['phone'] = 'phone must contain only digits';
            } elseif (strlen($phone) !== 10) {
                $errors['phone'] = 'phone must contain exactly 10 digits';
            } else {
                $updates[] = 'SDT_NHA = ?';
                $params[] = $phone;
            }
        }

        if (count($errors) > 0) {
            jsonResponse(400, [
                'success' => false,
                'message' => 'Vui lòng điền đầy đủ thông tin',
                'errors' => $errors
            ]);
        }
        
        if (empty($updates)) {
            jsonResponse(400, ['success' => false, 'message' => 'No fields to update']);
        }
        
        $params[] = $customerId;
        $params[] = $addressId;
        
        $sql = 'UPDATE dia_chi_khachhang SET ' . implode(', ', $updates) . ' WHERE IDKHACHHANG = ? AND ID = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(404, ['success' => false, 'message' => 'Address not found']);
        }
        
        jsonResponse(200, [
            'success' => true,
            'message' => 'Address updated'
        ]);
        
    } elseif ($method === 'DELETE' && $addressId) {
        // Delete address
        $stmt = $pdo->prepare('DELETE FROM dia_chi_khachhang WHERE IDKHACHHANG = ? AND ID = ?');
        $stmt->execute([$customerId, $addressId]);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(404, ['success' => false, 'message' => 'Address not found']);
        }
        
        jsonResponse(200, [
            'success' => true,
            'message' => 'Address deleted'
        ]);
    } else {
        jsonResponse(405, ['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    jsonResponse(500, ['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
