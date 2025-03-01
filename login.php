<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "newuser", "newpassword", "bmi_tracker");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action'])) {
        switch($data['action']) {
            case 'login':
                $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
                $stmt->bind_param("s", $data['username']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($user = $result->fetch_assoc()) {
                    if (password_verify($data['password'], $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        echo json_encode(['success' => true, 'username' => $user['username']]);
                    } else {
                        echo json_encode(['error' => 'Invalid password']);
                    }
                } else {
                    echo json_encode(['error' => 'User not found']);
                }
                break;
                
            case 'register':
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $data['username'], $data['email']);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows > 0) {
                    echo json_encode(['error' => 'Username or email already exists']);
                    break;
                }
                
                $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $data['username'], $hashed_password, $data['email']);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['username'] = $data['username'];
                    echo json_encode(['success' => true, 'username' => $data['username']]);
                } else {
                    echo json_encode(['error' => 'Registration failed']);
                }
                break;
                
            case 'logout':
                session_destroy();
                echo json_encode(['success' => true]);
                break;
                
            case 'check_session':
                if (isset($_SESSION['user_id'])) {
                    echo json_encode(['logged_in' => true, 'username' => $_SESSION['username']]);
                } else {
                    echo json_encode(['logged_in' => false]);
                }
                break;
        }
    }
}

$conn->close();
?>
