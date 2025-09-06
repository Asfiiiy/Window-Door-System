<?php
// define('ALLOW_INCLUDE', true);
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_new_calculation'])) {
    $_SESSION['calculation_started'] = true;
}
require_once 'db.php';
?>
<style>
    .toast {
        opacity: 1 !important;
    }
    .bg-success {
        background-color:rgb(141, 196, 231) !important;
    }
    .bg-danger {
        background-color: #dc3545 !important;
    }
    a.d-block.py-2.px-3.mb-2.sidebar-item {
        font-size: smaller;
    }
    .client-search-container {
        position: relative;
    }
    .client-search-input {
        padding-right: 35px;
    }
    .client-search-clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }
    .client-search-clear:hover {
        color: #dc3545;
    }
    .client-dropdown-container {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        display: none;
        position: absolute;
        width: 100%;
        z-index: 1000;
        background: white;
    }
    .client-dropdown-container.show {
        display: block;
    }
    .client-dropdown-item {
        padding: 8px 16px;
        cursor: pointer;
    }
    .client-dropdown-item:hover {
        background-color: #f8f9fa;
    }
</style>

<div class="container py-4">
  <h3 class="mb-4"><i class="fas fa-calculator text-primary me-2"></i>New Calculation</h3>

  <?php if (isset($_SESSION['calculation_started'])): ?>
    <div class="alert alert-success text-center">
      New order session started. You may proceed with selections.
    </div>
  <?php endif; ?>

  <!-- Heading and Buttons -->
  <div class="row g-3 mb-4 align-items-end">
    <!-- New Order Button -->
    <div class="col-md-2">
      <form method="post">
        <button type="submit" name="start_new_calculation" class="btn btn-primary w-100">
          <i class="fas fa-plus"></i> New Order
        </button>
      </form>
    </div>

  <!-- Horizontal Selection -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label">Company</label>
      <select class="form-select" id="companySelect" <?= isset($_SESSION['calculation_started']) ? '' : 'disabled' ?>>
        <option value="">-- Select Company --</option>
        <?php 
        $companies = $conn->query("SELECT id, name FROM companies ORDER BY name");
        while ($company = $companies->fetch_assoc()): ?>
          <option value="<?= $company['id'] ?>"><?= htmlspecialchars($company['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Product Type</label>
      <select class="form-select" id="productTypeSelect" disabled>
        <option value="">-- Select --</option>
        <option value="window">Window</option>
        <option value="door">Door</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Type</label>
      <select class="form-select" id="productSubTypeSelect" disabled>
        <option value="">-- Select --</option>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Client</label>
      <div class="client-search-container">
        <input type="text" class="form-control client-search-input" id="clientSearchInput" placeholder="Search clients..." disabled>
        <i class="fas fa-times client-search-clear" id="clientSearchClear" style="display: none;"></i>
        <select class="form-select" id="clientSelect" style="display: none;">
          <option value="">-- Select Client --</option>
        </select>
        <div class="client-dropdown-container" id="clientDropdown">
          <!-- Client options will be populated here -->
        </div>
      </div>
    </div>

    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#addClientModal">
        <i class="fas fa-plus me-1"></i> New Client
      </button>
    </div>
  </div>

  <!-- Calculator Result -->
  <div id="calculatorDisplayArea" class="mt-4">
    <div class="alert alert-info">
      Please select company, product type, and client to begin calculation.
    </div>
  </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addClientForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modalCompanyId" name="company_id">
        <div class="mb-3">
          <label class="form-label">Client Name*</label>
          <input type="text" class="form-control" name="name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone Number</label>
          <input type="tel" class="form-control" name="phone">
        </div>
        <div class="mb-3">
          <label class="form-label">Address</label>
          <textarea class="form-control" name="address" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save Client</button>
      </div>
    </form>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
  const productSubTypes = {
    window: ['2psl', '3psl', 'fixed', 'top_hung'],
    door: ['full_panel', 'half', 'openable', 'glass']
  };

  let allClients = []; // Store all clients for search functionality

  function formatName(str) {
    return str.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
  }

  // Client search functionality
  function setupClientSearch() {
    const $searchInput = $('#clientSearchInput');
    const $searchClear = $('#clientSearchClear');
    const $clientDropdown = $('#clientDropdown');
    const $clientSelect = $('#clientSelect');

    $searchInput.on('focus', function() {
      if ($clientSelect.find('option').length > 1) {
        $clientDropdown.addClass('show');
      }
    });

    $searchInput.on('input', function() {
      const searchTerm = $(this).val().toLowerCase();
      $searchClear.toggle(searchTerm.length > 0);
      
    if (searchTerm.length > 0) {
    const filteredClients = allClients.filter(client => 
        client.name.toLowerCase().includes(searchTerm))
        .slice(0, 10); // Limit to 10 results
        
    renderClientDropdown(filteredClients);
    $clientDropdown.addClass('show');
} else {
    renderClientDropdown(allClients.slice(0, 10)); // Show first 10 clients when empty
    $clientDropdown.addClass('show');
}
    });

    $searchClear.on('click', function() {
      $searchInput.val('').focus();
      $searchClear.hide();
      renderClientDropdown(allClients.slice(0, 10));
    });

    $(document).on('click', function(e) {
      if (!$(e.target).closest('.client-search-container').length) {
        $clientDropdown.removeClass('show');
      }
    });

    function renderClientDropdown(clients) {
      $clientDropdown.empty();
      if (clients.length > 0) {
        clients.forEach(client => {
          $clientDropdown.append(
            `<div class="client-dropdown-item" data-id="${client.id}">${client.name}</div>`
          );
        });
      } else {
        $clientDropdown.append(
          '<div class="client-dropdown-item text-muted">No clients found</div>'
        );
      }
    }

    // Handle client selection from dropdown
    $clientDropdown.on('click', '.client-dropdown-item', function() {
      const clientId = $(this).data('id');
      if (clientId) {
        const selectedClient = allClients.find(c => c.id == clientId);
        $searchInput.val(selectedClient.name);
        $clientSelect.val(clientId).trigger('change');
        $clientDropdown.removeClass('show');
        $searchClear.show();
      }
    });
  }

  $('#companySelect').change(function () {
    const companyId = $(this).val();
    if (companyId) {
      $.post('set_selected_company.php', { company_id: companyId });
      $('#productTypeSelect').prop('disabled', false);
      
      $.get('ajax_get_clients.php?company_id=' + companyId, function (clients) {
        const $clientSelect = $('#clientSelect');
        $clientSelect.empty().append('<option value="">-- Select Client --</option>');
        
        allClients = clients; // Store all clients for search
        if (clients.length > 0) {
          clients.forEach(client => {
            $clientSelect.append(`<option value="${client.id}">${client.name}</option>`);
          });
          $('#clientSearchInput').prop('disabled', false);
        } else {
          $clientSelect.append('<option value="">No clients found</option>');
          $('#clientSearchInput').prop('disabled', true);
        }
        
        $('#modalCompanyId').val(companyId);
      }, 'json');
    } else {
      $('#productTypeSelect, #productSubTypeSelect').val('').prop('disabled', true);
      $('#clientSearchInput').val('').prop('disabled', true);
      $('#clientSelect').val('');
      $('#calculatorDisplayArea').html('<div class="alert alert-info">Please select company, product type, and client to begin calculation.</div>');
    }
  });

  $('#productTypeSelect').change(function () {
    const type = $(this).val();
    const $subType = $('#productSubTypeSelect');
    $subType.empty().append('<option value="">-- Select --</option>');
    if (type && productSubTypes[type]) {
      productSubTypes[type].forEach(t => {
        $subType.append(`<option value="${t}">${formatName(t)}</option>`);
      });
      $subType.prop('disabled', false);
    } else {
      $subType.prop('disabled', true);
    }
  });

  $('#productSubTypeSelect, #clientSelect').change(function () {
    const companyId = $('#companySelect').val();
    const productType = $('#productTypeSelect').val();
    const subType = $('#productSubTypeSelect').val();
    const clientId = $('#clientSelect').val();

    if (companyId && productType && subType && clientId) {
      $('#calculatorDisplayArea').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading calculator...</div>');
      $.get(`ajax_get_calculator.php?company_id=${companyId}&product_type=${productType}&sub_type=${subType}&client_id=${clientId}`, 
        function (calculatorHtml) {
          try {
            $('#calculatorDisplayArea').html(calculatorHtml);
          } catch (e) {
            console.error("Error injecting calculatorHtml:", e);
            $('#calculatorDisplayArea').html('<div class="alert alert-danger">There was a problem rendering the calculator.</div>');
          }
        }
      ).fail(function () {
        $('#calculatorDisplayArea').html('<div class="alert alert-danger">Error loading calculator. Please try again.</div>');
      });

      sessionStorage.setItem('selected_client_id', clientId);
    }
  });

  $('#addClientForm').submit(function (e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"]');
    const originalBtnText = $submitBtn.html();
    
    $submitBtn.prop('disabled', true)
              .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

    $.ajax({
      url: 'ajax_add_client.php',
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json'
    })
    .done(function(response) {
      if (response.success) {
        showToast('Client added successfully!', 'success');
        const companyId = $('#companySelect').val();
        
        $.get('ajax_get_clients.php?company_id=' + companyId, function(clients) {
          const $clientSelect = $('#clientSelect');
          $clientSelect.empty().append('<option value="">-- Select Client --</option>');
          
          allClients = clients;
          if (clients.length > 0) {
            clients.forEach(client => {
              $clientSelect.append(`<option value="${client.id}">${client.name}</option>`);
            });
            $clientSelect.val(response.clientId).trigger('change');
            $('#clientSearchInput').val(clients.find(c => c.id == response.clientId).name);
          }
          
          $('#addClientModal').modal('hide');
          $form[0].reset();
        }, 'json');
      } else {
        showToast(response.message || 'Error adding client', 'danger');
      }
    })
    .fail(function(xhr, status, error) {
      showToast('Server error: ' + error, 'danger');
      console.error('Error:', error);
    })
    .always(function() {
      $submitBtn.prop('disabled', false).html(originalBtnText);
    });
  });

  function showToast(message, type = 'success') {
    const toast = $(`
      <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      </div>
    `);

    $('body').append(toast);
    const bsToast = new bootstrap.Toast(toast.find('.toast')[0]);
    bsToast.show();

    setTimeout(() => {
      toast.fadeOut(500, () => toast.remove());
    }, 3000);
  }

  // Initialize client search functionality
  setupClientSearch();
});
</script>
<script>
  setTimeout(() => {
    document.querySelector('.alert-success')?.remove();
  }, 3000);
</script>

<?php ob_end_flush(); ?>