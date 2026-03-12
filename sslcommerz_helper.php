<?php

require_once __DIR__ . '/sslcommerz_config.php';

function sslcommerz_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Detect if we're in /stock/ folder
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Check if script is in /stock/ directory (root or subdirectory)
    if (strpos($scriptName, '/stock/') !== false) {
        $basePath = '/stock';
    } else {
        // Fallback: go up from current script location
        $basePath = rtrim(dirname($scriptName), '/\\');
        if ($basePath === '' || $basePath === '.') {
            $basePath = '';
        }
    }

    return $scheme . '://' . $host . $basePath;
}

function sslcommerz_api_endpoint(bool $sandbox): string
{
    return $sandbox
        ? 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php'
        : 'https://securepay.sslcommerz.com/gwprocess/v3/api.php';
}

function sslcommerz_api_endpoint_v4(bool $sandbox): string
{
    return $sandbox
        ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
        : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
}

function sslcommerz_validator_endpoint(bool $sandbox): string
{
    return $sandbox
        ? 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'
        : 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';
}

function sslcommerz_init_payment(array $payload, bool $sandbox): array
{
    $url = sslcommerz_api_endpoint($sandbox);

    // Request JSON response when possible
    if (!isset($payload['format'])) {
        $payload['format'] = 'json';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Increased timeouts for slow sandbox
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_NOSIGNAL, true);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    if (defined('CURLOPT_TCP_KEEPALIVE')) {
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
    }
    if (defined('CURLOPT_TCP_KEEPIDLE')) {
        curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 30);
    }
    if (defined('CURLOPT_TCP_KEEPINTVL')) {
        curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 15);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'Expect:'
    ]);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $errno = curl_errno($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If gateway is slow/failed, try v4 endpoint as a fallback (sandbox only)
    if ($sandbox && ($raw === false || $http >= 500 || in_array($errno, [6, 7, 28], true))) {
        $url = sslcommerz_api_endpoint_v4($sandbox);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        if (defined('CURLOPT_TCP_KEEPALIVE')) {
            curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        }
        if (defined('CURLOPT_TCP_KEEPIDLE')) {
            curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 30);
        }
        if (defined('CURLOPT_TCP_KEEPINTVL')) {
            curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 15);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'Expect:'
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $errno = curl_errno($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    if ($raw === false || $raw === '') {
        return ['ok' => false, 'error' => 'SSLCommerz server is not responding. Please try again later. (' . ($err ?: 'Connection timeout') . ')', 'http' => $http];
    }

    if ($http >= 500) {
        $preview = mb_substr(trim($raw), 0, 240);
        return ['ok' => false, 'error' => 'Gateway error (HTTP ' . $http . '). Try again later.', 'http' => $http, 'raw' => $preview];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        // Try URL-encoded response
        $parsed = [];
        parse_str($raw, $parsed);
        if (isset($parsed['GatewayPageURL'])) {
            return ['ok' => true, 'gateway_url' => $parsed['GatewayPageURL'], 'data' => $parsed];
        }

        // Try to extract GatewayPageURL from raw text
        if (preg_match('/GatewayPageURL\s*[:=]\s*(https?:\/\/[^\s"\']+)/i', $raw, $m)) {
            return ['ok' => true, 'gateway_url' => $m[1], 'data' => ['GatewayPageURL' => $m[1]]];
        }

        $preview = mb_substr(trim($raw), 0, 240);
        return ['ok' => false, 'error' => 'Invalid JSON from gateway', 'http' => $http, 'raw' => $preview];
    }

    if (!empty($data['GatewayPageURL'])) {
        return ['ok' => true, 'gateway_url' => $data['GatewayPageURL'], 'data' => $data];
    }

    return ['ok' => false, 'error' => $data['failedreason'] ?? ($data['desc'] ?? 'Gateway did not return a redirect URL'), 'data' => $data];
}

function sslcommerz_validate_transaction(string $valId, bool $sandbox, string $storeId, string $storePass): array
{
    $url = sslcommerz_validator_endpoint($sandbox) . '?' . http_build_query([
        'val_id' => $valId,
        'store_id' => $storeId,
        'store_passwd' => $storePass,
        'v' => 1,
        'format' => 'json',
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$sandbox ? true : false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, !$sandbox ? 2 : 0);

    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $raw === '') {
        return ['ok' => false, 'error' => $err ?: 'Empty response from validator', 'http' => $http];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid JSON from validator', 'http' => $http, 'raw' => $raw];
    }

    return ['ok' => true, 'data' => $data];
}

function ensure_customer_payments_table(mysqli $conn): void
{
    // Keep it simple: create a payment mapping table so SSLCommerz callbacks can resolve order_id
    $sql = "CREATE TABLE IF NOT EXISTS `customer_payments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `customer_id` INT(11) NOT NULL,
        `gateway` VARCHAR(30) NOT NULL,
        `tran_id` VARCHAR(40) NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `currency` VARCHAR(10) NOT NULL DEFAULT 'BDT',
        `status` ENUM('initiated','success','failed','cancelled') NOT NULL DEFAULT 'initiated',
        `val_id` VARCHAR(120) DEFAULT NULL,
        `bank_tran_id` VARCHAR(120) DEFAULT NULL,
        `card_type` VARCHAR(80) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
        `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_tran_id` (`tran_id`),
        KEY `idx_order_id` (`order_id`),
        KEY `idx_customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $conn->query($sql);
}
