<?php
header('Content-Type: application/json');

$connection = new mysqli('localhost', 'root', '123', 'employeeAccounting');
if ($connection->connect_error) {
    echo json_encode(['error' => true, 'message' => 'Ошибка соединения с базой данных']);
    exit();
}

$id = intval($_GET['id']);
$query = "SELECT 
    e.surname, 
    e.name, 
    e.patronymic, 
    e.dateOfBirth, 
    ea.idDepartment, 
    ea.idPost, 
    ea.idStatus, 
    pd.passportSeries,
    pd.passportNumber,
    pd.addressResidential,
    pd.email,
    pd.phoneNumber
FROM 
    Employee e
JOIN 
    employeeAccounting ea ON e.idEmployee = ea.idEmployee
JOIN 
    PersonalData pd ON ea.idPersonalData = pd.idPersonalData
          WHERE e.idEmployee = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();

    // Получаем все отделы, должности и статусы
    $departments = $connection->query("SELECT idDepartment, address FROM Department")->fetch_all(MYSQLI_ASSOC);
    $posts = $connection->query("SELECT idPost, titlePost FROM Post")->fetch_all(MYSQLI_ASSOC);
    $statuses = $connection->query("SELECT idStatus, titleStatus FROM Status")->fetch_all(MYSQLI_ASSOC);

    // Добавляем эти данные в ответ
    $data['departments'] = $departments;
    $data['posts'] = $posts;
    $data['statuses'] = $statuses;

    echo json_encode($data);
} else {
    echo json_encode(['error' => true, 'message' => 'Сотрудник не найден']);
}

$connection->close();

?>
