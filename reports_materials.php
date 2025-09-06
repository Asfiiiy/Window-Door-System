<?php
if (!defined('ALLOW_INCLUDE')) {
    die("Access denied");
}
require_once 'db.php';

// Pagination variables
$per_page = 10;
$page_num = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = max(0, ($page_num - 1) * $per_page);

// Filter variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$company_filter = isset($_GET['company']) ? (int)$_GET['company'] : 0;

// Base SQL query
$sql = "
    SELECT 
        m.*, 
        co.name AS company_name,
        COUNT(*) OVER() AS total_count
    FROM materials m
    LEFT JOIN companies co ON m.company_id = co.id
";

// Add conditions based on filters
$conditions = [];
if (!empty($search)) {
    $conditions[] = "(m.name LIKE '%$search%' OR m.length LIKE '%$search%')";
}
if ($company_filter > 0) {
    $conditions[] = "m.company_id = $company_filter";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

// Add sorting and pagination
$sql .= " ORDER BY m.created_at DESC LIMIT $offset, $per_page";

$result = $conn->query($sql);

// Fetch data into array and get total count
$materials = [];
$total_count = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($total_count === 0) {
            $total_count = $row['total_count'];
        }
        unset($row['total_count']);
        $materials[] = $row;
    }
} else {
    $materials = [];
}

// Get companies for filter dropdown
$companies = [];
$company_result = $conn->query("SELECT id, name FROM companies");
if ($company_result && $company_result->num_rows > 0) {
    while ($row = $company_result->fetch_assoc()) {
        $companies[] = $row;
    }
}

// Calculate total pages
$total_pages = ceil($total_count / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials Management</title>
        <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            background-color: #f9f9f9;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h3 {
            color: #444;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .actions {
            white-space: nowrap;
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
        
        .search-filter {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-filter input, .search-filter select {
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-filter button {
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .pagination {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 4px 7px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover {
            background-color: #f2f2f2;
        }
        
        .pagination .current {
            background-color: #4caf50;
            color: white;
            border-color: #4caf50;
        }
        
        .total-items {
            margin-bottom: 15px;
            font-style: italic;
            color: #666;
        }
        
        a.d-block.py-2.px-3.mb-2.sidebar-item {
            font-size: smaller;
        }
        
        input[type="text"] {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>Materials Management (<?= $total_count ?> materials)</h3>
        
        <form method="get" action="">
            <input type="hidden" name="page" value="reports_materials">
            <div class="search-filter">
                <input type="text" name="search" placeholder="Search by name or length..." value="<?= !empty($search) ? htmlspecialchars($search) : '' ?>">
                <select name="company">
                    <option value="0">All Companies</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['id'] ?>" <?= $company_filter == $company['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($company['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filter</button>
                <a href="index.php?page=reports_materials" style="padding: 8px 15px; background-color: #f44336; color: white; border-radius: 4px; text-decoration: none;">Reset</a>
            </div>
        </form>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Length</th>
                    <th>Price/Foot</th>
                    <th>Bundle Qty</th>
                    <th>Bundle Price</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materials)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No materials found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?= htmlspecialchars($material['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($material['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($material['company_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($material['length'] ?? '') ?></td>
                        <td>Rs<?= isset($material['price_per_foot']) ? number_format((float)$material['price_per_foot'], 2) : '0.00' ?></td>
                        <td><?= htmlspecialchars($material['bundle_quantity'] ?? '') ?></td>
                        <td>Rs<?= isset($material['bundle_price']) ? number_format((float)$material['bundle_price'], 2) : '0.00' ?></td>
                        <td><?= !empty($material['created_at']) ? date('M d, Y', strtotime($material['created_at'])) : '' ?></td>
                        <td class="actions">
                            <!-- <a href="index.php?page=edit_material&id=<?= $material['id'] ?>" class="btn btn-edit">Edit</a> -->
                            <a href="index.php?page=reports_materials&delete=<?= $material['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this material?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page_num > 1): ?>
                <a href="index.php?page=reports_materials&p=1&search=<?= urlencode($search) ?>&company=<?= $company_filter ?>">First</a>
                <a href="index.php?page=reports_materials&p=<?= $page_num - 1 ?>&search=<?= urlencode($search) ?>&company=<?= $company_filter ?>">Prev</a>
            <?php endif; ?>
            
            <?php 
            $start = max(1, $page_num - 2);
            $end = min($total_pages, $page_num + 2);
            
            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $page_num): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="index.php?page=reports_materials&p=<?= $i ?>&search=<?= urlencode($search) ?>&company=<?= $company_filter ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page_num < $total_pages): ?>
                <a href="index.php?page=reports_materials&p=<?= $page_num + 1 ?>&search=<?= urlencode($search) ?>&company=<?= $company_filter ?>">Next</a>
                <a href="index.php?page=reports_materials&p=<?= $total_pages ?>&search=<?= urlencode($search) ?>&company=<?= $company_filter ?>">Last</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php
    if (isset($_GET['delete'])) {
        $delete_id = (int)$_GET['delete'];
        $delete_sql = "DELETE FROM materials WHERE id = $delete_id";
        if ($conn->query($delete_sql)) {
            echo "<script>alert('Material deleted successfully'); window.location.href='index.php?page=reports_materials';</script>";
        } else {
            echo "<script>alert('Error deleting material'); window.location.href='index.php?page=reports_materials';</script>";
        }
    }
    ?>
</body>
</html>