<?php
session_start();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit;
}

include '../config.php';
include '../includes/notification_functions.php'; // Add notification functions

// Get customer info
$customer_id = $_SESSION['customer_id'];
$customer_query = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
$customer = $customer_query->fetch_assoc();

// Handle AI chat messages
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Save user message
        $insert_query = "INSERT INTO ai_chat_messages (customer_id, message, message_type) VALUES (?, ?, 'customer_to_ai')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("is", $customer_id, $message);
        $stmt->execute();

        // Generate AI response based on message content
        $response = generateAIResponse($message, $conn);

        // Trigger admin notification for customer inquiry
        if (strpos(strtolower($message), 'stock') !== false ||
            strpos(strtolower($message), 'price') !== false ||
            strpos(strtolower($message), 'available') !== false) {
            handleCustomerProductRequest($customer_id, 0, 'inquiry', $conn); // 0 for general inquiry
        }

        // Save AI response
        $response_query = "INSERT INTO ai_chat_messages (customer_id, response, message_type) VALUES (?, ?, 'ai_to_customer')";
        $response_stmt = $conn->prepare($response_query);
        $response_stmt->bind_param("is", $customer_id, $response);
        $response_stmt->execute();
    }
}

// Get chat history
$chat_history = [];
$chat_query = $conn->query("
    SELECT * FROM ai_chat_messages
    WHERE customer_id = $customer_id
    ORDER BY id DESC
    LIMIT 50
");

while ($row = $chat_query->fetch_assoc()) {
    $chat_history[] = $row;
}
$chat_history = array_reverse($chat_history);

// AI Response generation function
function generateAIResponse($message, $conn) {
    $message = strtolower($message);

    // Product stock queries
    if (strpos($message, 'stock') !== false || strpos($message, 'available') !== false || strpos($message, 'quantity') !== false) {
        $product_name = extractProductName($message);
        if ($product_name) {
            $query = "SELECT name, stock FROM products WHERE name LIKE ? AND stock > 0";
            $stmt = $conn->prepare($query);
            $search_term = "%$product_name%";
            $stmt->bind_param("s", $search_term);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                return "📦 " . $product['name'] . " has " . $product['stock'] . " units available in stock.";
            } else {
                return "❌ I couldn't find that product or it might be out of stock. Please check our products page for current availability.";
            }
        } else {
            // Show all products with stock
            $query = "SELECT name, stock FROM products WHERE stock > 0 ORDER BY stock DESC LIMIT 5";
            $result = $conn->query($query);
            $response = "📋 Here are our top 5 products by stock level:\n";
            while ($product = $result->fetch_assoc()) {
                $response .= "• " . $product['name'] . ": " . $product['stock'] . " units\n";
            }
            return $response;
        }
    }

    // Price queries
    if (strpos($message, 'price') !== false || strpos($message, 'cost') !== false || strpos($message, 'how much') !== false) {
        $product_name = extractProductName($message);
        if ($product_name) {
            $query = "SELECT name, price FROM products WHERE name LIKE ?";
            $stmt = $conn->prepare($query);
            $search_term = "%$product_name%";
            $stmt->bind_param("s", $search_term);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                return "💰 The price of " . $product['name'] . " is $" . number_format($product['price'], 2) . ".";
            } else {
                return "❌ I couldn't find pricing information for that product. Please check our products page.";
            }
        } else {
            // Show price range
            $query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE stock > 0";
            $result = $conn->query($query);
            $price_range = $result->fetch_assoc();
            return "💰 Our product prices range from $" . number_format($price_range['min_price'], 2) . " to $" . number_format($price_range['max_price'], 2) . ". Check our products page for specific pricing!";
        }
    }

    // Order status queries
    if (strpos($message, 'order') !== false || strpos($message, 'status') !== false) {
        global $customer_id;
        $query = "SELECT COUNT(*) as total_orders, status, COUNT(*) as count
                  FROM customer_orders
                  WHERE customer_id = ?
                  GROUP BY status";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response = "📋 Your order summary:\n";
            while ($row = $result->fetch_assoc()) {
                $response .= "• " . ucfirst($row['status']) . ": " . $row['count'] . " orders\n";
            }
            return $response;
        } else {
            return "📭 You don't have any orders yet. Browse our products to place your first order!";
        }
    }

    // Help and general queries
    if (strpos($message, 'help') !== false || strpos($message, 'what can you do') !== false) {
        return "🤖 Hi! I'm your AI assistant. I can help you with:\n\n" .
               "📦 Check product stock availability\n" .
               "💰 Get product pricing information\n" .
               "📋 View your order status\n" .
               "🛒 Browse available products\n" .
               "📞 Contact support\n\n" .
               "Just ask me anything about our products or your orders!";
    }

    // Contact support
    if (strpos($message, 'support') !== false || strpos($message, 'contact') !== false || strpos($message, 'admin') !== false) {
        return "📞 For additional support, you can:\n\n" .
               "• Use the Support section in your dashboard\n" .
               "• Contact our admin team\n" .
               "• Check our FAQ section\n\n" .
               "Our support team will respond to your queries as soon as possible!";
    }

    // Default responses
    $greetings = ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening'];
    foreach ($greetings as $greeting) {
        if (strpos($message, $greeting) !== false) {
            return "👋 Hello! Welcome to our Stock Management System. How can I help you today?";
        }
    }

    if (strpos($message, 'thank') !== false) {
        return "🙏 You're welcome! Is there anything else I can help you with?";
    }

    // Generic response
    return "🤔 I'm here to help with product information, stock availability, pricing, and order status. Try asking me about specific products or use commands like 'stock of [product name]' or 'price of [product name]'!";
}

function extractProductName($message) {
    // Common patterns to extract product names
    $patterns = [
        '/stock of (.+)/i',
        '/price of (.+)/i',
        '/how much is (.+)/i',
        '/(.+) stock/i',
        '/(.+) price/i',
        '/about (.+)/i'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            return trim($matches[1]);
        }
    }

    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - Customer Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --bg-color: #f4f7fc;
            --main-color: #2c3e50;
            --accent-color: #3498db;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
            --success-color: #27ae60;
            --error-color: #e74c3c;
            --text-color: #2c3e50;
            --bot-color: #667eea;
            --user-color: #f093fb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--main-color);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .chat-container {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--bot-color), var(--accent-color));
            color: white;
            padding: 20px;
            text-align: center;
        }

        .chat-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .chat-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
        }

        .message.bot {
            justify-content: flex-start;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }

        .message.bot .message-content {
            background: linear-gradient(135deg, var(--bot-color), var(--accent-color));
            color: white;
            border-bottom-left-radius: 4px;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, var(--user-color), #f5576c);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .message.bot .message-avatar {
            background: var(--bot-color);
            color: white;
        }

        .message.user .message-avatar {
            background: var(--user-color);
            color: white;
        }

        .chat-input {
            padding: 20px;
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
        }

        .input-group {
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .message-input:focus {
            border-color: var(--accent-color);
        }

        .send-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .quick-questions {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
        }

        .quick-questions h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .question-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .question-tag {
            background: var(--accent-color);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .question-tag:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .typing-indicator {
            display: none;
            padding: 10px 20px;
            color: #666;
            font-style: italic;
        }

        .typing-indicator.show {
            display: block;
        }

        /* Back button */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .header-main h1 {
            margin: 0;
        }

        .header-main p {
            margin: 6px 0 0;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: #2c3e50;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .back-btn:hover {
            background: #34495e;
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.2);
        }

        .back-btn i {
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .chat-container {
                height: 500px;
            }

            .message-content {
                max-width: 85%;
            }

            .input-group {
                flex-direction: column;
            }

            .question-tags {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-main">
                <h1><i class="fas fa-robot"></i> AI Assistant</h1>
                <p>Get instant help with product information and support</p>
            </div>
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <h2><i class="fas fa-comments"></i> Chat with AI Assistant</h2>
                <p>Ask me anything about products, stock, or orders!</p>
            </div>

            <div class="quick-questions">
                <h3>Quick Questions:</h3>
                <div class="question-tags">
                    <span class="question-tag" onclick="sendQuickMessage('What products are available?')">Available Products</span>
                    <span class="question-tag" onclick="sendQuickMessage('Check stock of Laptop')">Check Stock</span>
                    <span class="question-tag" onclick="sendQuickMessage('What is the price of Keyboard?')">Product Price</span>
                    <span class="question-tag" onclick="sendQuickMessage('What is my order status?')">Order Status</span>
                    <span class="question-tag" onclick="sendQuickMessage('Help')">Help</span>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($chat_history)): ?>
                    <div class="message bot">
                        <div class="message-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-content">
                            👋 Hello <?php echo htmlspecialchars($customer['name']); ?>! Welcome to our AI assistant. I'm here to help you with product information, stock availability, pricing, and order support. What would you like to know?
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($chat_history as $msg): ?>
                        <div class="message <?php echo $msg['message_type'] === 'customer_to_ai' ? 'user' : 'bot'; ?>">
                            <div class="message-avatar">
                                <i class="fas fa-<?php echo $msg['message_type'] === 'customer_to_ai' ? 'user' : 'robot'; ?>"></i>
                            </div>
                            <div class="message-content">
                                <?php if ($msg['message_type'] === 'customer_to_ai'): ?>
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                <?php else: ?>
                                    <?php echo nl2br(htmlspecialchars($msg['response'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="typing-indicator" id="typingIndicator">
                <i class="fas fa-circle"></i>
                <i class="fas fa-circle"></i>
                <i class="fas fa-circle"></i>
                AI is typing...
            </div>

            <div class="chat-input">
                <form method="POST" action="" id="chatForm">
                    <div class="input-group">
                        <input type="text" name="message" class="message-input" placeholder="Ask me about products, stock, prices, or orders..." required>
                        <button type="submit" class="send-btn">
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Scroll to bottom on page load
        scrollToBottom();

        // Handle quick message sending
        function sendQuickMessage(message) {
            document.querySelector('.message-input').value = message;
            document.getElementById('chatForm').submit();
        }

        // Show typing indicator when form is submitted
        document.getElementById('chatForm').addEventListener('submit', function() {
            document.getElementById('typingIndicator').classList.add('show');
            scrollToBottom();
        });

        // Auto-focus on input field
        document.querySelector('.message-input').focus();

        // Handle Enter key to send message
        document.querySelector('.message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chatForm').submit();
            }
        });
    </script>
</body>
</html>