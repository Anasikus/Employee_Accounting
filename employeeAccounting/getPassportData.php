<?php
$connection = new mysqli('localhost', 'root', '123', 'employeeAccounting');

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

if (isset($_GET['id'])) {
    $employeeId = $connection->real_escape_string($_GET['id']);
    
    // Запрос для получения паспортных данных сотрудника
    $query = "
        SELECT CONCAT(pd.passportSeries, ' ', pd.passportNumber) AS passportData
        FROM employeeAccounting ea
        JOIN personalData pd ON ea.idPersonalData = pd.idPersonalData
        WHERE ea.idEmployee = '$employeeId'
    ";

    $result = $connection->query($query);

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data); // Возвращаем паспортные данные в формате JSON
    } else {
        echo json_encode(['passportData' => 'Данные не найдены']);
    }
} else {
    echo json_encode(['passportData' => 'Некорректный запрос']);
}

$connection->close();
?>
