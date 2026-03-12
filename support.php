<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit;
}

include '../config.php';
include '../includes/header.php';

$message = '';

if (isset($_POST['submit_support'])) {
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);

    if (!empty($subject) && !empty($message_text)) {
        // In a real system, you'd save this to a support_tickets table
        // For now, just show a success message
        $message = '<div class="success-message">Your support request has been submitted successfully! Our team will get back to you within 24 hours.</div>';
    } else {
        $message = '<div class="error-message">Please fill in all required fields.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support - Customer</title>

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
            --shadow-color: rgba(0, 0, 0, 0.1);
            --text-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--main-color), var(--accent-color));
            color: white;
            border-radius: 8px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }

        .support-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .support-form, .contact-info {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .support-form h2, .contact-info h2 {
            margin: 0 0 20px 0;
            color: var(--text-color);
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-group input[type="text"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .contact-item i {
            font-size: 1.2rem;
            color: var(--accent-color);
            margin-right: 15px;
            width: 20px;
        }

        .contact-item div {
            flex: 1;
        }

        .contact-item strong {
            display: block;
            color: var(--text-color);
        }

        .contact-item span {
            color: #666;
        }

        .faq-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
        }

        .faq-item {
            margin-bottom: 15px;
        }

        .faq-question {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .faq-answer {
            color: #666;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .support-container {
                grid-template-columns: 1fr;
            }

            .support-form, .contact-info {
                padding: 20px;
            }
        }

        .back-navigation {
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .back-btn:hover {
            background: #2980b9;
            color: white;
        }

        .back-btn i {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <div class="page-header">
        <h1>Customer Support</h1>
    </div>

    <div class="back-navigation">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="support-container">
        <div class="support-form">
            <h2>Submit a Support Request</h2>

            <?php echo $message; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a subject</option>
                        <option value="Order Issue">Order Issue</option>
                        <option value="Product Question">Product Question</option>
                        <option value="Delivery Problem">Delivery Problem</option>
                        <option value="Return/Refund">Return/Refund</option>
                        <option value="Technical Issue">Technical Issue</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" placeholder="Please describe your issue in detail..." required></textarea>
                </div>

                <button type="submit" name="submit_support" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Submit Request
                </button>
            </form>
        </div>

        <div class="contact-info">
            <h2>Contact Information</h2>

            <div class="contact-item">
                <i class="fa-solid fa-envelope"></i>
                <div>
                    <strong>Email</strong>
                    <span>support@stockmanagement.com</span>
                </div>
            </div>

            <div class="contact-item">
                <i class="fa-solid fa-phone"></i>
                <div>
                    <strong>Phone</strong>
                    <span>+880 123 456 7890</span>
                </div>
            </div>

            <div class="contact-item">
                <i class="fa-solid fa-clock"></i>
                <div>
                    <strong>Support Hours</strong>
                    <span>Monday - Friday: 9:00 AM - 6:00 PM</span>
                </div>
            </div>

            <div class="contact-item">
                <i class="fa-solid fa-map-marker-alt"></i>
                <div>
                    <strong>Address</strong>
                    <span>123 Business District, Dhaka, Bangladesh</span>
                </div>
            </div>

            <div class="faq-section">
                <h3>Frequently Asked Questions</h3>

                <div class="faq-item">
                    <div class="faq-question">How long does order processing take?</div>
                    <div class="faq-answer">Orders are typically processed within 1-2 business days after approval.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Can I modify my order after placing it?</div>
                    <div class="faq-answer">You can modify pending orders by contacting our support team within 2 hours of placement.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">What is your return policy?</div>
                    <div class="faq-answer">We accept returns within 7 days of delivery for unused items in original packaging.</div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>