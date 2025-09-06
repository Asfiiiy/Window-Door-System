<?php 
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
$page = $_GET['page'] ?? 'welcome';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Modern Furniture Admin</title>

  <!-- Load jQuery FIRST in head -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Then load Bootstrap CSS/JS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #3498db;
      --secondary: #2980b9;
      --accent: #2c3e50;
      --light: #f8f9fa;
      --dark: #343a40;
    }
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f5f8fa;
    }
    .app-header {
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 15px 20px;
    }
    .sidebar {
      background: white;
      min-height: 100vh;
      box-shadow: 2px 0 10px rgba(0,0,0,0.05);
      position: fixed;
      width: 16.666667%;
    }
    .sidebar-item {
      color: var(--dark);
      border-left: 3px solid transparent;
      transition: all 0.3s;
    }
    .sidebar-item:hover, .sidebar-item.active {
      background: #e9f5ff;
      border-left: 3px solid var(--primary);
      color: var(--primary);
    }
    .main-content {
      background: white;
      min-height: 100vh;
      margin-left: 16.666667%;
      width: 83.333333%;
    }
    a.d-block.py-2.px-3.mb-2.sidebar-item {
      font-size: smaller;
    }
    .stat-card {
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .text-rupee {
      font-family: Arial, sans-serif;
      font-weight: bold;
    }
  </style>
</head>
<body>
<header class="app-header d-flex justify-content-between align-items-center">
  <div class="d-flex align-items-center">
    <img src="./logo/mod.jpg" alt="Logo" height="40" class="me-3">
  </div>
  <div class="text-center">
    <span class="fs-4 fw-bold text-accent">Modern <span class="text-primary">Decore</span></span>
  </div>
  <div>
    <a href="?page=logout" class="btn btn-outline-secondary">
      <i class="fas fa-sign-out-alt me-1"></i> Logout
    </a>
  </div>
</header>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-2 p-0 sidebar">
      <div class="p-3 sticky-top">
        <a href="?page=welcome" class="d-block py-2 px-3 mb-2 sidebar-item <?= $page === 'welcome' ? 'active' : '' ?>">
          <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="?page=new_calculation" class="d-block py-2 px-3 mb-2 sidebar-item <?= $page === 'new_calculation' ? 'active' : '' ?>">
          <i class="fas fa-calculator me-2"></i> New Calculation
        </a>
          <a href="?page=quotation" class="d-block py-2 px-3 mb-2 sidebar-item <?= $page === 'quotation' ? 'active' : '' ?>">
            <i class="fas fa-eye me-2"></i> View Quotation
          </a>
        
        <div class="dropdown">
          <a class="d-block py-2 px-3 mb-2 sidebar-item dropdown-toggle <?= strpos($page, 'reports_') === 0 ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-file-invoice me-2"></i> Reports
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?= $page === 'reports_invoices' ? 'active' : '' ?>" href="?page=reports_invoices">Invoices Report</a></li>
            <li><a class="dropdown-item <?= $page === 'reports_expenses' ? 'active' : '' ?>" href="?page=reports_expenses">Expenses Report</a></li>
            <li><a class="dropdown-item <?= $page === 'reports_clients' ? 'active' : '' ?>" href="?page=reports_clients">Clients Report</a></li>
            <li><a class="dropdown-item <?= $page === 'reports_materials' ? 'active' : '' ?>" href="?page=reports_materials">Materials Report</a></li>
            <li><a class="dropdown-item <?= $page === 'reports_hardware' ? 'active' : '' ?>" href="?page=reports_hardware">Hardware Report</a></li>
            <li><a class="dropdown-item <?= $page === 'report_quotation' ? 'active' : '' ?>" href="?page=report_quotation">Quotation Report</a></li>
          </ul>
        </div>
        <div class="dropdown">
          <a class="d-block py-2 px-3 sidebar-item dropdown-toggle <?= strpos($page, 'settings_') === 0 ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-cogs me-2"></i> Settings
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?= $page === 'settings_materials' ? 'active' : '' ?>" href="?page=settings_materials">Add Materials</a></li>
            <li><a class="dropdown-item <?= $page === 'settings_hardware' ? 'active' : '' ?>" href="?page=settings_hardware">Add Hardware</a></li>
            <li><a class="dropdown-item <?= $page === 'add_expense' ? 'active' : '' ?>" href="?page=add_expense">Add Expense</a></li>
            <li><a class="dropdown-item <?= $page === 'settings_companies' ? 'active' : '' ?>" href="?page=settings_companies">Companies</a></li>
            <li><a class="dropdown-item <?= $page === 'settings_add_company' ? 'active' : '' ?>" href="?page=settings_add_company">Add Company</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-10 p-0 main-content">
      <?php if ($page === 'welcome'): ?>
        <div class="p-4">
          <h3><i class="fas fa-tachometer-alt text-primary me-2"></i>Dashboard Overview</h3>
          <div class="row mt-4">
            <!-- Quotations Card -->
            <div class="col-md-3 mb-4">
              <div class="card border-primary stat-card">
                <div class="card-body text-center">
                  <i class="fas fa-file-invoice fa-3x text-primary mb-3"></i>
                  <h5 class="card-title">Total Quotations</h5>
                  <h2 class="mb-0" id="quotationCount">
                    <div class="spinner-border text-primary spinner-border-sm" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </h2>
                  <small class="text-muted">Updated just now</small>
                </div>
              </div>
            </div>
            
            <!-- Companies Card -->
            <div class="col-md-3 mb-4">
              <div class="card border-success stat-card">
                <div class="card-body text-center">
                  <i class="fas fa-building fa-3x text-success mb-3"></i>
                  <h5 class="card-title">Companies</h5>
                  <h2 class="mb-0" id="companyCount">
                    <div class="spinner-border text-success spinner-border-sm" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </h2>
                  <small class="text-muted">Updated just now</small>
                </div>
              </div>
            </div>
            
            <!-- Expenses Card -->
            <div class="col-md-3 mb-4">
              <div class="card border-danger stat-card">
                <div class="card-body text-center">
                  <i class="fas fa-money-bill-wave fa-3x text-danger mb-3"></i>
                  <h5 class="card-title">Total Expenses</h5>
                  <h2 class="mb-0" id="expenseTotal">
                    <div class="spinner-border text-danger spinner-border-sm" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </h2>
                  <small class="text-muted">Updated just now</small>
                </div>
              </div>
            </div>
            
            <!-- Clients Card -->
            <div class="col-md-3 mb-4">
              <div class="card border-warning stat-card">
                <div class="card-body text-center">
                  <i class="fas fa-users fa-3x text-warning mb-3"></i>
                  <h5 class="card-title">Active Clients</h5>
                  <h2 class="mb-0" id="clientCount">
                    <div class="spinner-border text-warning spinner-border-sm" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </h2>
                  <small class="text-muted">Updated just now</small>
                </div>
              </div>
            </div>
          </div>
        </div>

      <?php elseif ($page === 'new_calculation'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'new_calculation.php';
          ?>
        </div>

      <?php elseif ($page === 'quotation'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'Pages/quotations.php';
          ?>
        </div>
        
      <?php elseif ($page === 'report_quotation'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'Pages/view_quotation.php';
          ?>
        </div>
        
      <?php elseif ($page === 'reports_invoices'): ?>
        <div class="p-4">
          <h4><i class="fas fa-file-invoice me-2"></i>Reports</h4>
          <?php
          define('ALLOW_INCLUDE', true);
          include 'report_invoices.php';
          ?>
        </div>
        
      <?php elseif ($page === 'reports_clients'): ?>
        <div class="p-4">
          <h4><i class="fas fa-file-invoice me-2"></i>Client Reports</h4>
          <?php
          define('ALLOW_INCLUDE', true);
          include 'client_report.php';
          ?>
        </div>
        
      <?php elseif ($page === 'reports_worker'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'worker_report.php';
          ?>
        </div>
        
      <?php elseif ($page === 'reports_views'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'eview.php';
          ?>
        </div>
        
      <?php elseif ($page === 'add_expense'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'add_expense.php';
          ?>
        </div>
        
      <?php elseif ($page === 'reports_expenses'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'reports_expenses.php';
          ?>
        </div>

        <?php elseif ($page === 'reports_materials'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'reports_materials.php';
          ?>
        </div>

        <?php elseif ($page === 'reports_hardware'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'reports_hardware.php';
          ?>
        </div>

        <?php elseif ($page === 'settings_materials'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'Pages/settings_materials.php';
          ?>
        </div>

        <?php elseif ($page === 'settings_hardware'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'Pages/settings_hardware.php';
          ?>
        </div>

        <?php elseif ($page === 'settings_companies'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'Pages/settings_companies.php';
          ?>
        </div>

        <?php elseif ($page === 'settings_add_company'): ?>
        <div class="p-4">
          <?php
          define('ALLOW_INCLUDE', true);
          include 'Pages/settings_add_company.php';
          ?>
        </div>
        
      <?php elseif ($page === 'logout'): ?>
        <?php
          session_destroy();
          header("Location: login.php");
          exit();
        ?>
        
      <?php else: ?>
        <div class="p-4">
          <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Select an option from the sidebar to get started.
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Global scripts that should run on every page
jQuery(document).ready(function($) {
  // Initialize all Bootstrap dropdowns
  $('.dropdown-toggle').dropdown();
  
  // Highlight active dropdown parent when child is active
  $('.dropdown-item.active').each(function() {
    $(this).closest('.dropdown').find('.dropdown-toggle').addClass('active');
  });

  // Dashboard-specific scripts
  if (window.location.search.includes('page=welcome')) {
    loadDashboardStats();
    setInterval(loadDashboardStats, 60000);
  }
  
  // Add active class to dropdown items
  $('.dropdown-item').each(function() {
    if (window.location.search.includes($(this).attr('href').split('=')[1])) {
      $(this).addClass('active');
    }
  });

  function loadDashboardStats() {
    $('.stat-card h2').html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>');
    
    $.ajax({
      url: 'ajax_get_summary.php',
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if (data.status === 'success') {
          $('#quotationCount').text(data.quotations);
          $('#companyCount').text(data.companies);
          $('#expenseTotal').html('<span class="text-rupee">Rs</span>' + data.expenses_total);
          $('#clientCount').text(data.clients);
          
          const now = new Date();
          $('.stat-card small').text('Updated at ' + now.toLocaleTimeString());
        } else {
          showDashboardError(data.message || 'Unknown error occurred');
        }
      },
      error: function() {
        showDashboardError('Failed to connect to server');
      }
    });
  }

  function showDashboardError(message) {
    $('.stat-card h2').html('<span class="text-danger">Error</span>');
    $('.stat-card small').text(message);
  }
});
</script>
</body>
</html>

<?php ob_end_flush(); ?>