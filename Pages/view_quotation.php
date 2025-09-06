<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../db.php');

// Handle deletion if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client_id'])) {
    $client_id = (int)$_POST['delete_client_id'];
    $company_id = (int)$_POST['company_id'];
    
    // Perform deletion
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to same page to prevent form resubmission
    header("Location: index.php?page=report_quotation&company_id=".$company_id);
    exit();
}

// Fetch all companies for dropdown
$companies = [];
$stmt = $conn->prepare("SELECT id, name FROM companies ORDER BY name");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $stmt->close();
}

// Get selected company - check GET first, then POST, then session, then default to first company
if (isset($_GET['company_id'])) {
    $company_id = (int)$_GET['company_id'];
} elseif (isset($_POST['company_id'])) {
    $company_id = (int)$_POST['company_id'];
} elseif (isset($_SESSION['current_company_id'])) {
    $company_id = (int)$_SESSION['current_company_id'];
} else {
    $company_id = $companies[0]['id'] ?? 0;
}

// Store company_id in session to persist across requests
$_SESSION['current_company_id'] = $company_id;

// Get search parameters
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $records_per_page;

// Base query for counting and fetching
$base_query = "FROM clients c 
LEFT JOIN window_calculation_details wcd ON c.id = wcd.client_id AND c.company_id = wcd.company_id
WHERE c.company_id = ?";

$params = [$company_id];
$param_types = "i";

// Add search conditions
if (!empty($search_name)) {
    $base_query .= " AND c.name LIKE ?";
    $params[] = "%$search_name%";
    $param_types .= "s";
}

if (!empty($from_date) && !empty($to_date)) {
    $base_query .= " AND DATE(wcd.created_at) BETWEEN ? AND ?";
    $params[] = $from_date;
    $params[] = $to_date;
    $param_types .= "ss";
} elseif (!empty($from_date)) {
    $base_query .= " AND DATE(wcd.created_at) >= ?";
    $params[] = $from_date;
    $param_types .= "s";
} elseif (!empty($to_date)) {
    $base_query .= " AND DATE(wcd.created_at) <= ?";
    $params[] = $to_date;
    $param_types .= "s";
}

// Get total number of clients for the selected company with filters
$total_clients = 0;
$count_query = "SELECT COUNT(DISTINCT c.id) $base_query";
$stmt = $conn->prepare($count_query);
if ($stmt) {
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $stmt->bind_result($total_clients);
    $stmt->fetch();
    $stmt->close();
}

// Calculate total pages for pagination
$total_pages = ceil($total_clients / $records_per_page);

// Fetch clients for the selected company with pagination and filters
$clients = [];
$select_query = "SELECT DISTINCT c.id, c.name, c.phone, c.address $base_query ORDER BY c.name LIMIT ? OFFSET ?";
$stmt = $conn->prepare($select_query);
if ($stmt) {
    // Add pagination parameters
    $params[] = $records_per_page;
    $params[] = $offset;
    $param_types .= "ii";
    
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
    $stmt->close();
}

// Function to build query string
function buildQueryString($params) {
    $query = [];
    foreach ($params as $key => $value) {
        if ($key != 'p' && $value !== '') {
            $query[] = "$key=" . urlencode($value);
        }
    }
    return implode('&', $query);
}

// Build base query string for pagination
$queryParams = [
    'page' => 'report_quotation',
    'company_id' => $company_id,
    'search_name' => $search_name,
    'from_date' => $from_date,
    'to_date' => $to_date
];
$baseQueryString = buildQueryString($queryParams);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Quotations</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 14px;
      background-color: #f8f9fa;
    }
    .container {
      max-width: 100%;
      padding: 0;
    }
    .table th, .table td {
      padding: 8px;
    }
    .form-select {
      height: 32px;
      font-size: 14px;
      padding: 4px 8px;
    }
    .btn-sm {
      padding: 4px 8px;
      font-size: 12px;
    }
    .card-header {
      padding: 8px 12px;
      font-size: 16px;
    }
    select.form-select.form-select-sm {
      width: 15%;
      align-items: center;
    }
    .search-container {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
      align-items: center;
      flex-wrap: wrap;
    }
    .search-container input {
      height: 32px;
      font-size: 14px;
      padding: 4px 8px;
    }
    .search-container .form-control {
      width: auto;
      flex-grow: 1;
      max-width: 200px;
    }
    .date-range {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .date-range span {
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">View Quotations</h5>
      </div>
      <div class="card-body p-2">
        <form method="get" class="mb-2">
          <input type="hidden" name="page" value="report_quotation">
          <div class="search-container">
            <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php foreach ($companies as $comp): ?>
                <option value="<?= $comp['id'] ?>" <?= $comp['id'] == $company_id ? 'selected' : '' ?>>
                  <?= htmlspecialchars($comp['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            
            <input type="text" name="search_name" class="form-control form-control-sm" 
                   placeholder="Search by client name" value="<?= htmlspecialchars($search_name) ?>"
                   oninput="this.form.submit()">
                   
            <div class="date-range">
              <span>From:</span>
              <input type="date" name="from_date" class="form-control form-control-sm" 
                     value="<?= htmlspecialchars($from_date) ?>"
                     onchange="this.form.submit()">
              <span>To:</span>
              <input type="date" name="to_date" class="form-control form-control-sm" 
                     value="<?= htmlspecialchars($to_date) ?>"
                     onchange="this.form.submit()">
            </div>
                   
            <?php if (!empty($search_name) || !empty($from_date) || !empty($to_date)): ?>
              <a href="index.php?page=report_quotation&company_id=<?= $company_id ?>" class="btn btn-sm btn-secondary">
                Clear Filters
              </a>
            <?php endif; ?>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="bg-primary text-white">
              <tr>
                <th>#</th>
                <th>Client Name</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($clients)): ?>
                <tr>
                  <td colspan="5" class="text-center py-2">No clients found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($clients as $index => $client): ?>
                  <?php
                  // Fetch latest quotation_number for this client
                  $quotation_number = '';
                  $stmt_quote = $conn->prepare("SELECT quotation_number FROM window_calculation_details WHERE client_id = ? AND company_id = ? ORDER BY id DESC LIMIT 1");
                  if ($stmt_quote) {
                      $stmt_quote->bind_param("ii", $client['id'], $company_id);
                      $stmt_quote->execute();
                      $stmt_quote->bind_result($quotation_number);
                      $stmt_quote->fetch();
                      $stmt_quote->close();
                  }
                  ?>
                  <tr>
                    <td><?= $offset + $index + 1 ?></td>
                    <td><?= htmlspecialchars($client['name']) ?></td>
                    <td><?= htmlspecialchars($client['phone']) ?></td>
                    <td><?= htmlspecialchars($client['address']) ?></td>
                    <td>
                      <?php if ($quotation_number): ?>
                        <a href="index.php?page=reports_worker&quotation_number=<?= urlencode($quotation_number) ?>&client_id=<?= $client['id'] ?>&company_id=<?= $company_id ?>" class="btn btn-sm btn-primary">
                          <i class="fas fa-edit"></i> Worker
                        </a>
                      <?php else: ?>
                        <span class="text-muted">No Quotation</span>
                      <?php endif; ?>

                      <a href="client_quotation.php?client_id=<?= $client['id'] ?>&company_id=<?= $company_id ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> View
                      </a>

                      <form method="post" action="index.php?page=report_quotation&company_id=<?= $company_id ?>" style="display:inline;">
                        <input type="hidden" name="delete_client_id" value="<?= $client['id'] ?>">
                        <input type="hidden" name="company_id" value="<?= $company_id ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this?')">
                          <i class="fas fa-trash"></i> Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm justify-content-center mt-2">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="index.php?<?= $baseQueryString ?>&p=<?= $page - 1 ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                  <a class="page-link" href="index.php?<?= $baseQueryString ?>&p=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <?php if ($page < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="index.php?<?= $baseQueryString ?>&p=<?= $page + 1 ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>