-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Окт 14 2024 г., 20:47
-- Версия сервера: 8.0.30
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `employeeAccounting`
--

-- --------------------------------------------------------

--
-- Структура таблицы `Department`
--

CREATE TABLE `Department` (
  `idDepartment` int NOT NULL,
  `address` text,
  `workingHours` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `Department`
--

INSERT INTO `Department` (`idDepartment`, `address`, `workingHours`) VALUES
(1, 'Улица Урицкого, дом 29', 12),
(2, 'Улица Победы, дом 53А', 8),
(3, 'Улица Пирогова, д. 56', 10);

-- --------------------------------------------------------

--
-- Структура таблицы `Employee`
--

CREATE TABLE `Employee` (
  `idEmployee` int NOT NULL,
  `surname` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `patronymic` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `dateOfBirth` date DEFAULT NULL,
  `dateOfEmployment` date DEFAULT NULL,
  `idPersonalData` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `Employee`
--

INSERT INTO `Employee` (`idEmployee`, `surname`, `name`, `patronymic`, `dateOfBirth`, `dateOfEmployment`, `idPersonalData`) VALUES
(1, 'Коршунов', 'Алексей', 'Дмитриевич', '2002-09-17', '2024-09-01', NULL),
(2, 'Егоров', 'Иван', 'Алексеевич', '2001-09-24', '2023-09-02', NULL),
(3, 'Драгунов', 'Владислав', 'Владимирович', '1990-03-15', '2023-08-01', NULL),
(108, 'Иссек', 'Владимир', 'Андреевич', '1010-10-10', '1010-10-10', 105),
(109, '1', '2', '3', '2121-03-12', '1212-12-12', 106),
(110, '123', '145', '167', '1111-11-11', '1111-11-11', 107),
(111, '66', '66', '66', '1111-11-11', '1111-11-11', 108),
(112, '5', '5', '5', '5122-05-05', '1211-12-12', 109),
(113, 'Туркин', 'Арсений', 'Владиславович', '2003-12-12', '2024-10-10', 110);

-- --------------------------------------------------------

--
-- Структура таблицы `employeeAccounting`
--

CREATE TABLE `employeeAccounting` (
  `idRecords` int NOT NULL,
  `idEmployee` int DEFAULT NULL,
  `idPersonalData` int DEFAULT NULL,
  `idDepartment` int DEFAULT NULL,
  `idPost` int DEFAULT NULL,
  `idStatus` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `employeeAccounting`
--

INSERT INTO `employeeAccounting` (`idRecords`, `idEmployee`, `idPersonalData`, `idDepartment`, `idPost`, `idStatus`) VALUES
(1, 1, 1, 1, 2, 2),
(2, 2, 2, 2, 2, 1),
(102, 108, 105, 1, 3, 1),
(103, 109, 106, 1, 1, 1),
(104, 110, 107, 1, 1, 1),
(105, 111, 108, 1, 1, 1),
(106, 112, 109, 1, 1, 1),
(107, 113, 110, 1, 2, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `personalData`
--

CREATE TABLE `personalData` (
  `idPersonalData` int NOT NULL,
  `passportSeries` int DEFAULT NULL,
  `passportNumber` int DEFAULT NULL,
  `addressResidential` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `phoneNumber` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `personalData`
--

INSERT INTO `personalData` (`idPersonalData`, `passportSeries`, `passportNumber`, `addressResidential`, `email`, `phoneNumber`) VALUES
(1, 2312, 455355, 'Улица Первомайская. 45Д', 'AlekseyKorshunov@mail.ru', '+7 (890) 332-21-55'),
(2, 2832, 123221, 'Улица Свободы, д. 43', 'IvanEgorov@gmail.com', '88005553535'),
(105, 6319, 65702, 'г.Саратов', 'kirigayakazyto082@gamil.com', '+7 (987) 356-32-97'),
(106, 6319, 65702, 'Екатеринбург', 'kirigayakazyto0812@gamil.com', '+7 (798) 735-63-29'),
(107, 2222, 222222, '12311', '13222@gmail.com', '+7 (777) 777-77-77'),
(108, 4444, 44444, 'г.Саратов', '132444444444@gmail.com', '+7 (444) 444-44-44'),
(109, 2221, 12221, '222121', '22121221212@g.com', '+7 (999) 999-99-99'),
(110, 7895, 463645, 'Екатеринбург', 'Davydik.tv108@yandex.ru', '+7 (784) 246-87-45');

-- --------------------------------------------------------

--
-- Структура таблицы `Post`
--

CREATE TABLE `Post` (
  `idPost` int NOT NULL,
  `titlePost` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `hourlyRate` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `Post`
--

INSERT INTO `Post` (`idPost`, `titlePost`, `hourlyRate`) VALUES
(1, 'Тестировщик', 360),
(2, 'Web-дизайнер', 280),
(3, 'Frontend-Разработчик', 150);

-- --------------------------------------------------------

--
-- Структура таблицы `Status`
--

CREATE TABLE `Status` (
  `idStatus` int NOT NULL,
  `titleStatus` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `Status`
--

INSERT INTO `Status` (`idStatus`, `titleStatus`) VALUES
(1, 'Работает'),
(2, 'Уволен'),
(3, 'В отпуске');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `Department`
--
ALTER TABLE `Department`
  ADD PRIMARY KEY (`idDepartment`);

--
-- Индексы таблицы `Employee`
--
ALTER TABLE `Employee`
  ADD PRIMARY KEY (`idEmployee`),
  ADD KEY `idPersonalData` (`idPersonalData`);

--
-- Индексы таблицы `employeeAccounting`
--
ALTER TABLE `employeeAccounting`
  ADD PRIMARY KEY (`idRecords`),
  ADD KEY `idEmployee` (`idEmployee`,`idPost`,`idStatus`),
  ADD KEY `idStatus` (`idStatus`),
  ADD KEY `idPost` (`idPost`),
  ADD KEY `idPersonalData` (`idPersonalData`),
  ADD KEY `idDepartment` (`idDepartment`);

--
-- Индексы таблицы `personalData`
--
ALTER TABLE `personalData`
  ADD PRIMARY KEY (`idPersonalData`);

--
-- Индексы таблицы `Post`
--
ALTER TABLE `Post`
  ADD PRIMARY KEY (`idPost`);

--
-- Индексы таблицы `Status`
--
ALTER TABLE `Status`
  ADD PRIMARY KEY (`idStatus`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `Department`
--
ALTER TABLE `Department`
  MODIFY `idDepartment` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `Employee`
--
ALTER TABLE `Employee`
  MODIFY `idEmployee` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT для таблицы `employeeAccounting`
--
ALTER TABLE `employeeAccounting`
  MODIFY `idRecords` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT для таблицы `personalData`
--
ALTER TABLE `personalData`
  MODIFY `idPersonalData` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT для таблицы `Post`
--
ALTER TABLE `Post`
  MODIFY `idPost` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `Employee`
--
ALTER TABLE `Employee`
  ADD CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`idPersonalData`) REFERENCES `personalData` (`idPersonalData`);

--
-- Ограничения внешнего ключа таблицы `employeeAccounting`
--
ALTER TABLE `employeeAccounting`
  ADD CONSTRAINT `employeeaccounting_ibfk_1` FOREIGN KEY (`idEmployee`) REFERENCES `Employee` (`idEmployee`),
  ADD CONSTRAINT `employeeaccounting_ibfk_3` FOREIGN KEY (`idPost`) REFERENCES `Post` (`idPost`),
  ADD CONSTRAINT `employeeaccounting_ibfk_4` FOREIGN KEY (`idStatus`) REFERENCES `Status` (`idStatus`),
  ADD CONSTRAINT `employeeaccounting_ibfk_5` FOREIGN KEY (`idDepartment`) REFERENCES `Department` (`idDepartment`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
