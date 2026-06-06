<?php
/**
 * TecaVirtual - Arquivo de Configuração e Conexão com o Banco de Dados
 * Adapta-se automaticamente ao ambiente MySQL local (XAMPP / WAMP / MAMP)
 */

// Garantir que todos os erros do PHP sejam impressos em tela (evita tela branca silenciosa)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('DB_HOST', 'localhost');
define('DB_NAME', 'tecavirtual');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // 1. Tentar conectar ao MySQL Geral
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 2. Garantir que o banco 'tecavirtual' exista
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    // 3. Executar a criação das tabelas caso elas não existam (auto-instalação facilitadora)
    
    // Tabela de Usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Inserir usuário administrativo padrão se não houver registros
    $stmt = $pdo->query("SELECT COUNT(*) FROM `usuarios`");
    if ($stmt->fetchColumn() == 0) {
        $defaultPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO `usuarios` (`username`, `password`) VALUES (?, ?)");
        $stmtInsert->execute(['admin@tecavirtual.com', $defaultPasswordHash]);
    }

    // Tabela de Categorias
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categorias` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nome` VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB;");

    // Inserir categorias pré-definidas se vazia
    $stmt = $pdo->query("SELECT COUNT(*) FROM `categorias`");
    if ($stmt->fetchColumn() == 0) {
        $categoriasPadrão = [
            'Administração', 'Marketing', 'Finanças & Economia', 
            'Desenvolvimento Pessoal', 'Tecnologia & Programação', 
            'Liderança & Gestão', 'Empreendedorismo', 'Comunicação', 
            'Ciência & Inovação', 'Outros'
        ];
        $stmtInsert = $pdo->prepare("INSERT INTO `categorias` (`nome`) VALUES (?)");
        foreach ($categoriasPadrão as $cat) {
            $stmtInsert->execute([$cat]);
        }
    }

    // Tabela de Livros
    $pdo->exec("CREATE TABLE IF NOT EXISTS `livros` (
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
    ) ENGINE=InnoDB;");

    // Inserir livros padrão se vazia
    $stmt = $pdo->query("SELECT COUNT(*) FROM `livros`");
    if ($stmt->fetchColumn() == 0) {
        $livrosPadrão = [
            [
                'title' => 'A Prática da Administração de Empresas',
                'author' => 'Peter Drucker',
                'isbn' => '978-8547214692',
                'publisher' => 'Saraiva Uni',
                'year' => 1954,
                'category' => 'Administração',
                'status' => 'disponivel',
                'synopsis' => 'Obra clássica que define a administração moderna, analisando o papel do administrador, as exigências do cargo e a importância da inovação e da estratégia organizacional.'
            ],
            [
                'title' => 'Empresas Feitas para Vencer',
                'author' => 'Jim Collins',
                'isbn' => '978-8525062406',
                'publisher' => 'Editora Globo',
                'year' => 2001,
                'category' => 'Liderança & Gestão',
                'status' => 'emprestado',
                'synopsis' => 'Jim Collins e sua equipe de pesquisa identificaram o que faz uma empresa comum se tornar excelente, gerando resultados duradouros que superam os concorrentes por décadas.'
            ],
            [
                'title' => 'Comece pelo Porquê',
                'author' => 'Simon Sinek',
                'isbn' => '978-8543106311',
                'publisher' => 'Editora Sextante',
                'year' => 2009,
                'category' => 'Liderança & Gestão',
                'status' => 'disponivel',
                'synopsis' => 'Explora como líderes influentes pensam, agem e se comunicam a partir de um propósito interno claro (o Círculo Dourado), inspirando equipes e clientes de maneira autêntica.'
            ]
        ];

        $stmtInsert = $pdo->prepare("INSERT INTO `livros` (`title`, `author`, `isbn`, `publisher`, `year`, `category`, `status`, `synopsis`) VALUES (:title, :author, :isbn, :publisher, :year, :category, :status, :synopsis)");
        foreach ($livrosPadrão as $livro) {
            $stmtInsert->execute($livro);
        }
    }

    // Tabela de Empréstimos
    $pdo->exec("CREATE TABLE IF NOT EXISTS `emprestimos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `book_id` INT NOT NULL,
        `reader_name` VARCHAR(255) NOT NULL,
        `reader_contact` VARCHAR(255) NOT NULL,
        `loan_date` DATE NOT NULL,
        `due_date` DATE NOT NULL,
        `return_date` DATE DEFAULT NULL,
        `status` ENUM('ativo', 'devolvido', 'atrasado') DEFAULT 'ativo',
        FOREIGN KEY (`book_id`) REFERENCES `livros`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // Inserir primeiro empréstimo de demonstração se vazia
    $stmt = $pdo->query("SELECT COUNT(*) FROM `emprestimos`");
    if ($stmt->fetchColumn() == 0) {
        $stmtInsert = $pdo->prepare("INSERT INTO `emprestimos` (`book_id`, `reader_name`, `reader_contact`, `loan_date`, `due_date`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtInsert->execute([2, 'Mariana Silva Santos', 'mariana.santos@email.com', '2026-05-20', '2026-06-03', 'ativo']);
    }

} catch (PDOException $e) {
    die("<div style='background-color:#fee2e2; border:1px solid #fca5a5; padding:20px; font-family:sans-serif; border-radius:10px; max-width:600px; margin:40px auto; color:#991b1b;'>
            <h2 style='margin-top:0;'>Erro ao Conectar ao Banco de Dados</h2>
            <p>Não foi possível estabelecer uma conexão com o MySQL de forma segura.</p>
            <p><strong>Detalhes técnicos do erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr style='border:none; border-top:1px solid #fca5a5; margin:15px 0;'>
            <p style='font-size:13px; color:#7f1d1d;'><strong>Soluções recomendadas:</strong><br>
            1. Verifique se o seu servidor local MySQL (XAMPP / WampServer) está ativo.<br>
            2. Se o seu MySQL usa senha ou porta diferente do padrão, abra o arquivo <code>config.php</code> e ajuste as constantes <code>DB_USER</code> e <code>DB_PASS</code>.</p>
         </div>");
}

// Inicializar sessão para persistência
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
