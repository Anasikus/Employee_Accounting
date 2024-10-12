<?php
$connection = new mysqli('localhost', 'root', '123', 'employeeAccounting');

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$departments = $connection->query("SELECT idDepartment, address FROM Department");
$posts = $connection->query("SELECT idPost, titlePost FROM Post");
$statuses = $connection->query("SELECT idStatus, titleStatus FROM Status");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];

    // Добавление нового сотрудника
    if (isset($_POST['action']) && $_POST['action'] === 'addEmployee') {
        $surname = $connection->real_escape_string($_POST['surname']);
        $name = $connection->real_escape_string($_POST['name']);
        $patronymic = $connection->real_escape_string($_POST['patronymic']);
        $dateOfBirth = $connection->real_escape_string($_POST['dateOfBirth']);
        $dateOfEmployment = $connection->real_escape_string($_POST['dateOfEmployment']);
        $departmentId = $connection->real_escape_string($_POST['department']);
        $postId = $connection->real_escape_string($_POST['post']);
        $statusId = $connection->real_escape_string($_POST['status']);
        $passportData = $connection->real_escape_string($_POST['passportData']);
        $addressResidential = $connection->real_escape_string($_POST['addressResidential']);
        $email = $connection->real_escape_string($_POST['email']);
        $phoneNumber = $connection->real_escape_string($_POST['phoneNumber']);

        $passportSeries = substr($passportData, 0, 4);
        $passportNumber = substr($passportData, 5, 6);

        // Проверка на дубликат
        $checkDuplicate = $connection->query("
            SELECT * FROM personalData
            WHERE passportSeries = '$passportSeries'
            AND passportNumber = '$passportNumber'
            AND email = '$email'
            AND phoneNumber = '$phoneNumber'
        ");

        if ($checkDuplicate && $checkDuplicate->num_rows > 0) {
            $response = ["error" => true, "message" => "Запись уже существует!"];
        } else {
            $sqlPersonalData = "INSERT INTO personalData (passportSeries, passportNumber, addressResidential, email, phoneNumber) 
                                VALUES ('$passportSeries', '$passportNumber', '$addressResidential', '$email', '$phoneNumber')";
            
            if ($connection->query($sqlPersonalData) === TRUE) {
                $personalDataId = $connection->insert_id;

                $sqlEmployeeData = "INSERT INTO Employee (surname, name, patronymic, dateOfBirth, dateOfEmployment) 
                                    VALUES ('$surname', '$name', '$patronymic', '$dateOfBirth', '$dateOfEmployment')";
                if ($connection->query($sqlEmployeeData) === TRUE) {
                    $employeeId = $connection->insert_id;

                    $sqlEmployeeAccounting = "INSERT INTO employeeAccounting (idEmployee, idPersonalData, idDepartment, idPost, idStatus) 
                                              VALUES ('$employeeId', '$personalDataId', '$departmentId', '$postId', '$statusId')";
                    if ($connection->query($sqlEmployeeAccounting) === TRUE) {
                        $response = [
                            "id" => $employeeId,
                            "surname" => $surname,
                            "name" => $name,
                            "patronymic" => $patronymic,
                            "dateOfBirth" => $dateOfBirth,
                            "dateOfEmployment" => $dateOfEmployment,
                            "department" => $departmentId,
                            "post" => $postId,
                            "status" => $statusId
                        ];
                    }
                }
            }
        }

        echo json_encode($response);
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'searchEmployee') {
        // Поиск сотрудников по фамилии, имени и отчеству
        $surname = $connection->real_escape_string($_POST['surname'] ?? '');
        $name = $connection->real_escape_string($_POST['name'] ?? '');
        $patronymic = $connection->real_escape_string($_POST['patronymic'] ?? '');
    
        $query = "SELECT 
                    e.idEmployee AS 'id',
                    e.surname AS 'Фамилия',
                    e.name AS 'Имя',
                    e.patronymic AS 'Отчество',
                    e.dateOfBirth AS 'Дата рождения',
                    e.dateOfEmployment AS 'Дата принятия',
                    d.address AS 'Отдел',
                    p.titlePost AS 'Должность',
                    (ea.hourlyRate * d.workingHours * 21) AS 'Зарплата',
                    s.titleStatus AS 'Статус'
                FROM employeeAccounting ea
                JOIN Employee e ON ea.idEmployee = e.idEmployee
                JOIN Department d ON ea.idDepartment = d.idDepartment
                JOIN Post p ON ea.idPost = p.idPost
                JOIN Status s ON ea.idStatus = s.idStatus
                WHERE 1=1";
    
        // Добавляем условия поиска по каждому полю, если оно заполнено
        if (!empty($surname)) {
            $query .= " AND e.surname LIKE '%$surname%'";
        }
        if (!empty($name)) {
            $query .= " AND e.name LIKE '%$name%'";
        }
        if (!empty($patronymic)) {
            $query .= " AND e.patronymic LIKE '%$patronymic%'";
        }
    
        $result = $connection->query($query);
        $response = [];
    
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $response[] = [
                    "id" => $row['id'],
                    "surname" => $row['Фамилия'],
                    "name" => $row['Имя'],
                    "patronymic" => $row['Отчество'],
                    "dateOfBirth" => $row['Дата рождения'],
                    "dateOfEmployment" => $row['Дата принятия'],
                    "department" => $row['Отдел'],
                    "post" => $row['Должность'],
                    "status" => $row['Статус'],
                    "salary" => number_format($row['Зарплата'], 2, ',', ' ')  // Форматирование зарплаты
                ];
            }
        }
    
        echo json_encode($response);
        exit();
    }
    
}



$result = $connection->query("
    SELECT 
        e.idEmployee AS 'id',
        e.surname AS 'Фамилия',
        e.name AS 'Имя',
        e.patronymic AS 'Отчество',
        e.dateOfBirth AS 'Дата рождения',
        e.dateOfEmployment AS 'Дата принятия',
        d.address AS 'Отдел',
        p.titlePost AS 'Должность',
        s.titleStatus AS 'Статус',
        (ea.hourlyRate * d.workingHours *21) AS 'Зарплата'
    FROM employeeAccounting ea
    JOIN Employee e ON ea.idEmployee = e.idEmployee
    JOIN Department d ON ea.idDepartment = d.idDepartment
    JOIN Post p ON ea.idPost = p.idPost
    JOIN Status s ON ea.idStatus = s.idStatus
    ORDER BY e.idEmployee ASC
");



$connection->close();
?>



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Учёт сотрудников</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <script src="js.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/inputmask.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<button onclick="toggleModal()">Добавить сотрудника</button>

<form id="searchForm" onsubmit="event.preventDefault(); searchEmployee();" style="max-width: 600px; margin: 20px auto;">
    <div class="form-row">
        <label for="searchSurname">Фамилия:</label>
        <input type="text" id="searchSurname" placeholder="Фамилия">
    </div>
    <div class="form-row">
        <label for="searchName">Имя:</label>
        <input type="text" id="searchName" placeholder="Имя">
    </div>
    <div class="form-row">
        <label for="searchPatronymic">Отчество:</label>
        <input type="text" id="searchPatronymic" placeholder="Отчество">
    </div>
    <button type="submit" id="searchButton">Искать</button>
</form>



<script>
    function getStatusOptions(currentStatus) {
    let options = '';
    <?php 
        $statuses->data_seek(0); // Сбрасываем указатель для повторного использования
        while ($statusRow = $statuses->fetch_assoc()) { 
            echo "options += '<option value=\"{$statusRow['idStatus']}\"' + (currentStatus === '{$statusRow['titleStatus']}' ? ' selected' : '') + '>{$statusRow['titleStatus']}</option>';";
        } 
    ?>
    return options;
}
    </script>


<div id="employeeModal" class="modal">
    <div class="modal-content">
        <span onclick="toggleModal()" style="cursor:pointer;float:right;">&times;</span>
        <h2>Добавить нового сотрудника</h2>
        <form id="employeeForm" onsubmit="event.preventDefault(); addEmployee();">
            <div class="form-row">
                <label for="surname">Фамилия:</label>
                <input type="text" id="surname" name="surname" required>
            </div>
            <div class="form-row">
                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-row">
                <label for="patronymic">Отчество:</label>
                <input type="text" id="patronymic" name="patronymic" required>
            </div>
            <div class="form-row">
                <label for="dateOfBirth">Дата рождения:</label>
                <input type="date" id="dateOfBirth" name="dateOfBirth" required>
            </div>
            <div class="form-row">
                <label for="dateOfEmployment">Дата принятия:</label>
                <input type="date" id="dateOfEmployment" name="dateOfEmployment" required>
            </div>
            <div class="form-row">
                <label for="department">Отдел:</label>
                <select id="department" name="department" required>
                    <?php while ($row = $departments->fetch_assoc()) { ?>
                        <option value="<?php echo $row['idDepartment']; ?>"><?php echo $row['address']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row">
                <label for="post">Должность:</label>
                <select id="post" name="post" required>
                    <?php while ($row = $posts->fetch_assoc()) { ?>
                        <option value="<?php echo $row['idPost']; ?>"><?php echo $row['titlePost']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row">
                <label for="status">Статус:</label>
                <select id="status" name="status" required>
                    <?php while ($row = $statuses->fetch_assoc()) { ?>
                        <option value="<?php echo $row['idStatus']; ?>"><?php echo $row['titleStatus']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row">
                <label for="passportData">Паспортные данные:</label>
                <input type="text" id="passportData" name="passportData" required>
            </div>
            <div class="form-row">
                <label for="addressResidential">Адрес проживания:</label>
                <input type="text" id="addressResidential" name="addressResidential" required>
            </div>
            <div class="form-row">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-row">
                <label for="phoneNumber">Телефон:</label>
                <input type="text" id="phoneNumber" name="phoneNumber" required>
            </div>
            <div class="form-row">
                <button type="submit">Добавить</button>
            </div>
        </form>
    </div>
</div>

<div id="notification"></div>

<table>
    <thead>
        <tr>
            <th>№</th>
            <th>Фамилия</th>
            <th>Имя</th>
            <th>Отчество</th>
            <th>Дата рождения</th>
            <th>Дата принятия</th>
            <th>Отдел</th>
            <th>Должность</th>
            <th>Зарплата</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
<?php while ($row = $result->fetch_assoc()) { ?>
    <tr data-id="<?php echo $row['id']; ?>">
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['Фамилия']; ?></td>
        <td><?php echo $row['Имя']; ?></td>
        <td><?php echo $row['Отчество']; ?></td>
        <td><?php echo $row['Дата рождения']; ?></td>
        <td><?php echo $row['Дата принятия']; ?></td>
        <td><?php echo $row['Отдел']; ?></td>
        <td><?php echo $row['Должность']; ?></td>
        <td><?php echo number_format($row['Зарплата'], 2, ',', ' '); ?> ₽</td> <!-- Отображение зарплаты -->
        <td>
            <select onchange="updateEmployeeStatus(<?php echo $row['id']; ?>, this.value)">
                <?php
                $statuses->data_seek(0); // Сбрасываем указатель для повторного использования
                while ($statusRow = $statuses->fetch_assoc()) { ?>
                    <option value="<?php echo $statusRow['idStatus']; ?>" <?php if ($row['Статус'] == $statusRow['titleStatus']) echo 'selected'; ?>>
                        <?php echo $statusRow['titleStatus']; ?>
                    </option>
                <?php } ?>
            </select>
        </td>
        <td>
            <button onclick="showDetails('<?php echo $row['id']; ?>')">Подробнее</button>
        </td>
    </tr>
<?php } ?>
</tbody>

</table>

<!-- Модальное окно для отображения паспортных данных -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span onclick="toggleDetailsModal()" style="cursor:pointer;float:right;">&times;</span>
        <h2>Паспортные данные</h2>
        <p id="passportDetails"></p>
    </div>
</div>

</body>
</html>
