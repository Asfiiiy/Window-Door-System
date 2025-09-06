<?php
// Database connection
$db = new mysqli('localhost', 'u742242489_decore', 'F9MGmm6:', 'u742242489_decore');


// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle expense deletion
if (isset($_GET['delete'])) {
    $expense_id = intval($_GET['delete']);
    $db->query("DELETE FROM expenses WHERE id = $expense_id");
    // Note: expense_items will be deleted automatically due to ON DELETE CASCADE
    header("Location: view_expenses.php");
    exit;
}

// Fetch all expenses with company names
$expenses = $db->query("
    SELECT e.*, c.name as company_name 
    FROM expenses e
    JOIN companies c ON e.company_id = c.id
    ORDER BY e.expense_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expenses</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .actions { white-space: nowrap; }
    </style>
</head>
<body>
    <h1>Expense Records</h1>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Company</th>
                <th>Description</th>
                <th>Total Amount</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($expense = $expenses->fetch_assoc()): ?>
                <tr>
                    <td><?= $expense['expense_date'] ?></td>
                    <td><?= $expense['company_name'] ?></td>
                    <td><?= $expense['description'] ?></td>
                    <td><?= number_format($expense['total_amount'], 2) ?></td>
                    <td><?= $expense['created_at'] ?></td>
                    <td class="actions">
                        <a href="index.php?page=reports_views&id=<?= $expense['id'] ?>" class="view-btn">View</a>
                        <a href="index.php?page=reports_expenses&delete=<?= $expense['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this expense?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <p><a href="add_expense.php">Add New Expense</a></p>
</body>
</html>