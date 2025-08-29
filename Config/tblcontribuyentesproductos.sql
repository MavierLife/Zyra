-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-08-2025 a las 00:07:07
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `zyra`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblcontribuyentesproductos`
--

CREATE TABLE `tblcontribuyentesproductos` (
  `UUIDProducto` bigint(20) UNSIGNED NOT NULL,
  `UUIDContribuyente` char(36) NOT NULL,
  `CodigoDeBarras` varchar(20) NOT NULL,
  `Descripcion` varchar(255) NOT NULL,
  `Existencias` int(11) NOT NULL DEFAULT 0,
  `PrecioVenta` decimal(10,2) NOT NULL,
  `CostoCompra` decimal(10,2) NOT NULL,
  `IDCategoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `tblcontribuyentesproductos`
--

INSERT INTO `tblcontribuyentesproductos` (`UUIDProducto`, `UUIDContribuyente`, `CodigoDeBarras`, `Descripcion`, `Existencias`, `PrecioVenta`, `CostoCompra`, `IDCategoria`) VALUES
(1, '1', '7478145845855', 'COCA COLA 2.5 LITROS', 10, 5.00, 2.00, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tblcontribuyentesproductos`
--
ALTER TABLE `tblcontribuyentesproductos`
  ADD PRIMARY KEY (`UUIDProducto`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tblcontribuyentesproductos`
--
ALTER TABLE `tblcontribuyentesproductos`
  MODIFY `UUIDProducto` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
