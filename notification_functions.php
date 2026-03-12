<?php
// Automated Notification System for Customer Orders
// This script should be called when an order status is updated to 'delivered'

function sendOrderNotification($customer_id, $order_id, $conn) {
    // Get customer information
    $customer_query = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    if (!$customer_query) {
        return false;
    }
    $customer_query->bind_param("i", $customer_id);
    $customer_query->execute();
    $customer = $customer_query->get_result()->fetch_assoc();

    // Get order details
    $order_query = $conn->prepare("
        SELECT co.*, p.name as product_name, p.price
        FROM customer_orders co
        JOIN products p ON co.product_id = p.id
        WHERE co.id = ?
    ");
    if (!$order_query) {
        return false;
    }
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order = $order_query->get_result()->fetch_assoc();

    if ($customer && $order) {
        // Create notification message
        $notification_message = "🎉 Great news! Your order for " . $order['product_name'] . " has been delivered successfully!\n\n" .
                               "📦 Order Details:\n" .
                               "• Product: " . $order['product_name'] . "\n" .
                               "• Quantity: " . $order['quantity'] . "\n" .
                               "• Total Amount: $" . number_format($order['total_amount'], 2) . "\n" .
                               "• Delivery Date: " . date('F d, Y', strtotime($order['updated_at'])) . "\n\n" .
                               "Thank you for shopping with us! We hope you enjoy your purchase.\n\n" .
                               "If you have any questions or need support, feel free to contact our support team.";

        // Save notification to database
        $insert_notification = $conn->prepare("
            INSERT INTO automated_notifications (customer_id, order_id, notification_type, message, created_at)
            VALUES (?, ?, 'order_delivered', ?, NOW())
        ");
        if (!$insert_notification) {
            return false;
        }
        $insert_notification->bind_param("iis", $customer_id, $order_id, $notification_message);

        if ($insert_notification->execute()) {
            // Also save as AI chat message for customer to see
            $ai_message = "🤖 AI Assistant: " . $notification_message;
            $insert_chat = $conn->prepare("
                INSERT INTO ai_chat_messages (customer_id, response, message_type, created_at)
                VALUES (?, ?, 'ai_to_customer', NOW())
            ");
            if ($insert_chat) {
                $insert_chat->bind_param("is", $customer_id, $ai_message);
                $insert_chat->execute();
            }

            return true;
        }
    }

    return false;
}

function sendOrderStatusUpdateNotification($customer_id, $order_id, $status, $conn) {
    // Get customer information
    $customer_query = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    if (!$customer_query) {
        return false;
    }
    $customer_query->bind_param("i", $customer_id);
    $customer_query->execute();
    $customer = $customer_query->get_result()->fetch_assoc();

    // Get order details
    $order_query = $conn->prepare("
        SELECT co.*, p.name as product_name
        FROM customer_orders co
        JOIN products p ON co.product_id = p.id
        WHERE co.id = ?
    ");
    if (!$order_query) {
        return false;
    }
    $order_query->bind_param("i", $order_id);
    $order_query->execute();
    $order = $order_query->get_result()->fetch_assoc();

    if ($customer && $order) {
        $status_messages = [
            'pending' => "📋 Your order for " . $order['product_name'] . " is now being processed.",
            'processing' => "⚙️ Your order for " . $order['product_name'] . " is now being prepared for shipment.",
            'shipped' => "🚚 Your order for " . $order['product_name'] . " has been shipped and is on its way!",
            'delivered' => "🎉 Your order for " . $order['product_name'] . " has been delivered successfully!",
            'cancelled' => "❌ Your order for " . $order['product_name'] . " has been cancelled."
        ];

        $notification_message = $status_messages[$status] ?? "Your order status has been updated to: " . ucfirst($status);

        // Save notification to database
        $insert_notification = $conn->prepare("
            INSERT INTO automated_notifications (customer_id, order_id, notification_type, message, created_at)
            VALUES (?, ?, 'order_status_update', ?, NOW())
        ");
        if (!$insert_notification) {
            return false;
        }
        $insert_notification->bind_param("iis", $customer_id, $order_id, $notification_message);

        if ($insert_notification->execute()) {
            // Also save as AI chat message
            $ai_message = "🤖 AI Assistant: " . $notification_message;
            $insert_chat = $conn->prepare("
                INSERT INTO ai_chat_messages (customer_id, response, message_type, created_at)
                VALUES (?, ?, 'ai_to_customer', NOW())
            ");
            if ($insert_chat) {
                $insert_chat->bind_param("is", $customer_id, $ai_message);
                $insert_chat->execute();
            }

            return true;
        }
    }

    return false;
}

function sendLowStockAlert($customer_id, $product_name, $remaining_stock, $conn) {
    $notification_message = "⚠️ Product Alert: " . $product_name . " is running low in stock! Only " . $remaining_stock . " units remaining.\n\n" .
                           "Don't miss out - place your order now before it's gone!";

    // Save notification to database
    $insert_notification = $conn->prepare("
        INSERT INTO automated_notifications (customer_id, notification_type, message, created_at)
        VALUES (?, 'low_stock_alert', ?, NOW())
    ");
    if (!$insert_notification) {
        return false;
    }
    $insert_notification->bind_param("is", $customer_id, $notification_message);

    if ($insert_notification->execute()) {
        // Also save as AI chat message
        $ai_message = "🤖 AI Assistant: " . $notification_message;
        $insert_chat = $conn->prepare("
            INSERT INTO ai_chat_messages (customer_id, response, message_type, created_at)
            VALUES (?, ?, 'ai_to_customer', NOW())
        ");
        if ($insert_chat) {
            $insert_chat->bind_param("is", $customer_id, $ai_message);
            $insert_chat->execute();
        }

        return true;
    }

    return false;
}

function sendWelcomeNotification($customer_id, $conn) {
    // Get customer information
    $customer_query = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    if (!$customer_query) {
        return false;
    }
    $customer_query->bind_param("i", $customer_id);
    $customer_query->execute();
    $customer = $customer_query->get_result()->fetch_assoc();

    if ($customer) {
        $notification_message = "👋 Welcome to our Stock Management System, " . $customer['name'] . "!\n\n" .
                               "🎉 Your account has been successfully created. You can now:\n\n" .
                               "• Browse and purchase products\n" .
                               "• Chat with our AI assistant for instant help\n" .
                               "• Track your orders and delivery status\n" .
                               "• Update your profile and preferences\n" .
                               "• Contact support for any assistance\n\n" .
                               "Start exploring our products or ask our AI assistant for recommendations!\n\n" .
                               "Happy shopping! 🛒";

        // Save notification to database
        $insert_notification = $conn->prepare("
            INSERT INTO automated_notifications (customer_id, notification_type, message, created_at)
            VALUES (?, 'welcome_message', ?, NOW())
        ");
        if (!$insert_notification) {
            return false;
        }
        $insert_notification->bind_param("is", $customer_id, $notification_message);

        if ($insert_notification->execute()) {
            // Also save as AI chat message
            $ai_message = "🤖 AI Assistant: " . $notification_message;
            $insert_chat = $conn->prepare("
                INSERT INTO ai_chat_messages (customer_id, response, message_type, created_at)
                VALUES (?, ?, 'ai_to_customer', NOW())
            ");
            if ($insert_chat) {
                $insert_chat->bind_param("is", $customer_id, $ai_message);
                $insert_chat->execute();
            }

            return true;
        }
    }

    return false;
}

// Function to get customer notifications
function getCustomerNotifications($customer_id, $limit = 10, $conn) {
    $query = $conn->prepare("
        SELECT * FROM automated_notifications
        WHERE customer_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $query->bind_param("ii", $customer_id, $limit);
    $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to mark notification as read
function markNotificationAsRead($notification_id, $conn) {
    $query = $conn->prepare("
        UPDATE automated_notifications
        SET is_read = 1
        WHERE id = ?
    ");
    if (!$query) {
        return false;
    }
    $query->bind_param("i", $notification_id);
    return $query->execute();
}

// Function to get unread notification count
function getUnreadNotificationCount($customer_id, $conn) {
    $query = $conn->prepare("
        SELECT COUNT(*) as unread_count
        FROM automated_notifications
        WHERE customer_id = ? AND is_read = 0
    ");
    $query->bind_param("i", $customer_id);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
    return $result['unread_count'];
}

// ===== NOTIFICATION DOTS SYSTEM =====

// Function to create a notification dot
function createNotificationDot($notification_type, $from_user_type, $to_user_type, $reference_id, $dot_color, $message, $conn) {
    $query = $conn->prepare("
        INSERT INTO notification_dots (notification_type, from_user_type, to_user_type, reference_id, dot_color, message)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $query->bind_param("sssiss", $notification_type, $from_user_type, $to_user_type, $reference_id, $dot_color, $message);
    return $query->execute();
}

// Function to get active notification dots for a user type
function getActiveNotificationDots($user_type, $conn) {
    $query = $conn->prepare("
        SELECT * FROM notification_dots
        WHERE to_user_type = ? AND is_active = 1
        ORDER BY created_at DESC
    ");
    $query->bind_param("s", $user_type);
    $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to deactivate notification dots by type and reference
function deactivateNotificationDots($notification_type, $reference_id, $conn) {
    $query = $conn->prepare("
        UPDATE notification_dots
        SET is_active = 0, updated_at = NOW()
        WHERE notification_type = ? AND reference_id = ?
    ");
    $query->bind_param("si", $notification_type, $reference_id);
    return $query->execute();
}

// Function to handle customer product inquiry/request
function handleCustomerProductRequest($customer_id, $product_id, $request_type = 'inquiry', $conn) {
    // Create blue dot notification for staff (staff handles customer orders first)
    $message = "Customer has " . ($request_type === 'order' ? 'placed an order' : 'inquired about') . " a product.";
    createNotificationDot('customer_request', 'customer', 'staff', $product_id, 'blue', $message, $conn);

    // Also send AI notification to staff
    $staff_ai_message = "🚨 Customer Activity: A customer has " . ($request_type === 'order' ? 'ordered' : 'asked about') . " a product. Check Customer Orders for details.";
    // This would be sent to staff's notification system
}

// Function to handle admin approval of customer request
function handleAdminApproval($customer_request_id, $admin_id, $conn) {
    // Create notification dot for staff
    $message = "Admin has approved a customer request. Please check product availability.";
    createNotificationDot('admin_approval', 'admin', 'staff', $customer_request_id, 'green', $message, $conn);
}

// Function to handle staff product need request
function handleStaffProductNeed($product_id, $staff_id, $quantity_needed, $urgency, $conn) {
    // Create notification dot for admin
    $message = "Staff has requested product replenishment (Urgency: $urgency).";
    createNotificationDot('staff_product_need', 'staff', 'admin', $product_id, 'yellow', $message, $conn);

    // Insert into product_requests table
    $query = $conn->prepare("
        INSERT INTO product_requests (product_id, requested_by, quantity_needed, urgency_level, reason)
        VALUES (?, ?, ?, ?, 'Staff identified low stock')
    ");
    $query->bind_param("iiiis", $product_id, $staff_id, $quantity_needed, $urgency);
    $query->execute();
    return $conn->insert_id;
}

// Function to handle admin order to supplier
function handleAdminSupplierOrder($product_request_id, $supplier_id, $product_id, $quantity, $conn) {
    // Create notification dot for supplier
    $message = "Admin has placed an order for products. Please confirm delivery.";
    createNotificationDot('admin_order_request', 'admin', 'supplier', $product_request_id, 'blue', $message, $conn);

    // Insert into supplier_orders table
    $query = $conn->prepare("
        INSERT INTO supplier_orders (product_request_id, supplier_id, product_id, quantity)
        VALUES (?, ?, ?, ?)
    ");
    $query->bind_param("iiii", $product_request_id, $supplier_id, $product_id, $quantity);
    $query->execute();
}

// Function to handle supplier response
function handleSupplierResponse($supplier_order_id, $response, $conn) {
    $colors = [
        'accepted' => 'green',
        'later' => 'yellow',
        'cancelled' => 'red'
    ];

    $messages = [
        'accepted' => 'Supplier has accepted the order and will send products.',
        'later' => 'Supplier will send products later.',
        'cancelled' => 'Supplier cannot fulfill the order at this time.'
    ];

    $dot_color = $colors[$response] ?? 'red';
    $message = $messages[$response] ?? 'Supplier response received.';

    // Update supplier_orders table
    $update_query = $conn->prepare("
        UPDATE supplier_orders SET status = ?, updated_at = NOW() WHERE id = ?
    ");
    $update_query->bind_param("si", $response, $supplier_order_id);
    $update_query->execute();

    // Create notification dot for admin
    createNotificationDot('supplier_response', 'supplier', 'admin', $supplier_order_id, $dot_color, $message, $conn);

    // Deactivate the original admin order request dot
    deactivateNotificationDots('admin_order_request', $supplier_order_id, $conn);
}

// Function to get notification dot counts by color for a user type
function getNotificationDotCounts($user_type, $conn) {
    $query = $conn->prepare("
        SELECT dot_color, COUNT(*) as count
        FROM notification_dots
        WHERE to_user_type = ? AND is_active = 1
        GROUP BY dot_color
    ");
    $query->bind_param("s", $user_type);
    $query->execute();
    $result = $query->get_result();

    $counts = ['blue' => 0, 'green' => 0, 'yellow' => 0, 'red' => 0];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['dot_color']] = $row['count'];
    }

    return $counts;
}
?>