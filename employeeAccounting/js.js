// Функция для открытия/закрытия модального окна
function toggleModal() {
    var modal = document.getElementById("employeeModal");
    modal.style.display = modal.style.display === "none" || modal.style.display === "" ? "block" : "none";
}

function toggleDetailsModal() {
    var modal = document.getElementById("detailsModal");
    modal.style.display = modal.style.display === "none" || modal.style.display === "" ? "block" : "none";
}

// Функция для отображения уведомления
function showNotification(message) {
    const notification = document.getElementById('notification');
    notification.innerText = message;
    notification.style.display = 'block';

    setTimeout(() => {
        notification.style.display = 'none';
    }, 5000);
}

// Функция для добавления нового сотрудника через AJAX
function addEmployee() {
    const formData = new FormData(document.getElementById('employeeForm'));
    formData.append('action', 'addEmployee');  // Добавляем параметр action для серверной логики

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())  // Ожидаем, что сервер вернёт данные в формате JSON
    .then(data => {
        if (data.error) {
            showNotification(data.message);
        } else {
            showNotification('Сотрудник успешно добавлен!');
            toggleModal();
            document.getElementById('employeeForm').reset();
            addEmployeeRow(data);  // Добавляем новую строку в таблицу
        }
    });
}


// Функция для динамического добавления строки сотрудника в таблицу
// Функция добавления строки сотрудника в таблицу
function addEmployeeRow(employeeData) {
    const tableBody = document.querySelector('tbody');
    const newRow = document.createElement('tr');

    newRow.innerHTML = `
        <td>${employeeData.id}</td>
        <td>${employeeData.surname}</td>
        <td>${employeeData.name}</td>
        <td>${employeeData.patronymic}</td>
        <td>${employeeData.dateOfBirth}</td>
        <td>${employeeData.dateOfEmployment}</td>
        <td>${employeeData.department}</td>
        <td>${employeeData.post}</td>
        <td>${employeeData.status}</td>
        <td>
            <button onclick="showDetails(${employeeData.id})">Подробнее</button>
        </td>
    `;

    tableBody.appendChild(newRow); // Добавляем новую строку в конец таблицы
}

// Функция для поиска сотрудников по ФИО
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');

    if (searchForm) {
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Предотвращает отправку формы
            searchEmployee(); // Вызывает функцию поиска
        });
    } else {
        console.error("Форма поиска не найдена в DOM!");
    }
});


function searchEmployee() {
    const surname = document.getElementById('searchSurname').value;
    const name = document.getElementById('searchName').value;
    const patronymic = document.getElementById('searchPatronymic').value;

    $.post('employeeAccounting.php', {
        action: 'searchEmployee',
        surname: surname,
        name: name,
        patronymic: patronymic
    }, function(response) {
        const result = JSON.parse(response);
        const tbody = document.querySelector('tbody');
        tbody.innerHTML = ''; // Очищаем предыдущие результаты

        if (result && result.length > 0) {
            result.forEach(employee => {
                const row = document.createElement('tr');
                row.setAttribute('data-id', employee.id);
                row.innerHTML = `
                    <td>${employee.id}</td>
                    <td>${employee.surname}</td>
                    <td>${employee.name}</td>
                    <td>${employee.patronymic}</td>
                    <td>${employee.dateOfBirth}</td>
                    <td>${employee.dateOfEmployment}</td>
                    <td>${employee.department}</td>
                    <td>${employee.post}</td>
                    <td>${employee.salary} ₽</td>
                    <td>
                        <select onchange="updateEmployeeStatus(${employee.id}, this.value)">
                            ${getStatusOptions(employee.status)}
                        </select>
                    </td>
                    <td>
                        <button onclick="showDetails('${employee.id}')">Подробнее</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } else {
            // Если не найдено, выводим сообщение
            const row = document.createElement('tr');
            row.innerHTML = `<td colspan="11">Сотрудник не найден</td>`;
            tbody.appendChild(row);
        }
    });
}






// Открытие модального окна с паспортными данными
function showDetails(employeeId) {
    fetch(`getPassportData.php?id=${employeeId}`)
    .then(response => response.json())
    .then(data => {
        document.getElementById('passportDetails').innerText = data.passportData;
        toggleDetailsModal();
    });
}

// Функция для обновления статуса сотрудника
function updateEmployeeStatus(employeeId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'updateStatus');
    formData.append('id', employeeId);
    formData.append('status', newStatus);

    fetch('updateStatus.php', {  // Путь к вашему обработчику статуса
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showNotification(data.message);
        } else {
            showNotification('Статус сотрудника обновлён');
        }
    })
    .catch(error => {
        console.error('Ошибка при обновлении статуса:', error);
    });
}



// Функция для удаления строки сотрудника из таблицы
function removeEmployeeRow(employeeId) {
    const row = document.querySelector(`tr[data-id="${employeeId}"]`);
    if (row) {
        row.remove();
    }
}

// Маски для полей ввода
window.onload = function() {
    Inputmask({
        mask: "+7 (999) 999-99-99"
    }).mask('#phoneNumber');

    Inputmask({
        mask: "9999 999999"
    }).mask('#passportData'); // Объединённое поле для серии и номера паспорта
};

