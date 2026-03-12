<?php
// Root-level page: set base path so header uses correct asset links
$base_path = '';
include 'includes/header.php';
?>

<h1>5-Day Development Learning Journal</h1>

<table style="width:100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="border:1px solid #e1e4ea; padding:8px;">Day</th>
            <th style="border:1px solid #e1e4ea; padding:8px;">Activity / Task</th>
            <th style="border:1px solid #e1e4ea; padding:8px;">Detailed Description</th>
            <th style="border:1px solid #e1e4ea; padding:8px;">New Skills Learned</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border:1px solid #e1e4ea; padding:8px;">Day 1</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Database Architecture</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Designed the database structure and ER relationships for the stock system.</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">1. Relational Database Design<br>2. SQL Data Types (INT, VARCHAR, DECIMAL)<br>3. Primary & Foreign Keys<br>4. PHP Database Connection (MySQLi Object-Oriented)<br>5. PHPMyAdmin Operations</td>
        </tr>
        <tr>
            <td style="border:1px solid #e1e4ea; padding:8px;">Day 2</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Authentication Systems</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Developed login/logout and authentication flows with role checks.</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">1. PHP Session Management<br>2. SQL Injection Prevention (Prepared Statements)<br>3. HTTP Header Redirection (header())<br>4. Role-Based Access Control (RBAC)<br>5. Secure Form Handling ($_POST)</td>
        </tr>
        <tr>
            <td style="border:1px solid #e1e4ea; padding:8px;">Day 3</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">User CRUD Operations</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Implemented user create/read/update/delete interfaces and handlers.</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">1. CRUD Operations<br>2. URL Parameter Handling ($_GET)<br>3. SQL WHERE Clauses<br>4. Error Handling in SQL execution<br>5. HTML-PHP Data Binding</td>
        </tr>
        <tr>
            <td style="border:1px solid #e1e4ea; padding:8px;">Day 4</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Supplier Management</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Built backend pages to add, list, and validate suppliers.</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">1. PHP While Loops for Data Fetching<br>2. Associative Arrays (fetch_assoc)<br>3. Dynamic Dropdown Logic<br>4. Data Validation (Email/Phone checks)<br>5. Single File Logic (Handling Add & List in one file)</td>
        </tr>
        <tr>
            <td style="border:1px solid #e1e4ea; padding:8px;">Day 5</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Product Inventory</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">Added product entry forms, validation, and inventory logic.</td>
            <td style="border:1px solid #e1e4ea; padding:8px;">1. Data Type Validation (Int vs Float)<br>2. Duplicate Data Entry Prevention<br>3. Mathematical Functions in PHP<br>4. Date & Time Handling (NOW() in SQL)<br>5. Complex Insert Queries</td>
        </tr>
    </tbody>
</table>

<?php include 'includes/footer.php'; ?>
