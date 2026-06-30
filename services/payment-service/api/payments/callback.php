<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db.php';

$configPath = __DIR__ . '/../../config_momo.json';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Missing MoMo config file', 'path' => $configPath]);
    exit;
}

$config = json_decode(file_get_contents($configPath), true);
if (!is_array($config)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Invalid MoMo config file']);
    exit;
}

// Handle callback from MoMo
$partnerCode = $_REQUEST['partnerCode'] ?? '';
$orderId = $_REQUEST['orderId'] ?? '';
$requestId = $_REQUEST['requestId'] ?? '';
$amount = $_REQUEST['amount'] ?? '';
$orderInfo = $_REQUEST['orderInfo'] ?? '';
$orderType = $_REQUEST['orderType'] ?? '';
$transId = $_REQUEST['transId'] ?? '';
$resultCode = $_REQUEST['resultCode'] ?? '';
$message = $_REQUEST['message'] ?? '';
$payType = $_REQUEST['payType'] ?? '';
$responseTime = $_REQUEST['responseTime'] ?? '';
$extraData = $_REQUEST['extraData'] ?? '';
$signature = $_REQUEST['signature'] ?? '';

$secretKey = $config['secretKey'];
$accessKey = $config['accessKey'];

$rawHash = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $resultCode . "&transId=" . $transId;

$partnerSignature = hash_hmac('sha256', $rawHash, $secretKey);

if ($signature == $partnerSignature) {
    $realOrderId = explode('_', $orderId)[0];
    if ($resultCode == '1006') {
        // Giao dịch thành công
        error_log("MoMo payment successful for orderId: $orderId");
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('UPDATE hoadonthanhtoan SET PAYMENT_STATUS = "paid" WHERE ID = :order_id');
            $stmt->execute(['order_id' => $realOrderId]);
        } catch (Throwable $e) {
            error_log("Lỗi cập nhật DB: " . $e->getMessage());
        }
    } else {
        // Giao dịch thất bại
        error_log("MoMo payment failed for orderId: $orderId. ResultCode: $resultCode");
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('UPDATE hoadonthanhtoan SET PAYMENT_STATUS = "failed" WHERE ID = :order_id');
            $stmt->execute(['order_id' => $realOrderId]);
        } catch (Throwable $e) {
            error_log("Lỗi cập nhật DB: " . $e->getMessage());
        }
    }
} else {
    error_log("Invalid signature for MoMo callback");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Khách hàng bị MoMo redirect về (bằng GET)
    header("Location: /customer-orders.html");
    exit;
} else {
    // MoMo server gọi ngầm IPN (bằng POST)
    echo "success";
    exit;
}