-- TecaVirtual - Script de Banco de Dados MySQL
-- Banco de dados: tecavirtual

CREATE DATABASE IF NOT EXISTS `tecavirtual` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tecavirtual`;

-- 1. Tabela de Usuários para Login Administrativo
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir usuário administrador padrão: admin@tecavirtual.com / admin123
-- A senha admin123 codificada em PHP password_hash é: $2y$10$gR0lH1S.X5255F4C74y2DeB/E8/e7gAtWzX6jB7669YpExQ5v6V7C
INSERT INTO `usuarios` (`username`, `password`) VALUES
('admin@tecavirtual.com', '$2y$10$gR0lH1S.X5255F4C74y2DeB/E8/e7gAtWzX6jB7669YpExQ5v6V7C')
ON DUPLICATE KEY UPDATE `id`=`id`;


-- 2. Tabela de Categorias
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir categorias pré-definidas
INSERT INTO `categorias` (`nome`) VALUES
('Administração'),
('Marketing'),
('Finanças & Economia'),
('Desenvolvimento Pessoal'),
('Tecnologia & Programação'),
('Liderança & Gestão'),
('Empreendedorismo'),
('Comunicação'),
('Ciência & Inovação'),
('Outros')
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`);


-- 3. Tabela de Livros (Acervo)
CREATE TABLE IF NOT EXISTS `livros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `author` VARCHAR(255) NOT NULL,
  `isbn` VARCHAR(50) DEFAULT NULL,
  `publisher` VARCHAR(255) DEFAULT NULL,
  `year` INT NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `status` ENUM('disponivel', 'emprestado', 'reservado') DEFAULT 'disponivel',
  `synopsis` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir livros padrão
INSERT INTO `livros` (`id`, `title`, `author`, `isbn`, `publisher`, `year`, `category`, `status`, `synopsis`) VALUES
(1, 'A Prática da Administração de Empresas', 'Peter Drucker', '978-8547214692', 'Saraiva Uni', 1954, 'Administração', 'disponivel', 'Obra clássica que define a administração moderna, analisando o papel do administrador, as exigências do cargo e a importância da inovação e da estratégia organizacional.'),
(2, 'Empresas Feitas para Vencer', 'Jim Collins', '978-8525062406', 'Editora Globo', 2001, 'Liderança & Gestão', 'emprestado', 'Jim Collins e sua equipe de pesquisa identificaram o que faz uma empresa comum se tornar excelente, gerando resultados duradouros que superam os concorrentes por décadas.'),
(3, 'Comece pelo Porquê', 'Simon Sinek', '978-8543106311', 'Editora Sextante', 2009, 'Liderança & Gestão', 'disponivel', 'Explora como líderes influentes pensam, agem e se comunicam a partir de um propósito interno claro (o Círculo Dourado), inspirando equipes e clientes de maneira autêntica.'),
(4, 'A Startup Enxuta', 'Eric Ries', '978-8535288599', 'Sextante', 2011, 'Empreendedorismo', 'disponivel', 'Defende a aplicação de métodos científicos no desenvolvimento de novos produtos e serviços, reduzindo desperdícios e encurtando ciclos focados em aprendizado validado.'),
(5, 'Marketing 5.0: Tecnologia para a Humanidade', 'Philip Kotler', '978-6555642056', 'Editora Sextante', 2021, 'Marketing', 'reservado', 'Mostra como os profissionais de marketing podem conciliar o avanço tecnológico (data-driven marketing, IA, internet das coisas) com a busca contínua pelas conexões humanas e sustentabilidade.')
ON DUPLICATE KEY UPDATE `id`=`id`;


-- 4. Tabela de Empréstimos
CREATE TABLE IF NOT EXISTS `emprestimos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT NOT NULL,
  `reader_name` VARCHAR(255) NOT NULL,
  `reader_contact` VARCHAR(255) NOT NULL,
  `loan_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE DEFAULT NULL,
  `status` ENUM('ativo', 'devolvido', 'atrasado') DEFAULT 'ativo',
  FOREIGN KEY (`book_id`) REFERENCES `livros`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir empréstimo padrão para demonstrar
INSERT INTO `emprestimos` (`id`, `book_id`, `reader_name`, `reader_contact`, `loan_date`, `due_date`, `status`) VALUES
(1, 2, 'Mariana Silva Santos', 'mariana.santos@email.com', '2026-05-20', '2026-06-03', 'ativo')
ON DUPLICATE KEY UPDATE `id`=`id`;
