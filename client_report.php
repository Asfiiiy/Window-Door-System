<?php
// define('ALLOW_INCLUDE', true);
require_once 'db.php';

// Pagination
$per_page = 10;
$page_num = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = max(0, ($page_num - 1) * $per_page);

// Filters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$phone_search = isset($_GET['phone_search']) ? $conn->real_escape_string($_GET['phone_search']) : '';
$company_filter = isset($_GET['company']) ? (int)$_GET['company'] : 0;
$date_from = isset($_GET['date_from']) ? $conn->real_escape_string($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? $conn->real_escape_string($_GET['date_to']) : '';

// Base query
$sql = "
    SELECT 
        c.*, 
        co.name AS company_name,
        COUNT(*) OVER() AS total_count
    FROM clients c
    LEFT JOIN companies co ON c.company_id = co.id
";

$conditions = [];
if (!empty($search)) {
    $conditions[] = "(c.name LIKE '%$search%' OR c.address LIKE '%$search%')";
}
if (!empty($phone_search)) {
    $conditions[] = "(c.phone LIKE '%$phone_search%')";
}
if ($company_filter > 0) {
    $conditions[] = "c.company_id = $company_filter";
}
if (!empty($date_from) && !empty($date_to)) {
    $conditions[] = "c.created_at BETWEEN '$date_from' AND '$date_to'";
} elseif (!empty($date_from)) {
    $conditions[] = "c.created_at >= '$date_from'";
} elseif (!empty($date_to)) {
    $conditions[] = "c.created_at <= '$date_to'";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY c.created_at DESC LIMIT $offset, $per_page";
$result = $conn->query($sql);

$clients = [];
$total_count = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($total_count === 0) {
            $total_count = $row['total_count'];
        }
        unset($row['total_count']);
        $clients[] = $row;
    }
}

$companies = [];
$company_result = $conn->query("SELECT id, name FROM companies");
if ($company_result && $company_result->num_rows > 0) {
    while ($row = $company_result->fetch_assoc()) {
        $companies[] = $row;
    }
}

$total_pages = ceil($total_count / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients Management</title>
    <style>
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
        
        .search-filter {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-filter input, 
        .search-filter select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 150px;
        }
        
        .search-filter button {
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            padding: 8px 15px;
        }
        
        .search-filter .reset-btn {
    background: linear-gradient(135deg, #f44336, #e53935); /* Vibrant gradient */
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.5px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(244, 67, 54, 0.4);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    z-index: 1;
}

.search-filter .reset-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -75%;
    width: 50%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transform: skewX(-20deg);
    transition: left 0.5s ease;
    z-index: 0;
}

.search-filter .reset-btn:hover::before {
    left: 125%; /* Slide shine effect */
}

.search-filter .reset-btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 6px 20px rgba(244, 67, 54, 0.6);
}
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.02); }
  100% { transform: scale(1); }
}

.search-filter .reset-btn {
    animation: pulse 2.5s infinite ease-in-out;
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
        
        .total-quotations {
            margin-bottom: 15px;
            font-style: italic;
            color: #666;
        }
        
        button, input, optgroup, select, textarea {
            margin: 0;
            font-family: inherit;
            line-height: inherit;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #666;
        }
    </style>
    </style>
</head>
<body>
<div class="container">
    <h3>Clients Management (<?= $total_count ?> clients)</h3>

    <form method="get" action="index.php" id="filterForm">
        <input type="hidden" name="page" value="reports_clients">
        <div class="search-filter">
            <input type="text" name="search" placeholder="Client name/address" value="<?= htmlspecialchars($search) ?>" oninput="this.form.submit()">
            <input type="text" name="phone_search" placeholder="Phone number" value="<?= htmlspecialchars($phone_search) ?>" oninput="this.form.submit()">
            <label>From: <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" onchange="this.form.submit()"></label>
            <label>To: <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" onchange="this.form.submit()"></label>
            <select name="company" onchange="this.form.submit()">
                <option value="0">All Companies</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= $company['id'] ?>" <?= $company_filter == $company['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($company['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="index.php?page=reports_clients" class="reset-btn">Reset</a>
        </div>
    </form>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Company</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($clients)): ?>
            <tr><td colspan="7" style="text-align:center;">No clients found</td></tr>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <tr>
                    <td><?= htmlspecialchars($client['id']) ?></td>
                    <td><?= htmlspecialchars($client['name']) ?></td>
                    <td><?= htmlspecialchars($client['company_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($client['phone'] ?? '') ?></td>
                    <td><?= htmlspecialchars(substr($client['address'] ?? '', 0, 50)) ?><?= strlen($client['address'] ?? '') > 50 ? '...' : '' ?></td>
                    <td><?= date('M d, Y', strtotime($client['created_at'])) ?></td>
                    <td class="actions">
                        <a href="index.php?page=reports_clients&delete_client=<?= $client['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this client?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page_num > 1): ?>
                <a href="index.php?page=reports_clients&p=1&search=<?= urlencode($search) ?>&phone_search=<?= urlencode($phone_search) ?>&company=<?= $company_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">First</a>
                <a href="index.php?page=reports_clients&p=<?= $page_num - 1 ?>&search=<?= urlencode($search) ?>&phone_search=<?= urlencode($phone_search) ?>&company=<?= $company_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Prev</a>
            <?php endif; ?>

            <?php 
            $start = max(1, $page_num - 2);
            $end = min($total_pages, $page_num + 2);
            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $page_num): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="index.php?page=reports_clients&p=<?= $i ?>&search=<?= urlencode($search) ?>&phone_search=<?= urlencode($phone_search) ?>&company=<?= $company_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page_num < $total_pages): ?>
                <a href="index.php?page=reports_clients&p=<?= $page_num + 1 ?>&search=<?= urlencode($search) ?>&phone_search=<?= urlencode($phone_search) ?>&company=<?= $company_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Next</a>
                <a href="index.php?page=reports_clients&p=<?= $total_pages ?>&search=<?= urlencode($search) ?>&phone_search=<?= urlencode($phone_search) ?>&company=<?= $company_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Last</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Handle delete
if (isset($_GET['delete_client'])) {
    $delete_id = (int)$_GET['delete_client'];
    $delete_sql = "DELETE FROM clients WHERE id = $delete_id";
    if ($conn->query($delete_sql)) {
        echo "<script>alert('Client deleted successfully'); window.location.href='index.php?page=reports_clients';</script>";
    } else {
        echo "<script>alert('Error deleting client'); window.location.href='index.php?page=reports_clients';</script>";
    }
}
?>
</body>
</html>
