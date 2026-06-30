<?php

declare(strict_types=1);

require_once __DIR__ . '/_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    jsonResponse(405, ['success' => false, 'message' => 'Method not allowed. Use POST/PATCH.', 'data' => null]);
}

$input          = getJsonInput();
$userId         = isset($input['user_id'])        ? (int) $input['user_id']        : 0;
$orderId        = isset($input['order_id'])       ? (int) $input['order_id']       : 0;
$active         = array_key_exists('active', $input)         ? $input['active']         : null;
$paymentStatus  = array_key_exists('payment_status', $input) ? (string) $input['payment_status'] : null;

if ($userId <= 0 || $orderId <= 0 || ($active === null && $paymentStatus === null)) {
    jsonResponse(400, ['success' => false, 'message' => 'Thiếu user_id, order_id hoặc cần ít nhất active/payment_status.', 'data' => null]);
}

// Chỉ admin/staff mới được đổi trạng thái đơn hàng
$requester = os_requireRoleByUserId($userId, ['staff', 'admin']);

$allowedPaymentStatuses = ['paid', 'pending', 'failed', 'refunded'];
if ($paymentStatus !== null && !in_array($paymentStatus, $allowedPaymentStatuses, true)) {
    jsonResponse(400, ['success' => false, 'message' => 'payment_status không hợp lệ. Giá trị cho phép: ' . implode(', ', $allowedPaymentStatuses), 'data' => null]);
}

try {
    $pdo   = getPDO();
    $order = os_getOrderById($pdo, $orderId);

    if (!$order) {
        jsonResponse(404, ['success' => false, 'message' => 'Không tìm thấy đơn hàng.', 'data' => null]);
    }

    // Xây dựng câu UPDATE động theo những field được gửi lên
    $setClauses = [];
    $params     = ['order_id' => $orderId];

    if ($active !== null) {
        // TREMOVE = 1 → đang hoạt động, TREMOVE = 0 → đã hủy
        $setClauses[]       = 'TREMOVE = :tremove';
        $params['tremove']  = (bool) $active ? 1 : 0;
    }

    if ($paymentStatus !== null) {
        $setClauses[]              = 'PAYMENT_STATUS = :payment_status';
        $params['payment_status']  = $paymentStatus;
    }

    $sql  = 'UPDATE hoadonthanhtoan SET ' . implode(', ', $setClauses) . ' WHERE ID = :order_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Tạo thông báo kết quả
    $messages = [];
    if ($active !== null) {
        $messages[] = (bool) $active ? 'Đã kích hoạt đơn hàng.' : 'Đã hủy đơn hàng.';
    }
    if ($paymentStatus !== null) {
        $labels = ['paid' => 'Đã thanh toán', 'pending' => 'Chờ thanh toán', 'failed' => 'Thanh toán thất bại', 'refunded' => 'Đã hoàn tiền'];
        $messages[] = 'Trạng thái thanh toán: ' . ($labels[$paymentStatus] ?? $paymentStatus) . '.';
    }

    jsonResponse(200, [
        'success' => true,
        'message' => implode(' ', $messages),
        'data'    => [
            'order_id'       => $orderId,
            'active'         => $active,
            'payment_status' => $paymentStatus,
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(500, ['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái.', 'error' => $e->getMessage(), 'data' => null]);
}
