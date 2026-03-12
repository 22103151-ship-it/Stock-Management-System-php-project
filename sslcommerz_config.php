<?php

// SSLCommerz configuration
// Sandbox docs: https://developer.sslcommerz.com/
//
// IMPORTANT:
// - For security, you can set credentials in one of these ways:
//   1) Edit this file directly (simple)
//   2) Create `includes/sslcommerz_config_local.php` to override values (recommended)
//   3) Set environment variables SSLCOMMERZ_STORE_ID / SSLCOMMERZ_STORE_PASS

// Sandbox (true) => https://sandbox.sslcommerz.com
// Live (false)   => https://securepay.sslcommerz.com
$SSLCOMMERZ_SANDBOX = true;

// Defaults (will be overridden by local config file or env vars if set)
$SSLCOMMERZ_STORE_ID = 'ecomm696d82eaadc7b';
$SSLCOMMERZ_STORE_PASS = 'ecomm696d82eaadc7b@ssl';

// Optional local override (not committed)
$localConfig = __DIR__ . '/sslcommerz_config_local.php';
if (is_file($localConfig)) {
	require $localConfig;
}

// Environment variable override
$envStoreId = getenv('SSLCOMMERZ_STORE_ID');
if (!empty($envStoreId)) {
	$SSLCOMMERZ_STORE_ID = $envStoreId;
}

$envStorePass = getenv('SSLCOMMERZ_STORE_PASS');
if (!empty($envStorePass)) {
	$SSLCOMMERZ_STORE_PASS = $envStorePass;
}

// Currency
$SSLCOMMERZ_CURRENCY = 'BDT';
