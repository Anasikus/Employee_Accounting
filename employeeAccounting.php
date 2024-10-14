<?php
$connection = new mysqli("localhost", "root", "123", "employeeAccounting");

if ($connection->connect_error) {
    error_log("Connection failed: " . $connection->connect_error);
    die("Ошибка соединения с базой данных.");
}

// Запрос для получения данных отделов, должностей и статусов
$departments = $connection->query(
    "SELECT idDepartment, address FROM Department"
);
$posts = $connection->query("SELECT idPost, titlePost FROM Post");
$statuses = $connection->query("SELECT idStatus, titleStatus FROM Status");

// Обработка POST-запросов
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $response = [];
    $action = $_POST["action"] ?? "";

    switch ($action) {
        case "addEmployee":
            $response = addEmployee($connection, $_POST);
            break;
        case "searchEmployee":
            $response = searchEmployee($connection, $_POST);
            break;
        case "filterByDepartmentPost": // Отдельная фильтрация по отделу и должности
            $response = filterByDepartmentPost($connection, $_POST);
            break;
        case "updateEmployee":
            $response = updateEmployee($connection, $_POST);
            break;
        case "fetchEmployeeData":
            $response = fetchEmployeeData($connection, $_POST);
            break;
        default:
            $response = [
                "error" => true,
                "message" => "Некорректное действие.",
            ];
    }

    echo json_encode($response);
    exit();
}

// Функция для обновления данных сотрудника
function updateEmployee($connection, $data)
{
    $id = intval($data["id"]);
    $surname = $connection->real_escape_string($data["surname"]);
    $name = $connection->real_escape_string($data["name"]);
    $patronymic = $connection->real_escape_string($data["patronymic"]);
    $dateOfBirth = $connection->real_escape_string($data["dateOfBirth"]);
    $departmentId = intval($data["department"]);
    $postId = intval($data["post"]);
    $statusId = intval($data["status"]);
    $passportData = $connection->real_escape_string($data["passportData"]);
    $addressResidential = $connection->real_escape_string(
        $data["addressResidential"]
    );
    $email = $connection->real_escape_string($data["email"]);
    $phoneNumber = $connection->real_escape_string($data["phoneNumber"]);

    // Разделяем паспортные данные на серию и номер
    $passportSeries = substr($passportData, 0, 4);
    $passportNumber = substr($passportData, 4);

    // Добавляем отладочные сообщения для проверки данных
    error_log("Updating employee ID: $id");

    // Обновляем таблицу PersonalData
    $updatePersonalData = $connection->prepare("
        UPDATE PersonalData 
        SET passportSeries = ?, passportNumber = ?, addressResidential = ?, email = ?, phoneNumber = ?
        WHERE idPersonalData = (SELECT idPersonalData FROM employeeAccounting WHERE idEmployee = ?)
    ");
    $updatePersonalData->bind_param(
        "sssssi",
        $passportSeries,
        $passportNumber,
        $addressResidential,
        $email,
        $phoneNumber,
        $id
    );

    if (!$updatePersonalData->execute()) {
        // Логируем ошибку, если запрос не выполняется
        error_log("Error updating PersonalData: " . $connection->error);
        return [
            "error" => true,
            "message" =>
                "Ошибка при обновлении PersonalData: " . $connection->error,
        ];
    }

    // Обновляем таблицу Employee
    $updateEmployee = $connection->prepare("
        UPDATE Employee 
        SET surname = ?, name = ?, patronymic = ?, dateOfBirth = ?
        WHERE idEmployee = ?
    ");
    $updateEmployee->bind_param(
        "ssssi",
        $surname,
        $name,
        $patronymic,
        $dateOfBirth,
        $id
    );

    if (!$updateEmployee->execute()) {
        // Логируем ошибку, если запрос не выполняется
        error_log("Error updating Employee: " . $connection->error);
        return [
            "error" => true,
            "message" =>
                "Ошибка при обновлении Employee: " . $connection->error,
        ];
    }

    // Обновляем таблицу employeeAccounting
    $updateEmployeeAccounting = $connection->prepare("
        UPDATE employeeAccounting
        SET idDepartment = ?, idPost = ?, idStatus = ?
        WHERE idEmployee = ?
    ");
    $updateEmployeeAccounting->bind_param(
        "iiii",
        $departmentId,
        $postId,
        $statusId,
        $id
    );

    if (!$updateEmployeeAccounting->execute()) {
        // Логируем ошибку, если запрос не выполняется
        error_log("Error updating employeeAccounting: " . $connection->error);
        return [
            "error" => true,
            "message" =>
                "Ошибка при обновлении employeeAccounting: " .
                $connection->error,
        ];
    }

    // Если все запросы выполнены успешно
    return ["success" => true, "message" => "Данные успешно обновлены"];
}

// Функция для фильтрации по отделу и должности
function filterByDepartmentPost($connection, $data)
{
    $departmentId = $connection->real_escape_string($data["departmentId"]);
    $postId = $connection->real_escape_string($data["postId"]);

    $query = "
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
            (p.hourlyRate * d.workingHours * 21) AS 'Зарплата'
        FROM employeeAccounting ea
        JOIN Employee e ON ea.idEmployee = e.idEmployee
        JOIN Department d ON ea.idDepartment = d.idDepartment
        JOIN Post p ON ea.idPost = p.idPost
        JOIN Status s ON ea.idStatus = s.idStatus
        WHERE 1=1";

    if (!empty($departmentId)) {
        $query .= " AND d.idDepartment = '$departmentId'";
    }
    if (!empty($postId)) {
        $query .= " AND p.idPost = '$postId'";
    }

    $result = $connection->query($query);
    $response = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                "id" => $row["id"],
                "surname" => $row["Фамилия"],
                "name" => $row["Имя"],
                "patronymic" => $row["Отчество"],
                "dateOfBirth" => $row["Дата рождения"],
                "dateOfEmployment" => $row["Дата принятия"],
                "department" => $row["Отдел"],
                "post" => $row["Должность"],
                "status" => $row["Статус"],
                "salary" => number_format($row["Зарплата"], 2, ",", " "),
            ];
        }
    }

    return $response;
}

// Получение данных для таблицы
$result = $connection->query(getEmployeeQuery());

function addEmployee($connection, $data)
{
    // Экранирование входных данных
    $surname = $connection->real_escape_string($data["surname"]);
    $name = $connection->real_escape_string($data["name"]);
    $patronymic = $connection->real_escape_string($data["patronymic"]);
    $dateOfBirth = $connection->real_escape_string($data["dateOfBirth"]);
    $dateOfEmployment = $connection->real_escape_string(
        $data["dateOfEmployment"]
    );
    $departmentId = (int) $data["department"];
    $postId = (int) $data["post"];
    $statusId = (int) $data["status"];
    $passportData = $connection->real_escape_string($data["passportData"]);
    $addressResidential = $connection->real_escape_string(
        $data["addressResidential"]
    );
    $email = $connection->real_escape_string($data["email"]);
    $phoneNumber = $connection->real_escape_string($data["phoneNumber"]);

    $passportSeries = substr($passportData, 0, 4);
    $passportNumber = substr($passportData, 4, 6);

    // Проверка на дубликат
    $checkDuplicate = $connection->prepare("
        SELECT * FROM personalData
        WHERE passportSeries = ? AND passportNumber = ? AND email = ? AND phoneNumber = ?
    ");
    $checkDuplicate->bind_param(
        "ssss",
        $passportSeries,
        $passportNumber,
        $email,
        $phoneNumber
    );
    $checkDuplicate->execute();
    $result = $checkDuplicate->get_result();

    if ($result && $result->num_rows > 0) {
        return ["error" => true, "message" => "Запись уже существует!"];
    }

    // Вставка данных в таблицу personalData
    $sqlPersonalData = $connection->prepare("INSERT INTO personalData (passportSeries, passportNumber, addressResidential, email, phoneNumber) 
                                             VALUES (?, ?, ?, ?, ?)");
    $sqlPersonalData->bind_param(
        "sssss",
        $passportSeries,
        $passportNumber,
        $addressResidential,
        $email,
        $phoneNumber
    );

    if ($sqlPersonalData->execute()) {
        $personalDataId = $connection->insert_id;

        // Вставка данных в таблицу Employee
        $sqlEmployeeData = $connection->prepare("INSERT INTO Employee (surname, name, patronymic, dateOfBirth, dateOfEmployment, idPersonalData) 
                                                 VALUES (?, ?, ?, ?, ?, ?)");
        $sqlEmployeeData->bind_param(
            "sssssi",
            $surname,
            $name,
            $patronymic,
            $dateOfBirth,
            $dateOfEmployment,
            $personalDataId
        );

        if ($sqlEmployeeData->execute()) {
            $employeeId = $connection->insert_id;

            // Вставка данных в таблицу employeeAccounting
            $sqlEmployeeAccounting = $connection->prepare("INSERT INTO employeeAccounting (idEmployee, idPersonalData, idDepartment, idPost, idStatus) 
                                                           VALUES (?, ?, ?, ?, ?)");
            $sqlEmployeeAccounting->bind_param(
                "iiiii",
                $employeeId,
                $personalDataId,
                $departmentId,
                $postId,
                $statusId
            );

            if ($sqlEmployeeAccounting->execute()) {
                return [
                    "id" => $employeeId,
                    "surname" => $surname,
                    "name" => $name,
                    "patronymic" => $patronymic,
                    "dateOfBirth" => $dateOfBirth,
                    "dateOfEmployment" => $dateOfEmployment,
                    "department" => $departmentId,
                    "post" => $postId,
                    "status" => $statusId,
                ];
            }
        }
    }

    return ["error" => true, "message" => "Ошибка при добавлении сотрудника."];
}

function filterByDepartmentAndPost($connection, $data)
{
    $departmentId = isset($data["departmentId"])
        ? intval($data["departmentId"])
        : null;
    $postId = isset($data["postId"]) ? intval($data["postId"]) : null;

    $query = "
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
            (p.hourlyRate * d.workingHours * 21) AS 'Зарплата'
        FROM employeeAccounting ea
        JOIN Employee e ON ea.idEmployee = e.idEmployee
        JOIN Department d ON ea.idDepartment = d.idDepartment
        JOIN Post p ON ea.idPost = p.idPost
        JOIN Status s ON ea.idStatus = s.idStatus
        WHERE 1=1";

    if ($departmentId) {
        $query .= " AND d.idDepartment = $departmentId";
    }
    if ($postId) {
        $query .= " AND p.idPost = $postId";
    }

    $result = $connection->query($query);
    $response = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                "id" => $row["id"],
                "surname" => $row["Фамилия"],
                "name" => $row["Имя"],
                "patronymic" => $row["Отчество"],
                "dateOfBirth" => $row["Дата рождения"],
                "dateOfEmployment" => $row["Дата принятия"],
                "department" => $row["Отдел"],
                "post" => $row["Должность"],
                "status" => $row["Статус"],
                "salary" => number_format($row["Зарплата"], 2, ",", " "),
            ];
        }
    }

    return $response;
}

// Обработка запросов на сервере
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $response = [];
    $action = $_POST["action"] ?? "";

    switch ($action) {
        case "addEmployee":
            $response = addEmployee($connection, $_POST);
            break;
        case "searchEmployee":
            $response = searchEmployee($connection, $_POST);
            break;
        case "filterByDepartmentAndPost":
            $response = filterByDepartmentAndPost($connection, $_POST);
            break;
        default:
            $response = [
                "error" => true,
                "message" => "Некорректное действие.",
            ];
    }

    echo json_encode($response);
    exit();
}
function searchEmployee($connection, $data)
{
    $surname = $connection->real_escape_string($data["surname"] ?? "");
    $name = $connection->real_escape_string($data["name"] ?? "");
    $patronymic = $connection->real_escape_string($data["patronymic"] ?? "");

    $query = "
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
            (p.hourlyRate * d.workingHours * 21) AS 'Зарплата'
        FROM employeeAccounting ea
        JOIN Employee e ON ea.idEmployee = e.idEmployee
        JOIN Department d ON ea.idDepartment = d.idDepartment
        JOIN Post p ON ea.idPost = p.idPost
        JOIN Status s ON ea.idStatus = s.idStatus
        WHERE 1=1";

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
                "id" => $row["id"],
                "surname" => $row["Фамилия"],
                "name" => $row["Имя"],
                "patronymic" => $row["Отчество"],
                "dateOfBirth" => $row["Дата рождения"],
                "dateOfEmployment" => $row["Дата принятия"],
                "department" => $row["Отдел"],
                "post" => $row["Должность"],
                "status" => $row["Статус"],
                "salary" => number_format($row["Зарплата"], 2, ",", " "),
            ];
        }
    }

    return $response;
}

function fetchEmployeeData($connection, $data)
{
    $departmentId =
        isset($data["departmentId"]) && !empty($data["departmentId"])
            ? $connection->real_escape_string($data["departmentId"])
            : null;
    $postId =
        isset($data["postId"]) && !empty($data["postId"])
            ? $connection->real_escape_string($data["postId"])
            : null;

    $query = "
        SELECT 
            ea.idRecords, 
            e.surname, 
            e.name, 
            e.patronymic, 
            e.dateOfBirth, 
            e.dateOfEmployment, 
            d.address, 
            p.titlePost, 
            (p.hourlyRate * d.workingHours * 21) AS Salary 
        FROM employeeAccounting ea
        JOIN Employee e ON ea.idEmployee = e.idEmployee
        JOIN Department d ON ea.idDepartment = d.idDepartment
        JOIN Post p ON ea.idPost = p.idPost
        WHERE 1=1
    ";

    if ($departmentId) {
        $query .= " AND d.idDepartment = '$departmentId'";
    }

    if ($postId) {
        $query .= " AND p.idPost = '$postId'";
    }

    // Выводим запрос для отладки (можно убрать в продакшене)
    // echo "SQL-запрос: $query<br>";

    $result = $connection->query($query);
    $employeeDetails = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employeeDetails[] = $row;
        }
    }

    return $employeeDetails;
}

function getEmployeeQuery($surname = "", $name = "", $patronymic = "")
{
    $query = "
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
            (p.hourlyRate * d.workingHours * 21) AS 'Зарплата'
        FROM employeeAccounting ea
        JOIN Employee e ON ea.idEmployee = e.idEmployee
        JOIN Department d ON ea.idDepartment = d.idDepartment
        JOIN Post p ON ea.idPost = p.idPost
        JOIN Status s ON ea.idStatus = s.idStatus
        WHERE 1=1";

    if (!empty($surname)) {
        $query .= " AND e.surname LIKE '%$surname%'";
    }
    if (!empty($name)) {
        $query .= " AND e.name LIKE '%$name%'";
    }
    if (!empty($patronymic)) {
        $query .= " AND e.patronymic LIKE '%$patronymic%'";
    }

    return $query;
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
        (p.hourlyRate * d.workingHours * 21) AS 'Зарплата'
    FROM employeeAccounting ea
    JOIN Employee e ON ea.idEmployee = e.idEmployee
    JOIN Department d ON ea.idDepartment = d.idDepartment
    JOIN Post p ON ea.idPost = p.idPost
    JOIN Status s ON ea.idStatus = s.idStatus
    ORDER BY e.idEmployee ASC  -- Сортировка по возрастанию id
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
      <button onclick="toggleModal('employeeModal')">Добавить сотрудника</button>
      <form id="searchForm" onsubmit="event.preventDefault(); searchEmployee();">
         <div>
            <label for="searchSurname">Фамилия:</label>
            <input type="text" id="searchSurname" placeholder="Фамилия">
         </div>
         <div>
            <label for="searchName">Имя:</label>
            <input type="text" id="searchName" placeholder="Имя">
         </div>
         <div>
            <label for="searchPatronymic">Отчество:</label>
            <input type="text" id="searchPatronymic" placeholder="Отчество">
         </div>
         <button type="submit" id="filterButton" onclick="fetchEmployeeData()">Применить</button>
         <button type="button" id="resetButton" onclick="resetFilters()">Сбросить фильтры</button>
      </form>
      <form id="filterForm" onsubmit="event.preventDefault(); applyDepartmentPostFilter();">
         <div>
            <label for="searchDepartment">Отдел:</label>
            <select id="searchDepartment" name="searchDepartment">
               <option value="">Все</option>
               <?php while ($row = $departments->fetch_assoc()) { ?>
               <option value="<?php echo $row['idDepartment']; ?>"><?php echo $row['address']; ?></option>
               <?php } ?>
            </select>
         </div>
         <div>
            <label for="searchPost">Должность:</label>
            <select id="searchPost" name="searchPost">
               <option value="">Все</option>
               <?php while ($row = $posts->fetch_assoc()) { ?>
               <option value="<?php echo $row['idPost']; ?>"><?php echo $row['titlePost']; ?></option>
               <?php } ?>
            </select>
         </div>
         <button type="submit" id="filterButton">Применить фильтр</button>
         <!-- Новая кнопка сброса фильтров -->
         <button type="button" id="resetFilterButton" onclick="resetFiltersDP()">Сбросить фильтр</button>
      </form>
      <div id="employeeData"></div>
      <script>
         function applyFilter() {
             const departmentId = document.getElementById('searchDepartment').value;
             const postId = document.getElementById('searchPost').value;
         
             fetch('employeeAccounting.php', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                 },
                 body: JSON.stringify({
                     action: 'fetchEmployeeData',
                     departmentId: departmentId,
                     postId: postId
                 }),
             })
             .then(response => response.json())
             .then(data => {
                 displayEmployeeData(data); // Отображаем полученные данные
             })
             .catch(error => {
                 console.error('Ошибка при загрузке данных:', error);
             });
         }
         
         function displayEmployeeData(data) {
             const employeeDataDiv = document.getElementById('employeeData');
             employeeDataDiv.innerHTML = ''; // Очищаем предыдущие данные
         
             if (data.length > 0) {
                 const table = document.createElement('table');
                 const headerRow = table.insertRow();
                 ['№', 'Фамилия', 'Имя', 'Отчество', 'Дата рождения', 'Дата принятия', 'Отдел', 'Должность', 'Зарплата'].forEach(header => {
                     const cell = headerRow.insertCell();
                     cell.textContent = header;
                 });
         
                 data.forEach(employee => {
                     const row = table.insertRow();
                     ['idRecords', 'surname', 'name', 'patronymic', 'dateOfBirth', 'dateOfEmployment', 'address', 'titlePost', 'Salary'].forEach(field => {
                         const cell = row.insertCell();
                         cell.textContent = employee[field];
                     });
                 });
         
                 employeeDataDiv.appendChild(table);  // Добавляем таблицу в DOM
             } else {
                 employeeDataDiv.textContent = "Сотрудников не найдено.";  // Если нет данных
             }
         }
         
      </script>
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
         
         function getPostOptions(currentPost) {
             let options = '';
             <?php 
            $posts->data_seek(0); // Сбрасываем указатель для повторного использования
            while ($postRow = $posts->fetch_assoc()) { 
                echo "options += '<option value=\"{$postRow['idPost']}\"' + (currentPost === '{$postRow['titlePost']}' ? ' selected' : '') + '>{$postRow['titlePost']}</option>';";
            } 
            ?>
             return options;
         }
             
      </script>
      <div id="employeeModal" class="modal">
         <div class="modal-content">
            <span onclick="toggleModal('employeeModal')" style="cursor:pointer;float:right;">&times;</span>
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
                     <?php 
                        $departments->data_seek(0); // Сбрасываем указатель для повторного использования
                        while ($row = $departments->fetch_assoc()) { ?>
                     <option value="<?php echo $row['idDepartment']; ?>"><?php echo $row['address']; ?></option>
                     <?php } ?>
                  </select>
               </div>
               <div class="form-row">
                  <label for="post">Должность:</label>
                  <select id="post" name="post" required>
                     <?php 
                        $posts->data_seek(0); // Сбрасываем указатель для повторного использования
                        while ($row = $posts->fetch_assoc()) { ?>
                     <option value="<?php echo $row['idPost']; ?>"><?php echo $row['titlePost']; ?></option>
                     <?php } ?>
                  </select>
               </div>
               <div class="form-row">
                  <label for="status">Статус:</label>
                  <select id="status" name="status" required>
                     <?php 
                        $statuses->data_seek(0); // Сбрасываем указатель для повторного использования
                        while ($row = $statuses->fetch_assoc()) { ?>
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
               <td>
                  <select name="post" onchange="updateEmployeePost(<?php echo $row['id']; ?>, this.value)">
                     <?php
                          $posts->data_seek(0);
                           while ($postRow = $posts->fetch_assoc()) { ?>
                       <option value="<?php echo $postRow['idPost']; ?>" <?php if ($row['Должность'] == $postRow['titlePost']) echo 'selected'; ?>>
                     <?php echo $postRow['titlePost']; ?>
                 </option>
                    <?php } ?>
                   </select>
               </td>
               <td><?php echo number_format($row['Зарплата'], 2, ',', ' '); ?> ₽</td>
               <!-- Отображение зарплаты -->
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
      <div id="editEmployeeModal" class="modal">
         <div class="modal-content">
            <span onclick="toggleModal('editEmployeeModal')" style="cursor:pointer;float:right;">&times;</span>
            <h2>Редактировать данные сотрудника</h2>
            <form id="editEmployeeForm" onsubmit="event.preventDefault(); updateEmployee();">
               <div class="form-row">
                  <label for="editSurname">Фамилия:</label>
                  <input type="text" id="editSurname" name="surname" required>
               </div>
               <div class="form-row">
                  <label for="editName">Имя:</label>
                  <input type="text" id="editName" name="name" required>
               </div>
               <div class="form-row">
                  <label for="editPatronymic">Отчество:</label>
                  <input type="text" id="editPatronymic" name="patronymic" required>
               </div>
               <div class="form-row">
                  <label for="editDateOfBirth">Дата рождения:</label>
                  <input type="date" id="editDateOfBirth" name="dateOfBirth" required>
               </div>
               <div class="form-row">
                  <label for="editDepartment">Отдел:</label>
                  <select id="editDepartment" name="department" required></select>
               </div>
               <div class="form-row">
                  <label for="editPost">Должность:</label>
                  <select id="editPost" name="post" required></select>
               </div>
               <div class="form-row">
                  <label for="editStatus">Статус:</label>
                  <select id="editStatus" name="status" required></select>
               </div>
               <div class="form-row">
                  <label for="editPassportData">Паспортные данные:</label>
                  <input type="text" id="editPassportData" name="passportData" required>
               </div>
               <div class="form-row">
                  <label for="editAddressResidential">Адрес проживания:</label>
                  <input type="text" id="editAddressResidential" name="addressResidential" required>
               </div>
               <div class="form-row">
                  <label for="editEmail">E-mail:</label>
                  <input type="editEmail" id="editEmail" name="email" required>
               </div>
               <div class="form-row">
                  <label for="editPhoneNumber">Телефон:</label>
                  <input type="text" id="editPhoneNumber" name="phoneNumber" required>
               </div>
               <div class="form-row">
                  <button type="submit">Сохранить изменения</button>
               </div>
            </form>
         </div>
      </div>
   </body>
</html>