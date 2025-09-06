<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// require_once(__DIR__ . '/../db.php');
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
echo "<script>window.currentClientId = $client_id;</script>";
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
  <meta charset="UTF-8">
  <title>Fix Window Material Calculator</title>
  <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
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
      margin: 20px 0;
      max-width: 800px;
    }
    .calculator-container:hover {
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
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
    }
    .btn-calculate:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .results-container {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      margin-top: 20px;
      border-left: 5px solid #4b6cb7;
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
    .section-title {
      color: #4b6cb7;
      border-bottom: 2px solid #4b6cb7;
      padding-bottom: 5px;
      margin-top: 20px;
    }
    .input-group-unit {
      width: 100px;
    }
    .quotation-buttons {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 20px;
    }
    .sketch-container {
      margin-top: 30px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 10px;
      text-align: center;
    }
    .alert-quotation {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 1000;
      min-width: 300px;
    }
    @media (max-width: 768px) {
      .calculator-container {
        margin: 10px;
      }
      .quotation-buttons {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="calculator-container">
          <div class="calculator-header text-center">
            <h2>Fix Window Material Calculator</h2>
            <p class="mb-0">Calculate materials and costs for fixed windows</p>
          </div>
          
          <div class="p-4">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="fixHeight" class="form-label">Height</label>
                <div class="input-group">
                  <input type="number" class="form-control" id="fixHeight" placeholder="Enter height" step="0.01" min="0.01">
                  <select class="form-select input-group-unit" id="fixHeightUnit">
                    <option value="ft">feet</option>
                    <option value="in">inches</option>
                    <option value="cm">centimeters</option>
                    <option value="mm">millimeters</option>
                  </select>
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="fixWidth" class="form-label">Width</label>
                <div class="input-group">
                  <input type="number" class="form-control" id="fixWidth" placeholder="Enter width" step="0.01" min="0.01">
                  <select class="form-select input-group-unit" id="fixWidthUnit">
                    <option value="ft">feet</option>
                    <option value="in">inches</option>
                    <option value="cm">centimeters</option>
                    <option value="mm">millimeters</option>
                  </select>
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label for="fixQuantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="fixQuantity" placeholder="Enter quantity" min="1" value="1">
              </div>
            </div>
            
            <div class="text-center mt-4">
              <button id="calculateBtn" class="btn btn-calculate btn-lg">
                <i class="fas fa-calculator me-2"></i>Calculate Materials & Costs
              </button>
            </div>
            
            <div class="results-container mt-4" id="fixOutput" style="display: none;"></div>
            
            <div class="sketch-container" id="fixSketch" style="display: none;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Database prices from PHP
    const prices = <?php echo json_encode($prices, JSON_HEX_TAG); ?>;
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
    
    function formatCurrency(amount) {
      return 'Rs. ' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
    
    function showError(message) {
      const output = document.getElementById("fixOutput");
      output.innerHTML = `
        <div class="alert alert-danger" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>${message}
        </div>
      `;
      output.style.display = "block";
    }
    
    // Main calculation function
    function calculateFixWindow() {
      // Get input values
      const heightValue = parseFloat(document.getElementById("fixHeight").value);
      const heightUnit = document.getElementById("fixHeightUnit").value;
      const widthValue = parseFloat(document.getElementById("fixWidth").value);
      const widthUnit = document.getElementById("fixWidthUnit").value;
      const quantity = parseInt(document.getElementById("fixQuantity").value);
      
      // Validate inputs
      if (isNaN(heightValue)) return showError("Please enter a valid height");
      if (isNaN(widthValue)) return showError("Please enter a valid width");
      if (isNaN(quantity) || quantity < 1) return showError("Please enter a valid quantity (minimum 1)");
      
      // Convert to feet for calculations
      const heightFt = convertToFeet(heightValue, heightUnit);
      const widthFt = convertToFeet(widthValue, widthUnit);
      
      if (heightFt <= 0 || widthFt <= 0) return showError("Height and width must be positive values");
      
      // Calculate dimensions
      const perimeter = (heightFt + widthFt) * 2;
      const area = heightFt * widthFt;
      const totalArea = area * quantity;
      
      // Material lengths
      const frameLength = perimeter * quantity;
      const beadingLength = frameLength;
      const steelLength = frameLength / 8; // Steel comes in 8ft lengths
      
      // Calculate costs
      const calculateCost = (length, material) => length * (prices.materials[material] || 0);
      
      const frameCost = calculateCost(frameLength, 'Frame');
      const beadingCost = calculateCost(beadingLength, 'Beading');
      const steelCost = steelLength * (prices.additional['Steel'] || 0);
      
      // Hardware calculations
      const selfScrew = 20 * quantity;
      const fittingScrew = 15 * quantity;
      const holeCap = 15 * quantity;
      const rawalPlug = 15 * quantity;
      
      // Calculate hardware costs
      const hardwareCosts = {
        'Self Screw': selfScrew * (prices.hardware['Self Screw'] || 0),
        'Fitting Screw': fittingScrew * (prices.hardware['Fitting Screw'] || 0),
        'Hole Cap': holeCap * (prices.hardware['Hole Caps'] || 0),
        'Rawal Plug': rawalPlug * (prices.hardware['Rawal Plug'] || 0)
      };
      
      const totalHardwareCost = Object.values(hardwareCosts).reduce((a, b) => a + b, 0);
      
      // Glass calculation
      const glassCost = totalArea * glassPricePerSqft;
      
      // Calculate totals
      const totalMaterialCost = frameCost + beadingCost + steelCost;
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
        
        <h6 class="section-title mt-4"><i class="fas fa-ruler-combined me-2"></i>Main Materials</h6>
        ${createResultRow('Frame Length', (frameLength/19).toFixed(2), '19ft lengths', frameCost)}
        ${createResultRow('Beading Length', (beadingLength/19).toFixed(2), '19ft lengths', beadingCost)}
        ${createResultRow('Steel', steelLength.toFixed(2), '8ft lengths', steelCost)}
        
        <h6 class="section-title mt-4"><i class="fas fa-tools me-2"></i>Hardware Items</h6>
        ${createResultRow('Self Screw', selfScrew, 'kg', hardwareCosts['Self Screw'])}
        ${createResultRow('Fitting Screw', fittingScrew, 'pcs', hardwareCosts['Fitting Screw'])}
        ${createResultRow('Hole Cap', holeCap, 'pcs', hardwareCosts['Hole Cap'])}
        ${createResultRow('Rawal Plug', rawalPlug, 'pcs', hardwareCosts['Rawal Plug'])}
        
        <h6 class="section-title mt-4"><i class="fas fa-window-maximize me-2"></i>Glass</h6>
        ${createResultRow('6mm Plain Glass', totalArea.toFixed(2), 'sft', glassCost)}
        
        <div class="result-total mt-4 p-3 bg-light rounded">
          ${createResultRow('Total Materials Cost', '', '', totalMaterialCost, true)}
          ${createResultRow('Total Hardware Cost', '', '', totalHardwareCost, true)}
          ${createResultRow('Glass Cost', '', '', glassCost, true)}
          ${createResultRow('Grand Total', '', '', grandTotal, true, 'fs-5')}
        </div>
      `;
      
      function createResultRow(label, value, unit, amount, isTotal = false, extraClass = '') {
        return `
          <div class="result-item ${extraClass}">
            <span class="result-label">${label}${value ? ` (${value} ${unit})` : ''}</span>
            <span class="result-value price-value">${formatCurrency(amount)}</span>
          </div>
        `;
      }
      
      // Display results
      const output = document.getElementById("fixOutput");
    output.innerHTML = outputHTML;
output.style.display = "block";

// --- Start Add to Quotation Integration ---

function prepareFullCalculation(heightFt, widthFt, area) { 
    return {
        dimensions: {
            height: heightFt,
            width: widthFt,
            quantity: quantity,
            area: area
        },
        materials: {
            frame: { length: (frameLength / 19).toFixed(2), cost: frameCost },
            beading: { length: (beadingLength / 19).toFixed(2), cost: beadingCost },
            steel: { quantity: steelLength.toFixed(2), cost: steelCost }
        },
        hardware: {
            selfScrew: { quantity: selfScrew, cost: hardwareCosts['Self Screw'] || 0 },
            fittingScrew: { quantity: fittingScrew, cost: hardwareCosts['Fitting Screw'] || 0 },
            holeCaps: { quantity: holeCap, cost: hardwareCosts['Hole Cap'] || 0 },
            rawalPlug: { quantity: rawalPlug, cost: hardwareCosts['Rawal Plug'] || 0 }
        },
        totals: {
            materials: totalMaterialCost,
            hardware: totalHardwareCost,
            glass: glassCost,
            grandTotal: grandTotal
        }
    };
}

const quoteBtnContainer = document.createElement('div');
quoteBtnContainer.className = 'quotation-buttons';

function getCalculationData() {
    const heightValue = parseFloat(document.getElementById("fixHeight").value);
    const heightUnit = document.getElementById("fixHeightUnit").value;
    const widthValue = parseFloat(document.getElementById("fixWidth").value);
    const widthUnit = document.getElementById("fixWidthUnit").value;
    const heightFt = convertToFeet(heightValue, heightUnit);
    const widthFt = convertToFeet(widthValue, widthUnit);
    const area = heightFt * widthFt * quantity;

    return {
        area,
        quantity,
        totalCost: grandTotal,
        height: heightFt,
        width: widthFt,
        unit: 'ft',
        _source: 'fix_calculator',
        original: {
            height: heightValue,
            width: widthValue,
            unit: heightUnit
        },
        fullData: prepareFullCalculation(heightFt, widthFt, area)
    };
}

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
    quoteFormData.append('window_type', 'Fix Window');
    quoteFormData.append('description', 'Fix Window');
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Added to quotation!', 'success');
        } else {
            showToast('Error: ' + (data.error || 'Failed to add'), 'danger');
        }
    });

    // Save calculation
    const saveFormData = new FormData();
    saveFormData.append('action', 'save_calculation');
    saveFormData.append('client_id', window.currentClientId);
    saveFormData.append('company_id', window.currentCompanyId);
    saveFormData.append('window_type', 'Fix Window');
    saveFormData.append('height', fullData.dimensions.height);
    saveFormData.append('width', fullData.dimensions.width);
    saveFormData.append('quantity', fullData.dimensions.quantity);
    saveFormData.append('total_area', fullData.dimensions.area);
    saveFormData.append('frame_length', fullData.materials.frame.length);
    saveFormData.append('beading_length', fullData.materials.beading.length);
    saveFormData.append('steel_quantity', fullData.materials.steel.quantity);
    saveFormData.append('self_screw', fullData.hardware.selfScrew.quantity);
    saveFormData.append('fitting_screw', fullData.hardware.fittingScrew.quantity);
    saveFormData.append('hole_caps', fullData.hardware.holeCaps.quantity);
    saveFormData.append('rawal_plug', fullData.hardware.rawalPlug.quantity);
    saveFormData.append('material_cost', fullData.totals.materials);
    saveFormData.append('hardware_cost', fullData.totals.hardware);
    saveFormData.append('glass_cost', fullData.totals.glass);
    saveFormData.append('total_cost', fullData.totals.grandTotal);

    console.log('Saving data to save_window_calculation.php...', Object.fromEntries(saveFormData));

    fetch('./Pages/save_window_calculation.php', {
        method: 'POST',
        body: saveFormData
    })
    .then(res => {
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return res.text().then(text => { throw new Error(`Invalid response: ${text}`); });
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



      
      
      // Generate window sketch
      generateWindowSketch(heightValue, heightUnit, widthValue, widthUnit);
      
      // Scroll to results
      output.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Generate window sketch SVG
    function generateWindowSketch(height, heightUnit, width, widthUnit) {
      const aspectRatio = height / width;
      const frameWidth = 300;
      const frameHeight = frameWidth * aspectRatio;
      
      const svg = `
      <svg width="${frameWidth + 60}" height="${frameHeight + 60}" style="border:1px solid #ddd; background: white; border-radius: 8px;" xmlns="http://www.w3.org/2000/svg">
        <g transform="translate(30,30)">
          <!-- Main frame -->
          <rect x="0" y="0" width="${frameWidth}" height="${frameHeight}" fill="none" stroke="#4b6cb7" stroke-width="2"/>
          
          <!-- Dimensions -->
          <text x="${frameWidth / 2 - 30}" y="-10" font-size="12" fill="#4b6cb7">${width} ${widthUnit}</text>
          <text x="-25" y="${frameHeight / 2}" font-size="12" fill="#4b6cb7" transform="rotate(-90 -10, ${frameHeight / 2})">${height} ${heightUnit}</text>
          
          <!-- Window label -->
          <text x="${frameWidth / 2}" y="${frameHeight / 2}" font-size="24" text-anchor="middle" fill="#182848" font-weight="bold">FIX</text>
        </g>
      </svg>
      `;
      
      document.getElementById("fixSketch").innerHTML = svg;
      document.getElementById("fixSketch").style.display = "block";
    }
    
    // Event listeners
    document.getElementById('calculateBtn').addEventListener('click', calculateFixWindow);


['height', 'width', 'quantity'].forEach(id => {
    document.getElementById(id).addEventListener('keypress', function(e) {
        if (e.key === 'Enter') calculate();
    });
});
    
    // Quotation functions (from your previous code)
    function saveQuotation(type, area, quantity, totalCost) {
      // Implement your quotation saving logic here
      alert(`Quotation saved for ${type} window (Area: ${area} sft, Quantity: ${quantity}, Total: Rs. ${totalCost.toFixed(2)})`);
    }
    
    function printQuotation() {
      window.print();
    }
  </script>
    <script src="quotation.js"></script>
</body>
</html>