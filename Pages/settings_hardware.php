<?php
if (!defined('ALLOW_INCLUDE')) {
    die("Access denied");
}
require_once 'db.php';

// Current page URL
$current_url = "index.php?page=settings_hardware";

// Get all companies for dropdown
$companies = [];
$company_result = $conn->query("SELECT id, name FROM companies");
if ($company_result && $company_result->num_rows > 0) {
    while ($row = $company_result->fetch_assoc()) {
        $companies[] = $row;
    }
}

// Get selected company (from GET or default to first company)
$selected_company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : ($companies[0]['id'] ?? 0);

// === DELETE ===
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM hardware WHERE id = ? AND company_id = ?");
    $stmt->bind_param("ii", $delete_id, $selected_company_id);
    if ($stmt->execute()) {
        header("Location: $current_url&company_id=$selected_company_id");
        exit();
    } else {
        echo "<script>alert('Failed to delete hardware.'); window.location.href='$current_url&company_id=$selected_company_id';</script>";
        exit();
    }
}

// === UPDATE ===
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $name = $_POST['name'];
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $bundle_quantity = !empty($_POST['bundle_quantity']) ? intval($_POST['bundle_quantity']) : null;
    $bundle_price = !empty($_POST['bundle_price']) ? floatval($_POST['bundle_price']) : null;

    $stmt = $conn->prepare("UPDATE hardware SET name=?, quantity=?, price=?, bundle_quantity=?, bundle_price=? WHERE id=? AND company_id=?");
    $stmt->bind_param("sidddii", $name, $quantity, $price, $bundle_quantity, $bundle_price, $edit_id, $selected_company_id);
    if ($stmt->execute()) {
        header("Location: $current_url&company_id=$selected_company_id");
        exit();
    } else {
        echo "<script>alert('Failed to update hardware.'); window.location.href='$current_url&company_id=$selected_company_id';</script>";
        exit();
    }
}

// === ADD NEW ===
if (isset($_POST['add_new'])) {
    $name = $_POST['name'];
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    $bundle_quantity = !empty($_POST['bundle_quantity']) ? intval($_POST['bundle_quantity']) : null;
    $bundle_price = !empty($_POST['bundle_price']) ? floatval($_POST['bundle_price']) : null;

    $stmt = $conn->prepare("INSERT INTO hardware (company_id, name, quantity, price, bundle_quantity, bundle_price) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiddi", $selected_company_id, $name, $quantity, $price, $bundle_quantity, $bundle_price);
    if ($stmt->execute()) {
        header("Location: $current_url&company_id=$selected_company_id");
        exit();
    } else {
        echo "<script>alert('Failed to add hardware.'); window.location.href='$current_url&company_id=$selected_company_id';</script>";
        exit();
    }
}

// === GET HARDWARE LIST FOR SELECTED COMPANY ===
$hardware = [];
if ($selected_company_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM hardware WHERE company_id = ?");
    $stmt->bind_param("i", $selected_company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hardware = $result->fetch_all(MYSQLI_ASSOC);
}

// === GET HARDWARE FOR EDITING ===
$edit_hardware = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $conn->prepare("SELECT * FROM hardware WHERE id = ? AND company_id = ?");
    $stmt->bind_param("ii", $edit_id, $selected_company_id);
    $stmt->execute();
    $edit_hardware = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hardware Management</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9f9f9; 
            margin: 0;
            /*padding: 20px;*/
        }
        .container {
            /*max-width: 1200px;*/
            margin: 0 auto;
            background: #fff;
            /*padding: 20px;*/
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #444;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: 20px;
        }
        th, td { 
            padding: 10px; 
            border: 1px solid #ddd;
            text-align: left;
        }
        th { 
            background-color: #333; 
            color: white; 
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin: 0 2px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        .btn-edit {
            background-color: #2196F3;
            color: white;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .btn-cancel {
            background-color: #ff9800;
            color: white;
        }
        .form-container {
            margin: 20px 0;
            padding: 15px;
            background: #f2f2f2;
            border-radius: 4px;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
            align-items: center;
        }
        .form-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        label {
            font-weight: 600;
            white-space: nowrap;
        }
        input[type="text"],
        input[type="number"],
        select {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .company-selector {
            margin-bottom: 15px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hardware Management</h2>
        
        <!-- Company Selection -->
        <div class="company-selector">
            <form method="get" action="">
                <input type="hidden" name="page" value="settings_hardware">
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_id">Company:</label>
                        <select name="company_id" id="company_id" onchange="this.form.submit()">
                            <?php foreach ($companies as $company): ?>
                                <option value="<?= $company['id'] ?>" <?= $selected_company_id == $company['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($company['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Add/Edit Form -->
        <div class="form-container">
            <?php if ($edit_hardware): ?>
                <form method="post" action="<?= $current_url ?>&company_id=<?= $selected_company_id ?>">
                    <input type="hidden" name="edit_id" value="<?= $edit_hardware['id'] ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name:</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($edit_hardware['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Quantity:</label>
                            <input type="number" name="quantity" value="<?= $edit_hardware['quantity'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Price:</label>
                            <input type="number" step="0.01" name="price" value="<?= $edit_hardware['price'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Bundle Qty:</label>
                            <input type="number" name="bundle_quantity" value="<?= $edit_hardware['bundle_quantity'] ?>">
                        </div>
                        <div class="form-group">
                            <label>Bundle Price:</label>
                            <input type="number" step="0.01" name="bundle_price" value="<?= $edit_hardware['bundle_price'] ?>">
                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="btn-submit">Update</button>
                            <a href="<?= $current_url ?>&company_id=<?= $selected_company_id ?>" class="btn btn-cancel">Cancel</a>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" action="<?= $current_url ?>&company_id=<?= $selected_company_id ?>">
                    <input type="hidden" name="add_new" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name:</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Quantity:</label>
                            <input type="number" name="quantity" required>
                        </div>
                        <div class="form-group">
                            <label>Price:</label>
                            <input type="number" step="0.01" name="price" required>
                        </div>
                        <div class="form-group">
                            <label>Bundle Qty:</label>
                            <input type="number" name="bundle_quantity">
                        </div>
                        <div class="form-group">
                            <label>Bundle Price:</label>
                            <input type="number" step="0.01" name="bundle_price">
                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="btn-submit">Add Hardware</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Hardware List -->
        <table>
            <tr>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Bundle Qty</th>
                <th>Bundle Price</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($hardware)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No hardware items found for selected company</td>
                </tr>
            <?php else: ?>
                <?php foreach ($hardware as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['name']) ?></td>
                    <td><?= $h['quantity'] ?></td>
                    <td>Rs<?= number_format($h['price'], 2) ?></td>
                    <td><?= $h['bundle_quantity'] ?? '-' ?></td>
                    <td><?= $h['bundle_price'] ? 'Rs' . number_format($h['bundle_price'], 2) : '-' ?></td>
                    <td>
                        <a href="<?= $current_url ?>&edit_id=<?= $h['id'] ?>&company_id=<?= $selected_company_id ?>" class="btn btn-edit">Edit</a>
                        <a href="<?= $current_url ?>&delete_id=<?= $h['id'] ?>&company_id=<?= $selected_company_id ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this hardware?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>