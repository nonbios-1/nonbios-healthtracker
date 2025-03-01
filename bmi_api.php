<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not authenticated']));
}

$conn = new mysqli("localhost", "newuser", "newpassword", "bmi_tracker");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("INSERT INTO bmi_records (height, weight, bmi, category, measurement_system, user_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("dddssi", 
        $data['height'],
        $data['weight'],
        $data['bmi'],
        $data['category'],
        $data['measurement_system'],
        $_SESSION['user_id']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    $stmt->close();
} 
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Only get records for the logged-in user
    $stmt = $conn->prepare("SELECT * FROM bmi_records WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    echo json_encode($records);
    $stmt->close();
}

$conn->close();
?>
