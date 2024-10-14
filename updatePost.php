<?php
$connection = new mysqli('localhost', 'root', '123', 'employeeAccounting');

if ($connection->connect_error) {
    die("Ошибка соединения: " . $connection->connect_error);
}

$employeeId = $_POST['id'];    // Используем $_POST, так как FormData используется на фронтенде
$newPostId = $_POST['postId'];

// Обновляем должность сотрудника
$query = "UPDATE employeeAccounting SET idPost = ? WHERE idEmployee = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("ii", $newPostId, $employeeId);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Ошибка обновления должности."]);
}

$stmt->close();
$connection->close();

?>
