<?php

function getResolvedSupplierId(mysqli $conn): int
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!empty($_SESSION['supplier_id'])) {
        return (int)$_SESSION['supplier_id'];
    }

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($user_id <= 0) {
        return 0;
    }

    // First, try direct id match (in case supplier ids align with user ids)
    $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $_SESSION['supplier_id'] = (int)$row['id'];
            return (int)$row['id'];
        }
    }

    // Next, resolve by user email/name
    $uStmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
    if (!$uStmt) {
        return 0;
    }

    $uStmt->bind_param('i', $user_id);
    $uStmt->execute();
    $user = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    if (!$user) {
        return 0;
    }

    $email = (string)$user['email'];
    $name = (string)$user['name'];

    $sStmt = $conn->prepare("SELECT id FROM suppliers WHERE email = ? OR name = ? LIMIT 1");
    if ($sStmt) {
        $sStmt->bind_param('ss', $email, $name);
        $sStmt->execute();
        $row = $sStmt->get_result()->fetch_assoc();
        $sStmt->close();
        if ($row) {
            $_SESSION['supplier_id'] = (int)$row['id'];
            return (int)$row['id'];
        }
    }

    return 0;
}
