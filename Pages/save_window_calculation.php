<?php
// Ensure no output before headers
ob_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/log.txt');
error_reporting(E_ALL);

require_once '../db.php';

// Clean any previous output
ob_clean();
header('Content-Type: application/json');

try {
    // Check action
    if (!isset($_POST['action'])) {
        throw new Exception("Invalid request: no action specified");
    }
    
    if ($_POST['action'] !== 'save_calculation') {
        throw new Exception("Invalid action");
    }

    // Required fields
    $required = [
        'client_id', 'company_id', 'window_type',
        'height', 'width', 'quantity', 'total_area',
        'material_cost', 'hardware_cost', 'glass_cost', 'total_cost'
    ];

    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $conn->begin_transaction();

    // Check client & company
    $check = $conn->prepare("SELECT 1 FROM clients WHERE id = ? AND company_id = ?");
    $check->bind_param("ii", $_POST['client_id'], $_POST['company_id']);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        throw new Exception("Client not found or doesn't belong to company");
    }
    $check->close();

    // Insert into window_calculation_details
    $stmt = $conn->prepare("
        INSERT INTO window_calculation_details (
            client_id, company_id, window_type,
            height, width, quantity, total_area,
            frame_length, sash_length, net_sash_length, beading_length, interlock_length,
            steel_quantity, net_area, net_rubber_quantity, burshi_length,
            locks, dummy, boofer, stopper, double_wheel, net_wheel,
            sada_screw, fitting_screw, self_screw, rawal_plug, silicon_white, hole_caps, water_caps,
            material_cost, hardware_cost, glass_cost, total_cost
        ) VALUES (
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Assign variables
    $client_id = (int)$_POST['client_id'];
    $company_id = (int)$_POST['company_id'];
    $window_type = $_POST['window_type'];
    $height = floatval($_POST['height']);
    $width = floatval($_POST['width']);
    $quantity = (int)$_POST['quantity'];
    $total_area = floatval($_POST['total_area']);
    $frame_length = floatval($_POST['frame_length'] ?? 0);
    $sash_length = floatval($_POST['sash_length'] ?? 0);
    $net_sash_length = floatval($_POST['net_sash_length'] ?? 0);
    $beading_length = floatval($_POST['beading_length'] ?? 0);
    $interlock_length = floatval($_POST['interlock_length'] ?? 0);
    $steel_quantity = floatval($_POST['steel_quantity'] ?? 0);
    $net_area = floatval($_POST['net_area'] ?? 0);
    $net_rubber_quantity = floatval($_POST['net_rubber_quantity'] ?? 0);
    $burshi_length = floatval($_POST['burshi_length'] ?? 0);
    $locks = (int)($_POST['locks'] ?? 0);
    $dummy = (int)($_POST['dummy'] ?? 0);
    $boofer = (int)($_POST['boofer'] ?? 0);
    $stopper = (int)($_POST['stopper'] ?? 0);
    $double_wheel = (int)($_POST['double_wheel'] ?? 0);
    $net_wheel = (int)($_POST['net_wheel'] ?? 0);
    $sada_screw = (int)($_POST['sada_screw'] ?? 0);
    $fitting_screw = (int)($_POST['fitting_screw'] ?? 0);
    $self_screw = floatval($_POST['self_screw'] ?? 0);
    $rawal_plug = (int)($_POST['rawal_plug'] ?? 0);
    $silicon_white = (int)($_POST['silicon_white'] ?? 0);
    $hole_caps = (int)($_POST['hole_caps'] ?? 0);
    $water_caps = (int)($_POST['water_caps'] ?? 0);
    $material_cost = floatval($_POST['material_cost']);
    $hardware_cost = floatval($_POST['hardware_cost']);
    $glass_cost = floatval($_POST['glass_cost']);
    $total_cost = floatval($_POST['total_cost']);

    // Bind parameters (33 parameters total)
    $stmt->bind_param(
        "iisddddddddddddiiiiiiiiidiiiiiddd",
        $client_id,
        $company_id,
        $window_type,
        $height,
        $width,
        $quantity,
        $total_area,
        $frame_length,
        $sash_length,
        $net_sash_length,
        $beading_length,
        $interlock_length,
        $steel_quantity,
        $net_area,
        $net_rubber_quantity,
        $burshi_length,
        $locks,
        $dummy,
        $boofer,
        $stopper,
        $double_wheel,
        $net_wheel,
        $sada_screw,
        $fitting_screw,
        $self_screw,
        $rawal_plug,
        $silicon_white,
        $hole_caps,
        $water_caps,
        $material_cost,
        $hardware_cost,
        $glass_cost,
        $total_cost
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to save calculation: " . $stmt->error);
    }

    $insert_id = $conn->insert_id;
    $conn->commit();

    echo json_encode([
        'success' => true,
        'id' => $insert_id,
        'message' => 'Calculation saved successfully!'
    ]);

} catch (Exception $e) {
    // Ensure no output has been sent
    ob_clean();
    
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->rollback();
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}