<?php
// Database connection
// Database connection
$db = new mysqli('localhost', 'u742242489_decore', 'F9MGmm6:', 'u742242489_decore');

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Insert expense header
        $stmt = $db->prepare("INSERT INTO expenses (company_id, expense_date, description, total_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issd", $_POST['company_id'], $_POST['expense_date'], $_POST['description'], $_POST['total_amount']);
        $stmt->execute();
        $expense_id = $db->insert_id;
        
        // Insert expense items
        $itemStmt = $db->prepare("INSERT INTO expense_items (expense_id, item_name, amount) VALUES (?, ?, ?)");
        
        foreach ($_POST['item_name'] as $index => $item_name) {
            $amount = $_POST['amount'][$index];
            $itemStmt->bind_param("isd", $expense_id, $item_name, $amount);
            $itemStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        $success = "Expense added successfully!";
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error adding expense: " . $e->getMessage();
    }
}

// Fetch companies for dropdown
$companies = $db->query("SELECT id, name FROM companies");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            /* max-width: 1000px; */
            margin: 0 auto;
            /* padding: 20px; */
            /* line-height: 1.6; */
        }
        
        .top-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            align-items: center;
        }
        
        .form-group label {
            margin-right: 10px;
            min-width: 80px;
        }
        
        select, input[type="date"], input[type="text"], 
        input[type="number"], textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea {
            width: 250px;
            height: 36px;
        }
        
        .items-container {
            margin: 20px 0;
        }
        
        .item-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }
        
        .item-row input[type="text"] {
            width: 200px;
        }
        
        .item-row input[type="number"] {
            width: 120px;
        }
        
        button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .remove-item {
            background-color: #f44336;
        }
        
        .remove-item:hover {
            background-color: #d32f2f;
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        h3 {
            margin: 25px 0 15px 0;
            color: #555;
        }
        
        .total-amount {
            font-weight: bold;
            margin: 20px 0;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <h1>Add New Expense</h1>
    
    <?php if (isset($error)): ?>
        <div class="message error"><?= $error ?></div>
    <?php elseif (isset($success)): ?>
        <div class="message success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="top-row">
            <div class="form-group">
                <label>Company:</label>
                <select name="company_id" required>
                    <option value="">Select Company</option>
                    <?php while ($company = $companies->fetch_assoc()): ?>
                        <option value="<?= $company['id'] ?>"><?= $company['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="expense_date" required>
            </div>
            
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description"></textarea>
            </div>
        </div>
        
        <h3>Expense Items</h3>
        <div class="items-container" id="items-container">
            <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="item-row" id="item-row-<?= $i ?>">
                    <input type="text" name="item_name[]" placeholder="Item name" required>
                    <input type="number" name="amount[]" placeholder="Amount" step="0.01" required>
                    <?php if ($i >= 4): ?>
                        <button type="button" class="remove-item" data-row="item-row-<?= $i ?>">Remove</button>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        
        <button type="button" id="add-item">Add Another Item</button>
        
        <div class="total-amount">
            <div class="form-group">
                <label>Total Amount:</label>
                <input type="number" name="total_amount" step="0.01" required>
            </div>
        </div>
        
        <button type="submit">Save Expense</button>
    </form>
    
    <script>
        $(document).ready(function() {
            let itemCount = 4;
            
            // Add new item row
            $('#add-item').click(function() {
                const newRow = `
                    <div class="item-row" id="item-row-${itemCount}">
                        <input type="text" name="item_name[]" placeholder="Item name" required>
                        <input type="number" name="amount[]" placeholder="Amount" step="0.01" required>
                        <button type="button" class="remove-item" data-row="item-row-${itemCount}">Remove</button>
                    </div>
                `;
                $('#items-container').append(newRow);
                itemCount++;
            });
            
            // Remove item row
            $(document).on('click', '.remove-item', function() {
                $('#' + $(this).data('row')).remove();
                calculateTotal();
            });
            
            // Calculate total amount when item amounts change
            $(document).on('input', 'input[name="amount[]"]', function() {
                calculateTotal();
            });
            
            function calculateTotal() {
                let total = 0;
                $('input[name="amount[]"]').each(function() {
                    const val = parseFloat($(this).val()) || 0;
                    total += val;
                });
                $('input[name="total_amount"]').val(total.toFixed(2));
            }
        });
    </script>
</body>
</html>