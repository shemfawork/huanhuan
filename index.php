<?php
/**
 * 许可证验证服务
 * 要求：MySQL数据库 + PHP 7.4+
 */

// 配置数据库连接（建议存储在环境变量中）
$db_host = 'localhost';
$db_name = 'license_system';
$db_user = 'secure_user';
$db_pass = 'S@feP@ssw0rd!';

// 验证请求参数
$input_license = filter_input(INPUT_POST, 'license_key', FILTER_SANITIZE_STRING);
$customer_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

// 响应数组
$response = [
    'status' => 'error',
    'message' => '',
    'license' => null
];

// 数据库连接
try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die(json_encode(['status' => 'db_error', 'message' => 'Database connection failed']));
}

// 输入验证
if (!$input_license || !$customer_email) {
    http_response_code(400);
    $response['message'] = 'Invalid request parameters';
    die(json_encode($response));
}

// 验证许可证
try {
    // 查询许可证信息
    $stmt = $pdo->prepare("
        SELECT * FROM licenses 
        WHERE license_key = :license_key 
        AND customer_email = :customer_email
    ");
    $stmt->execute([
        ':license_key' => $input_license,
        ':customer_email' => $customer_email
    ]);
    $license = $stmt->fetch();

    if (!$license) {
        $response['message'] = 'Invalid license key or email mismatch';
        http_response_code(401);
        die(json_encode($response));
    }

    // 检查有效期
    if (strtotime($license['valid_until']) < time()) {
        $response['status'] = 'expired';
        $response['message'] = 'License has expired';
        http_response_code(403);
        die(json_encode($response));
    }

    // 检查是否被禁用
    if ($license['is_disabled']) {
        $response['status'] = 'disabled';
        $response['message'] = 'License has been disabled';
        http_response_code(403);
        die(json_encode($response));
    }

    // 更新最后验证时间
    $update_stmt = $pdo->prepare("
        UPDATE licenses 
        SET last_verified = NOW() 
        WHERE license_key = :license_key
    ");
    $update_stmt->execute([':license_key' => $input_license]);

    // 返回成功响应
    $response['status'] = 'valid';
    $response['message'] = 'License verified successfully';
    $response['license'] = [
        'product' => $license['product_name'],
        'expires' => $license['valid_until'],
        'max_users' => $license['max_users']
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database operation failed';
    http_response_code(500);
}

// 返回JSON响应
header('Content-Type: application/json');
echo json_encode($response);
