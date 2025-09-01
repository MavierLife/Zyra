-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-09-2025 a las 17:02:05
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
-- Estructura de tabla para la tabla `tblcontribuyentes`
--

CREATE TABLE `tblcontribuyentes` (
  `UUIDContribuyente` bigint(20) UNSIGNED NOT NULL,
  `IDActividad` varchar(10) NOT NULL,
  `CodEstable` varchar(10) NOT NULL,
  `Correo` varchar(150) NOT NULL,
  `NIT` varchar(20) NOT NULL,
  `RazonSocial` varchar(200) DEFAULT NULL,
  `NombreComercial` varchar(100) NOT NULL,
  `UltimoNumFactura` int(11) NOT NULL DEFAULT 0,
  `UltimoNumCreditoFiscal` int(11) NOT NULL DEFAULT 0,
  `UltimoNumNotaDeCredito` int(11) NOT NULL DEFAULT 0,
  `UltimoNumSujetoExcluido` int(11) NOT NULL DEFAULT 0,
  `URLFirmador` text DEFAULT NULL,
  `URLAutenticacion` text DEFAULT NULL,
  `URLRecepcion` text DEFAULT NULL,
  `ClavePrivada` varchar(200) DEFAULT NULL,
  `ClavePublica` varchar(200) DEFAULT NULL,
  `ClaveAPI` varchar(200) DEFAULT NULL,
  `URLRecepcionLOTE` text DEFAULT NULL,
  `URLConsultarDTE` text DEFAULT NULL,
  `URLContingencia` text DEFAULT NULL,
  `URLAnularDTE` text DEFAULT NULL,
  `AmbienteDTE` enum('00','01') NOT NULL,
  `TipoEstablecimiento` varchar(10) DEFAULT NULL,
  `NRC` varchar(20) NOT NULL,
  `Telefono` varchar(20) NOT NULL,
  `DireccionComplemento` varchar(255) NOT NULL,
  `DireccionDepartamento` varchar(5) NOT NULL,
  `DireccionMunicipio` varchar(5) NOT NULL,
  `FechaRegistro` timestamp NOT NULL DEFAULT current_timestamp(),
  `UsuarioRegistro` varchar(100) DEFAULT NULL,
  `TrasmiteElectronica` tinyint(1) NOT NULL DEFAULT 0,
  `idcurrency` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tblcontribuyentes`
--

INSERT INTO `tblcontribuyentes` (`UUIDContribuyente`, `IDActividad`, `CodEstable`, `Correo`, `NIT`, `RazonSocial`, `NombreComercial`, `UltimoNumFactura`, `UltimoNumCreditoFiscal`, `UltimoNumNotaDeCredito`, `UltimoNumSujetoExcluido`, `URLFirmador`, `URLAutenticacion`, `URLRecepcion`, `ClavePrivada`, `ClavePublica`, `ClaveAPI`, `URLRecepcionLOTE`, `URLConsultarDTE`, `URLContingencia`, `URLAnularDTE`, `AmbienteDTE`, `TipoEstablecimiento`, `NRC`, `Telefono`, `DireccionComplemento`, `DireccionDepartamento`, `DireccionMunicipio`, `FechaRegistro`, `UsuarioRegistro`, `TrasmiteElectronica`, `idcurrency`) VALUES
(1, '771', 'CM01', 'facturacionelectronica@grupobenavides.com', '12171309861013', 'Edwin Antonio Coto Benavides', 'Benamax', 0, 0, 0, 0, 'http://localhost:8114/firmardocumento/', 'https://api.dtes.mh.gob.sv/seguridad/auth', 'https://api.dtes.mh.gob.sv/fesv/recepciondte', '848600Coto*', 'Jc2828permiso*', 'API8486*', 'https://api.dtes.mh.gob.sv/fesv/recepcionlote/', 'https://api.dtes.mh.gob.sv/fesv/recepcion/consultadte/', 'https://api.dtes.mh.gob.sv/fesv/contingencia', 'https://api.dtes.mh.gob.sv/fesv/anulardte', '00', '01', '1757811', '76973748', 'CASERIO LA GOLONDRINA', '12', '12', '2025-08-28 14:43:19', 'Antonio Hernandez', 0, '4');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tblcontribuyentes`
--
ALTER TABLE `tblcontribuyentes`
  ADD PRIMARY KEY (`UUIDContribuyente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tblcontribuyentes`
--
ALTER TABLE `tblcontribuyentes`
  MODIFY `UUIDContribuyente` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
