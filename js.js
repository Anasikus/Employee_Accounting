// Универсальная функция для открытия/закрытия модальных окон
function toggleModal(modalId) {
	var modal = document.getElementById(modalId);
	if (modal) {
		modal.style.display = modal.style.display === "none" || modal.style.display === "" ? "block" : "none";
	} else {
		console.error('Модальное окно с идентификатором ' + modalId + ' не найдено.');
	}
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

function addEmployee() {
	const formData = new FormData(document.getElementById('employeeForm'));
	formData.append('action', 'addEmployee'); // Добавляем параметр action для серверной логики

	fetch('employeeAccounting.php', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.error) {
				showNotification(data.message);
			} else {
				showNotification('Сотрудник успешно добавлен!');
				toggleModal('employeeModal'); // Закрываем модальное окно добавления сотрудника
				document.getElementById('employeeForm').reset();

				// Обновляем таблицу данных сотрудников сразу после добавления
				fetchEmployeeData();
			}
		})
		.catch(error => console.error('Ошибка при добавлении сотрудника:', error));
}

function updateDropdowns() {
	fetch('employeeAccounting.php?action=getDropdownData')
		.then(response => response.json())
		.then(data => {
			document.getElementById('department').innerHTML = generateOptions(data.departments, 'idDepartment', 'address');
			document.getElementById('post').innerHTML = generateOptions(data.posts, 'idPost', 'titlePost');
			document.getElementById('status').innerHTML = generateOptions(data.statuses, 'idStatus', 'titleStatus');
		})
		.catch(error => console.error('Ошибка при обновлении выпадающих списков:', error));
}

function generateOptions(dataArray, idField, textField) {
	return dataArray.map(item => `<option value="${item[idField]}">${item[textField]}</option>`).join('');
}

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
                    <td>
                        <select onchange="updateEmployeePost(${employee.id}, this.value)">
                            ${getPostOptions(employee.post)}
                        </select>
                    </td>
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
let currentEmployeeId = null;

function showDetails(employeeId) {
	currentEmployeeId = employeeId; // Присваиваем значение ID текущего сотрудника
	fetch(`getEmployeeData.php?id=${employeeId}`)
		.then(response => response.json())
		.then(data => {
			if (data.error) {
				showNotification(data.message);
			} else {
				// Заполняем форму в модальном окне данными сотрудника
				document.getElementById('editSurname').value = data.surname;
				document.getElementById('editName').value = data.name;
				document.getElementById('editPatronymic').value = data.patronymic;
				document.getElementById('editDateOfBirth').value = data.dateOfBirth;
				document.getElementById('editPassportData').value = `${data.passportSeries} ${data.passportNumber}`;
				document.getElementById('editAddressResidential').value = data.addressResidential;
				document.getElementById('editEmail').value = data.email;
				document.getElementById('editPhoneNumber').value = data.phoneNumber;

				// Обновляем выпадающие списки (Отдел, Должность, Статус)
				document.getElementById('editDepartment').innerHTML = generateOptions(data.departments, 'idDepartment', 'address');
				document.getElementById('editPost').innerHTML = generateOptions(data.posts, 'idPost', 'titlePost');
				document.getElementById('editStatus').innerHTML = generateOptions(data.statuses, 'idStatus', 'titleStatus');

				// Устанавливаем текущие значения для выпадающих списков
				document.getElementById('editDepartment').value = data.idDepartment;
				document.getElementById('editPost').value = data.idPost;
				document.getElementById('editStatus').value = data.idStatus;

				// Открываем модальное окно для редактирования
				toggleModal('editEmployeeModal');
			}
		})
		.catch(error => {
			console.error('Ошибка при загрузке данных сотрудника:', error);
		});
}

// Функция для обновления статуса сотрудника
function updateEmployeeStatus(employeeId, newStatus) {
	const formData = new FormData();
	formData.append('action', 'updateStatus');
	formData.append('id', employeeId);
	formData.append('status', newStatus);

	fetch('updateStatus.php', { // Путь к вашему обработчику статуса
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
		mask: "+7 (999) 999-99-99"
	}).mask('#editPhoneNumber');
	Inputmask({
		mask: "9999 999999"
	}).mask('#passportData'); // Объединённое поле для серии и номера паспорта
	Inputmask({
		mask: "9999 999999"
	}).mask('#editPassportData');
};

function resetFilters() {
	// Очищаем поля ФИО
	document.getElementById('searchSurname').value = '';
	document.getElementById('searchName').value = '';
	document.getElementById('searchPatronymic').value = '';

	// Запрашиваем все данные без фильтров
	fetchEmployeeData(); // Вызов функции для получения всех данных
}

function resetFiltersDP() {
	// Устанавливаем значение "Все" для выпадающих списков
	document.getElementById('searchDepartment').value = ''; // Значение для "Все" обычно пустое
	document.getElementById('searchPost').value = ''; // Значение для "Все" обычно пустое

	// После сброса фильтров загружаем все данные сотрудников
	fetchEmployeeData();
}


function fetchEmployeeData() {
	const surname = document.getElementById('searchSurname').value || '';
	const name = document.getElementById('searchName').value || '';
	const patronymic = document.getElementById('searchPatronymic').value || '';

	// Отправляем запрос на сервер для получения данных сотрудников
	$.post('employeeAccounting.php', {
		action: 'searchEmployee',
		surname: surname,
		name: name,
		patronymic: patronymic
	}, function(response) {
		const result = JSON.parse(response);
		const tbody = document.querySelector('tbody');
		tbody.innerHTML = ''; // Очищаем предыдущие данные

		if (result.length > 0) {
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
                    <td>
                        <select onchange="updateEmployeePost(${employee.id}, this.value)">
                            ${getPostOptions(employee.post)}
                        </select>
                    </td>
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
			const row = document.createElement('tr');
			row.innerHTML = `<td colspan="11">Сотрудников не найдено</td>`;
			tbody.appendChild(row);
		}
	});
}

function applyDepartmentPostFilter() {
	const departmentId = document.getElementById('searchDepartment').value;
	const postId = document.getElementById('searchPost').value;

	$.post('employeeAccounting.php', {
		action: 'filterByDepartmentPost',
		departmentId: departmentId,
		postId: postId
	}, function(response) {
		const result = JSON.parse(response);
		const tbody = document.querySelector('tbody');
		tbody.innerHTML = ''; // Очищаем предыдущие данные

		if (result.length > 0) {
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
                    <td>
                        <select onchange="updateEmployeePost(${employee.id}, this.value)">
                            ${getPostOptions(employee.post)}
                        </select>
                    </td>
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
			const row = document.createElement('tr');
			row.innerHTML = `<td colspan="11">Сотрудников не найдено</td>`;
			tbody.appendChild(row);
		}
	});
}


function displayEmployeeData(data) {
	const employeeDataDiv = document.getElementById('employeeData');
	employeeDataDiv.innerHTML = ''; // Очищаем старые данные

	if (data.length > 0) {
		const table = document.createElement('table');
		const headerRow = table.insertRow();
		['№', 'Фамилия', 'Имя', 'Отчество', 'Дата рождения', 'Дата принятия', 'Отдел', 'Должность', 'Зарплата', 'Изменить должность'].forEach(header => {
			const cell = headerRow.insertCell();
			cell.textContent = header;
		});

		data.forEach(employee => {
			const row = table.insertRow();
			['id', 'surname', 'name', 'patronymic', 'dateOfBirth', 'dateOfEmployment', 'department', 'post', 'salary'].forEach(field => {
				const cell = row.insertCell();
				cell.textContent = employee[field];
			});

			// Добавляем выпадающий список для изменения должности
			const selectCell = row.insertCell();
			selectCell.innerHTML = `<select onchange="updateEmployeePost(${employee.id}, this.value)">
                ${getPostOptions(employee.post)}
            </select>`;
		});

		employeeDataDiv.appendChild(table); // Добавляем таблицу в DOM
	} else {
		employeeDataDiv.textContent = "Сотрудников не найдено.";
	}
}


function updateEmployeePost(employeeId, newPostId) {
    const formData = new FormData();
    formData.append('action', 'updatePost');
    formData.append('id', employeeId);
    formData.append('postId', newPostId);

    fetch('updatePost.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Должность сотрудника обновлена.');

            // Обновляем только ячейку с должностью для данного сотрудника
            const postCell = document.querySelector(`tr[data-id="${employeeId}"] select[name="post"]`);
            postCell.value = newPostId;  // обновляем значение должности в селекте
        } else {
            console.error('Ошибка при изменении должности:', data.message);
        }
    })
    .catch(error => console.error('Ошибка при изменении должности:', error));
}

function updateEmployee() {
	if (!currentEmployeeId) {
		console.error('ID сотрудника не определён');
		showNotification('Ошибка: Не выбран сотрудник для обновления.');
		return;
	}

	const formData = new FormData(document.getElementById('editEmployeeForm'));
	formData.append('action', 'updateEmployee');
	formData.append('id', currentEmployeeId); // Передаем ID сотрудника

	// Выводим данные в консоль для проверки
	for (let [key, value] of formData.entries()) {
		console.log(`${key}: ${value}`);
	}

	fetch('employeeAccounting.php', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.error) {
				showNotification(data.message);
			} else {
				showNotification('Данные сотрудника успешно обновлены!');
				toggleModal('editEmployeeModal');
				fetchEmployeeData(); // Обновляем таблицу сотрудников
			}
		})
		.catch(error => {
			console.error('Ошибка при обновлении данных сотрудника:', error);
		});
}