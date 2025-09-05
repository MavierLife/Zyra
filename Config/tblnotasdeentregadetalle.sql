-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-09-2025 a las 01:02:28
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
-- Estructura de tabla para la tabla `tblnotasdeentregadetalle`
--

CREATE TABLE `tblnotasdeentregadetalle` (
  `UUIDDetalleVenta` varchar(200) NOT NULL,
  `UUIDVenta` varchar(200) DEFAULT NULL,
  `FechaRegistro` datetime DEFAULT current_timestamp(),
  `UsuarioRegistro` varchar(200) DEFAULT NULL,
  `NumeroDocumento` varchar(200) DEFAULT NULL,
  `NoItem` int(11) NOT NULL DEFAULT 0,
  `TipoDeItem` int(11) DEFAULT 1,
  `CodigoPROD` varchar(200) DEFAULT NULL,
  `CodigoBarra` varchar(200) DEFAULT NULL,
  `Concepto` text DEFAULT NULL,
  `TV` int(11) NOT NULL DEFAULT 1,
  `UnidadDeMedida` int(11) NOT NULL DEFAULT 99,
  `Cantidad` decimal(10,4) DEFAULT 0.0000,
  `UnidadesVendidas` int(11) NOT NULL DEFAULT 0,
  `PrecioVenta` decimal(10,4) DEFAULT 0.0000,
  `PrecioVentaSinImpuesto` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `PrecioNormal` decimal(10,2) DEFAULT 0.00,
  `PrecioSugeridoVenta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Descuento` decimal(10,2) DEFAULT 0.00,
  `VentaNoSujeta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `VentaExenta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `VentaGravada` decimal(10,2) NOT NULL DEFAULT 0.00,
  `VentaGravadaSinImpuesto` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `TotalImporte` decimal(10,2) NOT NULL DEFAULT 0.00,
  `TotalOperacion` decimal(10,2) NOT NULL DEFAULT 0.00,
  `IVAItem` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `PrecioCosto` decimal(10,4) DEFAULT 0.0000,
  `TotalCosto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `PagaImpuesto` int(11) NOT NULL DEFAULT 1,
  `PorcentajeImpuesto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `CodigoTributo` varchar(10) DEFAULT NULL,
  `Tributo` varchar(10) DEFAULT NULL,
  `FechaUpdate` datetime DEFAULT NULL,
  `UsuarioUpdate` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `tblnotasdeentregadetalle`
--

INSERT INTO `tblnotasdeentregadetalle` (`UUIDDetalleVenta`, `UUIDVenta`, `FechaRegistro`, `UsuarioRegistro`, `NumeroDocumento`, `NoItem`, `TipoDeItem`, `CodigoPROD`, `CodigoBarra`, `Concepto`, `TV`, `UnidadDeMedida`, `Cantidad`, `UnidadesVendidas`, `PrecioVenta`, `PrecioVentaSinImpuesto`, `PrecioNormal`, `PrecioSugeridoVenta`, `Descuento`, `VentaNoSujeta`, `VentaExenta`, `VentaGravada`, `VentaGravadaSinImpuesto`, `TotalImporte`, `TotalOperacion`, `IVAItem`, `PrecioCosto`, `TotalCosto`, `PagaImpuesto`, `PorcentajeImpuesto`, `CodigoTributo`, `Tributo`, `FechaUpdate`, `UsuarioUpdate`) VALUES
('87fbab11-31bd-4cbe-94d1-c38534249741', '3ab371c2-b42c-43ec-8e5a-4e655738c82f', '2025-09-04 13:40:25', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('263740b5-1238-4d26-bd73-e00f02cdada7', 'cca10210-290a-46a3-b118-6fbf6159f562', '2025-09-04 13:43:01', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('8adf8e58-ecf7-48b0-aa5d-08d32ef1b69b', '3b017ff2-37b1-437b-88f7-0d12c530de8a', '2025-09-04 13:43:49', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('8d2627e5-1b18-422c-aaa2-84f1587676b3', 'd981bbb4-ebc0-4615-97bf-2da1f1974236', '2025-09-04 13:44:53', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('4fbdfde2-09d3-4c25-b80d-9ff039d612d2', 'a0282f4f-01d1-45a9-ba40-880f64ebcaff', '2025-09-04 13:45:54', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '10', NULL, 'ARROZ BLANCO 1 LIBRA', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('7bf595e7-9b7b-4358-afa9-c93046f91e8a', 'f2c865f9-186d-4e22-9163-54c8ac0b8c7a', '2025-09-04 14:10:57', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '7', NULL, 'CERVEZA PILSENER 355ML', 1, 99, 1.0000, 0, 1.2500, 1.1062, 0.00, 0.00, 0.00, 0.00, 0.00, 1.25, 1.1062, 1.25, 0.00, 0.1438, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('af99dd2c-c070-49d8-a187-f24e227a72e1', 'f2c865f9-186d-4e22-9163-54c8ac0b8c7a', '2025-09-04 14:10:57', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('b9db2da3-5692-453d-9ce6-fabebc3f3980', 'f2c865f9-186d-4e22-9163-54c8ac0b8c7a', '2025-09-04 14:10:57', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('db6b891e-3d8e-4ffb-922c-367433665a96', '3c84a0e5-9338-464a-bae4-1c0e9d4fb32c', '2025-09-04 14:45:08', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('5398f139-2115-4060-a1d3-dfb4645d2489', '70cfff66-6ef7-4289-a2d1-7350aea1ca65', '2025-09-04 14:49:05', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('d3240c4d-fe5d-4122-8709-650a494de4cc', 'fd6023f4-f4ff-485b-b4f8-f34ecba3553c', '2025-09-04 15:21:33', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('76963f18-bacc-475c-9724-f5c842e7960b', '082dcad4-c904-4eed-847d-788fa007595c', '2025-09-04 15:53:24', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('87fbab11-31bd-4cbe-94d1-c38534249741', '3ab371c2-b42c-43ec-8e5a-4e655738c82f', '2025-09-04 13:40:25', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('263740b5-1238-4d26-bd73-e00f02cdada7', 'cca10210-290a-46a3-b118-6fbf6159f562', '2025-09-04 13:43:01', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('8adf8e58-ecf7-48b0-aa5d-08d32ef1b69b', '3b017ff2-37b1-437b-88f7-0d12c530de8a', '2025-09-04 13:43:49', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('8d2627e5-1b18-422c-aaa2-84f1587676b3', 'd981bbb4-ebc0-4615-97bf-2da1f1974236', '2025-09-04 13:44:53', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('4fbdfde2-09d3-4c25-b80d-9ff039d612d2', 'a0282f4f-01d1-45a9-ba40-880f64ebcaff', '2025-09-04 13:45:54', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '10', NULL, 'ARROZ BLANCO 1 LIBRA', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('7bf595e7-9b7b-4358-afa9-c93046f91e8a', 'f2c865f9-186d-4e22-9163-54c8ac0b8c7a', '2025-09-04 14:10:57', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '7', NULL, 'CERVEZA PILSENER 355ML', 1, 99, 1.0000, 0, 1.2500, 1.1062, 0.00, 0.00, 0.00, 0.00, 0.00, 1.25, 1.1062, 1.25, 0.00, 0.1438, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('af99dd2c-c070-49d8-a187-f24e227a72e1', 'f2c865f9-186d-4e22-9163-54c8ac0b8c7a', '2025-09-04 14:10:57', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('b9db2da3-5692-453d-9ce6-fabebc3f3980', 'f2c865f9-186d-4e22-9163-54c8ac0b8c7a', '2025-09-04 14:10:57', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('db6b891e-3d8e-4ffb-922c-367433665a96', '3c84a0e5-9338-464a-bae4-1c0e9d4fb32c', '2025-09-04 14:45:08', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('5398f139-2115-4060-a1d3-dfb4645d2489', '70cfff66-6ef7-4289-a2d1-7350aea1ca65', '2025-09-04 14:49:05', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('d3240c4d-fe5d-4122-8709-650a494de4cc', 'fd6023f4-f4ff-485b-b4f8-f34ecba3553c', '2025-09-04 15:21:33', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '1', NULL, 'COCA COLA 2.5 LITROS', 1, 99, 1.0000, 0, 1.5000, 1.3274, 0.00, 0.00, 0.00, 0.00, 0.00, 1.50, 1.3274, 1.50, 0.00, 0.1726, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL),
('76963f18-bacc-475c-9724-f5c842e7960b', '082dcad4-c904-4eed-847d-788fa007595c', '2025-09-04 15:53:24', 'Edwin Antonio Coto Benavides', NULL, 0, 1, '2', NULL, 'PEPSI COLA 2 LITROS', 1, 99, 1.0000, 0, 4.5000, 3.9823, 0.00, 0.00, 0.00, 0.00, 0.00, 4.50, 3.9823, 4.50, 0.00, 0.5177, 0.0000, 0.00, 1, 0.00, NULL, NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
