-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/10/2025 às 15:06
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `controle_visitante`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `visitante`
--

CREATE TABLE `visitante` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `secao` varchar(100) NOT NULL,
  `ramal` varchar(30) NOT NULL,
  `autorizado` varchar(100) NOT NULL,
  `carro_entrou` varchar(3) NOT NULL,
  `placa` varchar(8) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_saida` datetime DEFAULT NULL,
  `icea_autorizado_id` int(11) DEFAULT NULL,
  `icea_periodo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estrutura para cadastro permanente de autorizados ICEA

CREATE TABLE `icea_autorizado` (
  `id` int(11) NOT NULL,
  `posto_graduacao` varchar(60) NOT NULL,
  `nome_guerra` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `om_origem` varchar(100) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `excluido_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Histórico de períodos de acesso ICEA

CREATE TABLE `icea_periodo_acesso` (
  `id` int(11) NOT NULL,
  `autorizado_id` int(11) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  CONSTRAINT `chk_icea_periodo_datas` CHECK (`data_fim` >= `data_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `visitante`
--

INSERT INTO `visitante` (`id`, `nome`, `cpf`, `secao`, `ramal`, `autorizado`, `carro_entrou`, `placa`, `modelo`, `data_hora`, `data_saida`) VALUES
(1, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6659', 'TEN Romeu', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-22 13:48:23', '2025-09-29 16:00:11'),
(2, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6659', 'TEN Romeu', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-22 15:04:39', '2025-09-29 16:00:11'),
(3, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6659', 'TEN Romeu', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-22 16:21:04', '2025-09-29 16:00:11'),
(4, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6659', 'TEN Romeu', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-22 16:32:53', '2025-09-29 16:00:11'),
(5, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6659', 'TEN Romeu', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-22 16:32:56', '2025-09-29 16:00:11'),
(6, 'jose', '537.116.038-82', 'STI', '6659', 'TEN Romeu', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-22 16:36:36', '2025-09-29 16:00:11'),
(7, 'Cap Gilvan', '123.456.789-00', 'SAU', '6716', 'CEL GILVAN', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 12:20:22', '2025-09-29 16:00:11'),
(8, 'Cap Gilvandgsdgsdgsdg', '123.456.789-00', 'SAU', '6716', 'CEL GILVAN', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:50:33', '2025-09-29 16:00:11'),
(9, 'Cap Gilvandgsdgsdgsdg', '123.456.789-00', 'SAU', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:50:43', '2025-09-29 16:00:11'),
(10, 'Cap Gilvandgsdgsdgsdg', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:50:52', '2025-09-29 16:00:11'),
(11, 'Cap Gilvandgsdgsdgsdsdfsdfsdfsdfsdfsdg', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:53:07', '2025-09-29 16:00:11'),
(12, 'skskskskkss', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:53:21', '2025-09-29 16:00:11'),
(13, 'jon', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:53:55', '2025-09-29 16:00:11'),
(14, 'jair', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:54:10', '2025-09-29 16:00:11'),
(15, 'donal', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:57:11', '2025-09-29 16:00:11'),
(16, 'yuri', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'CEL GILVANsdfsdfsdf', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:57:32', '2025-09-29 16:00:11'),
(17, 'yuri', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:57:53', '2025-09-29 16:00:11'),
(18, 'yuri', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-26 14:59:15', '2025-09-29 16:00:11'),
(19, 'jairo', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 17:28:36', '2025-09-29 16:00:11'),
(20, 'jairo', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 17:28:59', '2025-09-29 16:00:11'),
(21, 'jairo', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 17:30:49', '2025-09-29 16:00:11'),
(22, 'jairo', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 17:31:13', '2025-09-29 16:00:11'),
(23, 'rafael', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 18:11:32', '2025-09-29 16:00:11'),
(24, 'abril', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 18:48:48', '2025-09-29 16:00:11'),
(25, 'abril', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 18:49:07', '2025-09-29 16:00:11'),
(26, 'abril', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 18:49:23', '2025-09-29 16:00:11'),
(27, 'romero', '123.456.789-00', 'SAUsfsdfsdfdsf', '6716', 'jaiss', 'sim', 'GCK-8E85', 'ONIX LTZ', '2025-09-29 19:02:49', '2025-09-29 16:03:02'),
(28, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '5465', 'CEL GILVAN', 'sim', 'GCK-8E85', 'fffffffff', '2025-09-29 19:37:43', '2025-09-29 16:39:09'),
(29, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '5465', 'CEL GILVAN', 'sim', 'GCK-8E85', 'fffffffff', '2025-09-29 19:37:43', '2025-09-29 16:39:09'),
(30, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6716', 'TEN Romeu', '', '', '', '2025-09-29 19:38:16', '2025-09-29 16:39:07'),
(31, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6716', 'TEN Romeu', 'nao', '', '', '2025-09-29 19:38:16', '2025-09-29 16:39:08'),
(32, 'jose', '537.116.038-82', 'STI', '6659', 'jaiss', 'nao', '', '', '2025-09-29 19:39:22', '2025-09-29 16:39:34'),
(33, 'maria', '537.116.038-82', 'STI', '6659', 'fffffffffffffff', 'nao', '', '', '2025-09-29 19:42:03', '2025-09-29 16:42:35'),
(34, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6659', 'CEL GILVAN', 'nao', '', '', '2025-09-29 19:42:53', '2025-09-29 16:43:35'),
(35, 'joginho', '123.456.789-00', 'SAU', '6659', 'CEL GILVAN', 'nao', '', '', '2025-09-30 15:14:31', '2025-09-30 12:21:53'),
(36, 'marcos', '315.165.651-65', 'SSG', '5555', 'LAIS ', 'nao', '', '', '2025-09-30 15:17:12', '2025-09-30 12:17:44'),
(37, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'SSG', '6716', 'LAIS ', 'nao', '', '', '2025-09-30 15:22:10', '2025-09-30 12:22:28'),
(38, 'SGT Sobral', '123.855.668-41', 'SDA', '6660', 'CAP Miguel', 'sim', 'ddk55547', 'POSCHE', '2025-09-30 16:51:19', '2025-09-30 13:51:42'),
(39, 'CB De Moura', '564.848.125-62', 'SDA', '6660', 'LAIS ', 'nao', '', '', '2025-10-01 13:06:58', '2025-10-01 10:07:23'),
(40, 'Ten Romeu', '576.727.277-17', 'SSG', '5465', 'Cel Gilvan', 'nao', '', '', '2025-10-01 13:10:05', '2025-10-01 10:10:20'),
(41, 'SGT César', '265.465.468-43', 'GAB', '6685', 'CV Sandra ', 'sim', 'GAY-1224', 'PICASSO', '2025-10-01 17:09:13', '2025-10-01 14:10:09'),
(42, 'DEISE', '456.456.456-45', 'GAB', '4545', 'TEN Romeu', 'nao', '', '', '2025-10-01 17:16:16', '2025-10-01 14:26:34'),
(43, 'Jhonata Gusmão de Freitas', '537.116.038-82', 'STI', '6660', 'TEN Romeu', 'nao', '', '', '2025-10-01 17:32:21', '2025-10-01 14:32:44'),
(44, 'Cb De Moura', '549.849.847-57', 'SDA', '2728', 'Cel Gilvan', 'nao', '', '', '2025-10-01 18:10:31', '2025-10-01 15:11:02'),
(45, 'jose', '123.456.789-00', 'STI', '6659', 'TEN Romeu', 'nao', '', '', '2025-10-01 18:35:12', '2025-10-01 15:35:17'),
(46, 'Jose da silva ', '123.456.789-10', 'CGI', '3256', 'cel Cesar', 'nao', '', '', '2025-10-03 14:09:08', '2025-10-06 09:41:07'),
(47, 'Gusmão', '564.561.516-51', 'sks', '4444', 'LAIS ', 'nao', '', '', '2025-10-06 15:06:38', '2025-10-06 13:24:00'),
(48, 'SGT FLÁVIO', '125.879.642-66', 'CGOV', '6659', '2S IRINEU', 'sim', 'GCK-8E8', 'PICASSO', '2025-10-06 19:02:55', '2025-10-06 16:09:42'),
(49, 'JHONATA GUSMÃO DE FREITAS', '266.479.542-14', 'ACS', '6660', 'Maj SAKAJIRI', 'sim', 'GCK-8E8', 'ONIX LTZ', '2025-10-07 14:54:48', '2025-10-07 12:14:58'),
(50, 'JACARE', '123.456.789-00', 'CGI', '5656', 'Cel ANDRADE', 'nao', '', '', '2025-10-07 15:15:56', '2025-10-07 12:16:20'),
(51, 'JHONATA GUSMÃO DE FREITAS', '222.222.222-22', 'SDA', '6685', '2S VILA NOVA', 'sim', 'FFFFFFF', 'PICASSO', '2025-10-07 18:07:28', '2025-10-09 09:55:45');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `visitante`
--
ALTER TABLE `visitante`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_visitante_icea_autorizado` (`icea_autorizado_id`),
  ADD KEY `ix_visitante_icea_periodo` (`icea_periodo_id`);

-- Índices e vínculos das tabelas ICEA
ALTER TABLE `icea_autorizado`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_icea_autorizado_cpf` (`cpf`);

ALTER TABLE `icea_periodo_acesso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_icea_periodo_autorizado_datas` (`autorizado_id`, `data_inicio`, `data_fim`),
  ADD CONSTRAINT `fk_icea_periodo_autorizado`
    FOREIGN KEY (`autorizado_id`) REFERENCES `icea_autorizado` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `visitante`
  ADD CONSTRAINT `fk_visitante_icea_autorizado`
    FOREIGN KEY (`icea_autorizado_id`) REFERENCES `icea_autorizado` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_visitante_icea_periodo`
    FOREIGN KEY (`icea_periodo_id`) REFERENCES `icea_periodo_acesso` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `visitante`
--
ALTER TABLE `visitante`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

ALTER TABLE `icea_autorizado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `icea_periodo_acesso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
