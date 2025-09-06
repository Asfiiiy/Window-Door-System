<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../quotation_handler.php');

// Debug output
echo "<!-- Session: " . print_r($_SESSION, true) . " -->";
echo "<!-- GET: " . print_r($_GET, true) . " -->";

// Check if required parameters are passed via GET
$required_params = ['company_id', 'product_type', 'sub_type', 'client_id'];
foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        die('<div class="alert alert-danger">Missing required parameter: ' . $param . '</div>');
    }
}

$company_id = (int)$_GET['company_id'];
$product_type = $_GET['product_type'];
$sub_type = $_GET['sub_type'];
$client_id = (int)$_GET['client_id'];

// Set client ID in JavaScript scope
echo "<script>window.currentClientId = $client_id; window.currentCompanyId = $company_id;</script>";

// Database connection
try {
    $conn = new mysqli("localhost", "u742242489_decore", "F9MGmm6:", "u742242489_decore");
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Verify client belongs to selected company
    $stmt = $conn->prepare("SELECT id, name FROM clients WHERE id = ? AND company_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $client_id, $company_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
    $stmt->close();

    if (!$client) {
        throw new Exception("Client not found or doesn't belong to selected company");
    }

    // Fetch all prices
    $prices = ['materials' => [], 'hardware' => [], 'additional' => []];

    // Fetch material prices
    $stmt = $conn->prepare("SELECT name, price_per_foot FROM materials WHERE company_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $company_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $prices['materials'][$row['name']] = $row['price_per_foot'];
                $prices['additional'][$row['name']] = $row['price_per_foot'];
            }
        }
        $stmt->close();
    }

    // Fetch hardware prices
    $stmt = $conn->prepare("SELECT name, price FROM hardware WHERE company_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $company_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $prices['hardware'][$row['name']] = $row['price'];
            }
        }
        $stmt->close();
    }

    // Default glass price
    $glass_price_per_sqft = 200;
    $conn->close();

} catch (Exception $e) {
    die('<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>UPVC Door Material Calculator</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
    }
    .calculator-container {
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: all 0.3s ease;
    }
    .calculator-container:hover {
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
      transform: translateY(-5px);
    }
    .calculator-header {
      background: linear-gradient(to right, #4b6cb7, #182848);
      color: white;
      padding: 20px;
      margin-bottom: 20px;
    }
    .form-control, .form-select {
      border-radius: 8px;
      padding: 12px 15px;
      border: 1px solid #ddd;
      transition: all 0.3s;
    }
    .form-control:focus, .form-select:focus {
      border-color: #4b6cb7;
      box-shadow: 0 0 0 0.25rem rgba(75, 108, 183, 0.25);
    }
    .btn-calculate {
      background: linear-gradient(to right, #4b6cb7, #182848);
      border: none;
      padding: 12px 30px;
      font-weight: 600;
      letter-spacing: 1px;
      transition: all 0.3s;
      color: white;
    }
    .btn-calculate:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      background-color: #182848;
      color: white;
    }
    .results-container {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      margin-top: 20px;
      border-left: 5px solid #4b6cb7;
      animation: fadeIn 0.5s ease-out;
    }
    .result-item {
      padding: 8px 0;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
    }
    .result-item:last-child {
      border-bottom: none;
    }
    .result-label {
      font-weight: 600;
      color: #182848;
    }
    .result-value {
      text-align: right;
    }
    .price-value {
      color: #28a745;
      font-weight: bold;
    }
    .result-note {
      font-size: 0.8rem;
      color: #666;
      font-style: italic;
    }
    .sketch-container {
      margin-top: 30px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 10px;
      animation: fadeInUp 0.6s ease-out;
      position: relative;
      text-align: center;
    }
    .door-image {
      max-width: 100%;
      height: auto;
      border: 1px solid #ddd;
      border-radius: 8px;
    }
    .dimension-label {
      position: absolute;
      font-weight: bold;
      color: #182848;
      background: rgba(255,255,255,0.8);
      padding: 2px 5px;
      border-radius: 3px;
    }
    .width-label {
      top: -10px;
      left: 50%;
      transform: translateX(-50%);
    }
    .height-label {
      right: 10px;
      top: 50%;
      transform: translateY(-50%) rotate(-90deg);
      transform-origin: right center;
    }
    .section-title {
      color: #4b6cb7;
      border-bottom: 2px solid #4b6cb7;
      padding-bottom: 5px;
      margin-top: 20px;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .pulse {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    .no-length {
      color: #999;
    }
    .input-group-unit {
      width: 100px;
    }
    .alert-quotation {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      min-width: 300px;
    }
    .quotation-buttons {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="calculator-container animate__animated animate__fadeIn">
          <div class="calculator-header text-center">
            <h2 class="animate__animated animate__fadeInDown">UPVC Door Material Calculator</h2>
            <p class="mb-0 animate__animated animate__fadeInUp animate__delay-1s">Calculate materials for UPVC doors</p>
          </div>
          
          <div class="p-4">
            <div class="mb-3 animate__animated animate__fadeIn animate__delay-1s">
              <label for="height" class="form-label">Height</label>
              <div class="input-group">
                <input type="number" class="form-control" id="height" placeholder="Enter height" step="0.01" min="0.01">
                <select class="form-select input-group-unit" id="heightUnit">
                  <option value="ft">feet</option>
                  <option value="in">inches</option>
                  <option value="cm">centimeters</option>
                  <option value="mm">millimeters</option>
                </select>
              </div>
            </div>
            
            <div class="mb-3 animate__animated animate__fadeIn animate__delay-1s">
              <label for="width" class="form-label">Width</label>
              <div class="input-group">
                <input type="number" class="form-control" id="width" placeholder="Enter width" step="0.01" min="0.01">
                <select class="form-select input-group-unit" id="widthUnit">
                  <option value="ft">feet</option>
                  <option value="in">inches</option>
                  <option value="cm">centimeters</option>
                  <option value="mm">millimeters</option>
                </select>
              </div>
            </div>
            
            <div class="mb-3 animate__animated animate__fadeIn animate__delay-1s">
              <label for="quantity" class="form-label">Quantity</label>
              <input type="number" class="form-control" id="quantity" placeholder="Enter quantity" min="1" value="1">
            </div>
            
            <div class="text-center mt-4 animate__animated animate__fadeIn animate__delay-2s">
              <button id="calculateBtn" class="btn btn-calculate btn-lg pulse">
                <i class="fas fa-calculator me-2"></i>Calculate Materials & Costs
              </button>
            </div>
            
            <div class="results-container mt-4" id="output" style="display: none;"></div>
            
            <div class="sketch-container" id="sketch" style="display: none;">
              <img src="Pages/image/fulldoor.jpg" alt="UPVC Door Diagram" class="door-image">
              <div class="dimension-label width-label" id="widthLabel"></div>
              <div class="dimension-label height-label" id="heightLabel"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Database prices from PHP
    const prices = <?php echo json_encode($prices); ?>;
    const glassPricePerSqft = <?php echo $glass_price_per_sqft; ?>;
    
    // Utility functions
    function convertToFeet(value, unit) {
      const conversions = {
        'in': 12,
        'cm': 30.48,
        'mm': 304.8,
        'ft': 1
      };
      return value / (conversions[unit] || 1);
    }
    
    function convertToInches(value, unit) {
      switch(unit) {
        case 'ft': return value * 12;
        case 'cm': return value / 2.54;
        case 'mm': return value / 25.4;
        default: return value;
      }
    }
    
    function formatCurrency(amount) {
      return 'Rs. ' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    function formatUnit(value, unit) {
      switch(unit) {
        case 'in': return `${value} in`;
        case 'cm': return `${value} cm`;
        case 'mm': return `${value} mm`;
        default: return `${value} ft`;
      }
    }
    
    function showError(message) {
      const output = document.getElementById("output");
      output.innerHTML = `
        <div class="alert alert-danger" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>${message}
        </div>
      `;
      output.style.display = "block";
    }
    
    function createResultRow(label, value, unit, amount, isTotal = false, extraClass = '') {
      return `
        <div class="result-item ${extraClass}">
          <span class="result-label">${label}${value ? ` (${value} ${unit})` : ''}</span>
          <span class="result-value ${isTotal ? 'price-value' : ''}">${amount ? formatCurrency(amount) : ''}</span>
        </div>
      `;
    }

    function generateDoorSketch(height, heightUnit, width, widthUnit) {
      // Update door image with dimensions
      document.getElementById("widthLabel").textContent = `${width} ${widthUnit}`;
      document.getElementById("heightLabel").textContent = `${height} ${heightUnit}`;
      document.getElementById("sketch").style.display = "block";
    }
    
    function calculateDoor() {
      // Get input values
      const heightValue = parseFloat(document.getElementById("height").value);
      const heightUnit = document.getElementById("heightUnit").value;
      const widthValue = parseFloat(document.getElementById("width").value);
      const widthUnit = document.getElementById("widthUnit").value;
      const quantity = parseInt(document.getElementById("quantity").value);
      
      // Validate inputs
      if (isNaN(heightValue)) return showError("Please enter a valid height");
      if (isNaN(widthValue)) return showError("Please enter a valid width");
      if (isNaN(quantity) || quantity < 1) return showError("Please enter a valid quantity (minimum 1)");
      
      // Convert to feet and inches for calculations
      const heightFt = convertToFeet(heightValue, heightUnit);
      const widthFt = convertToFeet(widthValue, widthUnit);
      const heightIn = convertToInches(heightValue, heightUnit);
      
      if (heightFt <= 0 || widthFt <= 0) return showError("Height and width must be positive values");
      
      // Calculate perimeter
      const perimeter = (heightFt * 2) + (widthFt * 2);
      
      // Area and Glass calculations
      const area = heightFt * widthFt;
      const totalArea = area * quantity;
      const glass = totalArea / 2;
      
      // Main calculations
      const frameLength = ((heightFt * 2) + widthFt) * quantity;
      const doorSashLength = ((widthFt * 2) + (heightFt * 2)) * quantity;
      const beadingLength = doorSashLength * quantity;
      
      // Panel calculations
      let doorPanel;
      if (heightUnit === 'in') {
        doorPanel = (heightValue / 4) * widthFt;
      } else {
        doorPanel = (heightIn / 4) * widthFt;
      }
      doorPanel = doorPanel * quantity;
      
      // Steel calculation
      const steel = ((frameLength + doorSashLength) / 8);
      
      // Hardware calculations
      const hardwareItems = {
        'Fitting Screw': 15 * quantity,
        'Rawal Plug': 15 * quantity,
        'Silicon White': 2 * quantity,
        'Self Screw': 40 * quantity,
        'Sada Screw': 30 * quantity,
        'Hinges': 4 * quantity,
        'Locks': 1 * quantity
      };
      
      // Calculate costs
      const calculateCost = (length, material) => length * (prices.materials[material] || 0);
      
      const frameCost = calculateCost(frameLength, 'Frame');
      const doorSashCost = calculateCost(doorSashLength, 'Door Sash');
      const beadingCost = calculateCost(beadingLength, 'Beading');
      const steelCost = steel * (prices.additional['Steel'] || 0);
      
      const doorPanelCost = calculateCost(doorPanel, 'Door Panel');
      const glassCost = glass * glassPricePerSqft;
      
      // Calculate hardware costs
      let totalHardwareCost = 0;
      const hardwareCosts = {};
      
      Object.entries(hardwareItems).forEach(([name, qty]) => {
        const cost = qty * (prices.hardware[name] || 0);
        hardwareCosts[name] = cost;
        totalHardwareCost += cost;
      });
      
      // Calculate totals
      const totalMaterialCost = frameCost + doorSashCost + beadingCost + doorPanelCost + steelCost;
      const grandTotal = totalMaterialCost + totalHardwareCost + glassCost;
      
      // Generate output HTML
      const outputHTML = `
        <h5 class="mb-3"><i class="fas fa-calculator me-2"></i>Calculation Results</h5>
        
        <div class="result-item">
          <span class="result-label">Quantity:</span>
          <span class="result-value">${quantity}</span>
        </div>
        <div class="result-item">
          <span class="result-label">Height:</span>
          <span class="result-value">${heightValue} ${heightUnit} (${heightFt.toFixed(2)} ft)</span>
        </div>
        <div class="result-item">
          <span class="result-label">Width:</span>
          <span class="result-value">${widthValue} ${widthUnit} (${widthFt.toFixed(2)} ft)</span>
        </div>
        <div class="result-item">
          <span class="result-label">Total Area:</span>
          <span class="result-value">${totalArea.toFixed(2)} sft</span>
        </div>
        
        <h6 class="section-title"><i class="fas fa-ruler-combined me-2"></i>Frame Materials</h6>
        ${createResultRow('60mm Frame Length', (frameLength/19).toFixed(2), 'lengths', frameCost)}
        ${createResultRow('Door Sash Length', (doorSashLength/19).toFixed(2), 'lengths', doorSashCost)}
        ${createResultRow('Beading Length', (beadingLength/19).toFixed(2), 'lengths', beadingCost)}
        ${createResultRow('Door Panel', (doorPanel/19).toFixed(2), 'lengths', doorPanelCost)}
        ${createResultRow('Steel', steel.toFixed(2), 'kg', steelCost)}
        ${createResultRow('Glass', glass.toFixed(2), 'sft', glassCost)}
        
        <h6 class="section-title"><i class="fas fa-tools me-2"></i>Hardware Items</h6>
        ${Object.entries(hardwareItems).map(([name, qty]) => 
          createResultRow(name, qty, 'pcs', hardwareCosts[name])).join('')}
        
        <div class="result-total mt-4 p-3 bg-light rounded">
          ${createResultRow('Total Materials Cost', '', '', totalMaterialCost, true)}
          ${createResultRow('Total Hardware Cost', '', '', totalHardwareCost, true)}
          ${createResultRow('Glass Cost', '', '', glassCost, true)}
          ${createResultRow('Grand Total', '', '', grandTotal, true, 'fs-5')}
        </div>
      `;
      
      // Display results
      function prepareFullCalculation(heightFt, widthFt, quantity) {
        return {
          dimensions: {
            height: heightFt,
            width: widthFt,
            quantity: quantity,
            area: heightFt * widthFt * quantity
          },
          materials: {
            frame: { length: (frameLength/19).toFixed(2), cost: frameCost },
            sash: { length: (doorSashLength/19).toFixed(2), cost: doorSashCost },
            beading: { length: (beadingLength/19).toFixed(2), cost: beadingCost },
            panel: { length: (doorPanel/19).toFixed(2), cost: doorPanelCost },
            steel: { quantity: steel.toFixed(2), cost: steelCost },
            glass: { area: glass.toFixed(2), cost: glassCost }
          },
          hardware: {
            items: hardwareItems,
            costs: hardwareCosts,
            total: totalHardwareCost
          },
          totals: {
            materials: totalMaterialCost,
            hardware: totalHardwareCost,
            glass: glassCost,
            grandTotal: grandTotal
          }
        };
      }

      const output = document.getElementById("output");
      output.innerHTML = outputHTML;
      output.style.display = "block";

      const quoteBtnContainer = document.createElement('div');
      quoteBtnContainer.className = 'quotation-buttons';

      const getCalculationData = () => {
        return {
          area: totalArea,
          quantity,
          totalCost: grandTotal,
          height: heightFt,
          width: widthFt,
          unit: 'ft',
          _source: 'door_calculator',
          original: {
            height: heightValue,
            width: widthValue,
            unit: heightUnit
          },
          fullData: prepareFullCalculation(heightFt, widthFt, quantity)
        };
      };

      const addButton = document.createElement('button');
      addButton.className = 'btn btn-success';
      addButton.id = 'addToQuotationBtn';
      addButton.innerHTML = `<i class="fas fa-plus"></i> Add to Quotation`;

      function showToast(message, type = 'info') {
        alert(`${type.toUpperCase()}: ${message}`);
      }

      addButton.addEventListener('click', function() {
        const calcData = getCalculationData();
        const fullData = calcData.fullData;

        const quoteFormData = new FormData();
        quoteFormData.append('action', 'add_item');
        quoteFormData.append('window_type', 'Door');
        quoteFormData.append('description', 'UPVC Door');
        quoteFormData.append('area', calcData.area);
        quoteFormData.append('rate', calcData.totalCost / calcData.area);
        quoteFormData.append('amount', calcData.totalCost);
        quoteFormData.append('quantity', calcData.quantity);
        quoteFormData.append('height', calcData.height);
        quoteFormData.append('width', calcData.width);
        quoteFormData.append('client_id', window.currentClientId);
        quoteFormData.append('calculation_data', JSON.stringify(fullData));

        fetch('quotation_handler.php', {
          method: 'POST',
          body: quoteFormData
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('HTTP error: ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            showToast('Added to quotation!', 'success');
          } else {
            showToast('Error: ' + (data.error || 'Failed to add'), 'danger');
          }
        });

        const saveFormData = new FormData();
        saveFormData.append('action', 'save_calculation');
        saveFormData.append('client_id', window.currentClientId);
        saveFormData.append('company_id', window.currentCompanyId);
        saveFormData.append('window_type', 'Door');
        saveFormData.append('height', fullData.dimensions.height);
        saveFormData.append('width', fullData.dimensions.width);
        saveFormData.append('quantity', fullData.dimensions.quantity);
        saveFormData.append('total_area', fullData.dimensions.area);
        saveFormData.append('frame_length', fullData.materials.frame.length);
        saveFormData.append('sash_length', fullData.materials.sash.length);
        saveFormData.append('beading_length', fullData.materials.beading.length);
        saveFormData.append('panel_length', fullData.materials.panel.length);
        saveFormData.append('steel_quantity', fullData.materials.steel.quantity);
        saveFormData.append('glass_area', fullData.materials.glass.area);
        saveFormData.append('material_cost', fullData.totals.materials);
        saveFormData.append('hardware_cost', fullData.totals.hardware);
        saveFormData.append('glass_cost', fullData.totals.glass);
        saveFormData.append('total_cost', fullData.totals.grandTotal);

        fetch('./Pages/save_window_calculation.php', {
          method: 'POST',
          body: saveFormData
        })
        .then(res => {
          const contentType = res.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            return res.text().then(text => {
              throw new Error(`Invalid response: ${text}`);
            });
          }
          return res.json();
        })
        .then(data => {
          if (data.success) {
            alert("Saved successfully");
          } else {
            alert("Save failed: " + (data.error || 'Unknown error'));
          }
        })
        .catch(error => {
          console.error("Error:", error.message);
          alert("Error: " + error.message);
        });
      });

      quoteBtnContainer.appendChild(addButton);
      output.appendChild(quoteBtnContainer);
      generateDoorSketch(heightValue, heightUnit, widthValue, widthUnit);
      output.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Event listeners
    document.getElementById('calculateBtn').addEventListener('click', calculateDoor);

    ['height', 'width', 'quantity'].forEach(id => {
      document.getElementById(id).addEventListener('keypress', function(e) {
        if (e.key === 'Enter') calculateDoor();
      });
    });
  </script>
</body>
</html>