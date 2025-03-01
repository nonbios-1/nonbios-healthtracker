<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
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
    
    $stmt = $conn->prepare("INSERT INTO food_logs (user_id, food_name, calories, protein, carbs, fat, meal_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiddds", 
        $_SESSION['user_id'],
        $data['food_name'],
        $data['calories'],
        $data['protein'],
        $data['carbs'],
        $data['fat'],
        $data['meal_type']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    $stmt->close();
} 
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $period = isset($_GET['period']) ? $_GET['period'] : 'day';
    
    $query = "SELECT 
                DATE(consumed_at) as date,
                SUM(calories) as total_calories,
                SUM(protein) as total_protein,
                SUM(carbs) as total_carbs,
                SUM(fat) as total_fat,
                GROUP_CONCAT(CONCAT(food_name, ' (', calories, ' cal)') SEPARATOR ', ') as foods
              FROM food_logs 
              WHERE user_id = ? ";
    
    switch($period) {
        case 'day':
            $query .= "AND DATE(consumed_at) = CURDATE() ";
            break;
        case 'week':
            $query .= "AND consumed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ";
            break;
        case 'month':
            $query .= "AND consumed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ";
            break;
    }
    
    $query .= "GROUP BY DATE(consumed_at) ORDER BY date DESC";
    
    $stmt = $conn->prepare($query);
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
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("DELETE FROM food_logs WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $data['id'], $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>
