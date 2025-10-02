-- phpMyAdmin SQL Dump
-- version 5.0.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-09-2025 a las 04:00:12
-- Versión del servidor: 10.4.14-MariaDB
-- Versión de PHP: 7.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `prestamobd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `dni` varchar(20) COLLATE utf8_spanish_ci NOT NULL,
  `first_name` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `last_name` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `gender` enum('masculino','femenino','','') COLLATE utf8_spanish_ci DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `province_id` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  `district_id` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  `address` varchar(160) COLLATE utf8_spanish_ci DEFAULT NULL,
  `mobile` varchar(32) COLLATE utf8_spanish_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ruc` varchar(20) COLLATE utf8_spanish_ci DEFAULT NULL,
  `company` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  `loan_status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `customers`
--

INSERT INTO `customers` (`id`, `dni`, `first_name`, `last_name`, `gender`, `department_id`, `province_id`, `district_id`, `address`, `mobile`, `phone`, `user_id`, `ruc`, `company`, `loan_status`) VALUES
(8, '12345678', 'pepe', 'pepito', 'masculino', NULL, NULL, NULL, '', '', '', NULL, '', '', 1),
(9, '344555', 'sebas', 'marin', 'femenino', 10, '1003', '100311', '', '', '', NULL, '', '', 1),
(10, '12344', 'dobby', 'marin', 'masculino', 10, '1002', '100202', '', '', '', NULL, '', '', 1),
(11, '123451', 'cesar', 'ramos', 'masculino', 12, '1203', '120303', '', '', '', NULL, '', '', 1),
(12, '7654321', 'martin', 'francisco', 'femenino', 11, '1103', '110304', '', '', '', NULL, '', '', 1),
(13, '1223', 'prueba', 'prueba07', 'masculino', 10, '1002', '100202', '', '', '', NULL, '', '', 1),
(14, '777777777', 'Prueba23', 'Prueba23', 'masculino', 1, '0101', '010103', '', '', '', NULL, '', '', 1),
(15, '189878982', 'Zeus', 'Chavez', 'femenino', 3, '0301', '030104', '', '', '', NULL, '', '', 1),
(16, '134564356', 'luisa', 'ramirez', 'masculino', 2, '0201', '020102', '', '', '', NULL, '', '', 1),
(17, '115268456', 'Maariana', 'Vasquez', 'masculino', 4, '0401', '040102', '', '', '', NULL, '', '', 1),
(18, '136783981', 'Luz', 'Ayda', 'femenino', 1, '0101', '010104', '', '', '', NULL, '', '', 1),
(19, '123456', 'esteban', 'marin', 'masculino', 2, NULL, '020101', 'choqwur n455', '23232', '3017614353', NULL, '', 'premex', 1),
(20, '123', 'sssssssss', 'ssssssssss', 'masculino', 1, '0101', '010101', 'asasa', 'sasa', 'asa', NULL, '', '', 1),
(21, '123', 'qwqwq', 'qwqwq', 'masculino', 1, '0101', '010101', 'qqqq', '3312323', '23232', NULL, '', '', 0),
(22, '111111', 'qwqwqwq', 'qwqwq', 'masculino', 1, '0101', '010102', 'qwqwqwq', '12121', '212', NULL, '', '', 1),
(23, '2222', 'qwqwqw', 'qwqwqw', 'masculino', 1, NULL, '010102', 'asasas', '12121', '2122', 3, '', '', 1),
(24, '33333', 'Esteban', 'Ramos', 'masculino', 1, NULL, '010101', '', '', '', 2, '', '', 1),
(0, '888888', 'Esteban', 'Esteban', 'masculino', 1, '0101', '010101', 'choqwur n455', '', '', 2, '', '', 0);

--
-- Estructura de tabla para la tabla `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `num_prestamo` int(11) NOT NULL DEFAULT 0,
  `customer_id` int(11) NOT NULL,
  `credit_amount` int(11) NOT NULL,
  `interest_amount` decimal(10,2) NOT NULL,
  `num_fee` int(11) NOT NULL,
  `payment_m` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `coin_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `amortization_type` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `assigned_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Estructura de tabla para la tabla `loan_items`
--

CREATE TABLE `loan_items` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `num_quota` int(11) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `interest_amount` decimal(10,2) NOT NULL,
  `capital_amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Estructura de tabla para la tabla `coins`
--

CREATE TABLE `coins` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `short_name` varchar(10) COLLATE utf8_spanish_ci NOT NULL,
  `symbol` varchar(5) COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `coins`
--

INSERT INTO `coins` (`id`, `name`, `short_name`, `symbol`) VALUES
(1, 'Peso Colombiano', 'COP', '$');

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_spanish_ci NOT NULL,
  `first_name` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `last_name` varchar(150) COLLATE utf8_spanish_ci NOT NULL,
  `perfil` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `estado` int(11) NOT NULL DEFAULT 1,
  `fecha` datetime DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `perfil`, `estado`, `fecha`, `ultimo_login`) VALUES
(1, 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Admin', 'admin', 1, '2025-09-27 04:00:12', NULL),
(2, 'operador@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operador', 'Uno', 'operador', 1, '2025-09-27 04:00:12', NULL),
(3, 'viewer@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Viewer', 'Uno', 'viewer', 1, '2025-09-27 04:00:12', NULL);

--
-- Estructura de tabla para la tabla `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_name` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `value` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Estructura de tabla para la tabla `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Estructura de tabla para la tabla `provinces`
--

CREATE TABLE `provinces` (
  `id` varchar(10) COLLATE utf8_spanish_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Estructura de tabla para la tabla `districts`
--

CREATE TABLE `districts` (
  `id` varchar(10) COLLATE utf8_spanish_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_spanish_ci NOT NULL,
  `province_id` varchar(10) COLLATE utf8_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `province_id` (`province_id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `coin_id` (`coin_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `assigned_user_id` (`assigned_user_id`);

--
-- Indices de la tabla `loan_items`
--
ALTER TABLE `loan_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indices de la tabla `coins`
--
ALTER TABLE `coins`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Indices de la tabla `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indices de la tabla `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `province_id` (`province_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `loan_items`
--
ALTER TABLE `loan_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `coins`
--
ALTER TABLE `coins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `provinces`
--
ALTER TABLE `provinces`
  MODIFY `id` varchar(10) COLLATE utf8_spanish_ci NOT NULL;

--
-- AUTO_INCREMENT de la tabla `districts`
--
ALTER TABLE `districts`
  MODIFY `id` varchar(10) COLLATE utf8_spanish_ci NOT NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
