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