<?php
$connection = new mysqli('localhost', 'root', '123', 'employeeAccounting');

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateStatus') {
    $employeeId = $connection->real_escape_string($_POST['id']);
    $newStatus = $connection->real_escape_string($_POST['status']);

    $sql = "UPDATE employeeAccounting SET idStatus = '$newStatus' WHERE idEmployee = '$employeeId'";

    if ($connection->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => true, 'message' => 'Ошибка при обновлении статуса']);
    }
} else {
    echo json_encode(['error' => true, 'message' => 'Неверные данные']);
}

$connection->close();
?>
