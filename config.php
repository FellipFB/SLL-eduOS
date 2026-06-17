<?php
/**
 * TecaVirtual - Arquivo de Configuração e Conexão com o Banco de Dados (Raiz)
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

function loadDatabaseFromSqlFile($pdo) {
    $possible_paths = [
        __DIR__ . '/db.sql',
        __DIR__ . '/php_version/db.sql',
        dirname(__DIR__) . '/php_version/db.sql',
        __DIR__ . '/../php_version/db.sql'
    ];
    
    $sql_file = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $sql_file = $path;
            break;
        }
    }
    
    if (!$sql_file) {
        return false;
    }
    
    try {
        $sql = file_get_contents($sql_file);
        $isSQLite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        
        // Remove comments
        $sql = preg_replace('/(--.*)|(\/\*(.|\s)*?\*\/)/', '', $sql);
        
        // Split by semicolon
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) {
                continue;
            }
            
            if ($isSQLite) {
                if (stripos($statement, 'CREATE DATABASE') !== false || stripos($statement, 'USE ') !== false) {
                    continue;
                }
                $statement = preg_replace('/ENGINE\s*=\s*\w+/i', '', $statement);
                $statement = preg_replace('/DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $statement);
                $statement = preg_replace('/COLLATE\s*=\s*\w+/i', '', $statement);
                
                if (stripos($statement, 'ON DUPLICATE KEY UPDATE') !== false) {
                    $statement = preg_replace('/INSERT INTO/i', 'INSERT OR IGNORE INTO', $statement);
                    $statement = preg_replace('/ON DUPLICATE KEY UPDATE.*/is', '', $statement);
                    $statement = trim($statement);
                }
            }
            
            try {
                $pdo->exec($statement);
            } catch (PDOException $ex) {
                if (!$isSQLite) {
                    throw $ex;
                }
            }
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function ensureDefaultAdminAccounts($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `adm_usuarios` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;");

        $stmt = $pdo->prepare("INSERT IGNORE INTO `adm_usuarios` (`name`, `email`, `password`) VALUES (?, ?, ?)");
        $stmt->execute([
            'Administrador Geral',
            'admin@tecavirtual.com',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
        $stmt->execute([
            'Rony Admin',
            'rony@tecavirtual.com',
            password_hash('rony123', PASSWORD_DEFAULT)
        ]);
    } catch (Exception $e) {
        // Ignore; the login page will report DB issues if the database is still unavailable.
    }
}

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

    // 3. Verificar se as tabelas já existem, senão executa ou importa db.sql automaticamente
    $tablesExist = false;
    try {
        $stmtCheck = $pdo->query("SELECT 1 FROM `livros` LIMIT 1");
        if ($stmtCheck !== false) {
            $tablesExist = true;
        }
    } catch (PDOException $e) {
        $tablesExist = false;
    }

    if (!$tablesExist) {
        $imported = loadDatabaseFromSqlFile($pdo);
        if (!$imported) {
            // Fallback manual de emergência caso db.sql não exista ou falhe
            
            // Tabela de Usuários (Leitores)
            $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;");

            // Tabela de Administradores
            $pdo->exec("CREATE TABLE IF NOT EXISTS `adm_usuarios` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(100) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;");

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
        }
    }

    ensureDefaultAdminAccounts($pdo);

} catch (PDOException $e) {
    // Caso falhe o MySQL (ex: offline no localhost), tentamos SQLite para robustez total out-of-the-box
    try {
        $db_file = __DIR__ . '/tecavirtual.db';
        if (basename(__DIR__) === 'php_version') {
            $db_file = __DIR__ . '/tecavirtual.db';
        } else {
            $db_file = __DIR__ . '/php_version/tecavirtual.db';
        }
        
        $pdo = new PDO("sqlite:" . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON;");
        
        $tablesExist = false;
        try {
            $stmtCheck = $pdo->query("SELECT 1 FROM `livros` LIMIT 1");
            if ($stmtCheck !== false) {
                $tablesExist = true;
            }
        } catch (Exception $err) {
            $tablesExist = false;
        }
        
        if (!$tablesExist) {
            loadDatabaseFromSqlFile($pdo);
        }
    } catch (PDOException $sqlite_error) {
        die("<div style='background-color:#fee2e2; border:1px solid #fca5a5; padding:20px; font-family:sans-serif; border-radius:10px; max-width:600px; margin:40px auto; color:#991b1b;'>
                <h2 style='margin-top:0;'>Erro ao Conectar ao Banco de Dados</h2>
                <p>Não foi possível estabelecer uma conexão com o MySQL de forma automática e o fallback de robustez local via SQLite falhou.</p>
                <p><strong>Detalhes técnicos do erro original:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <hr style='border:none; border-top:1px solid #fca5a5; margin:15px 0;'>
                <p style='font-size:13px; color:#7f1d1d;'><strong>Soluções recomendadas no seu localhost:</strong><br>
                1. Verifique se o seu servidor local MySQL (XAMPP / WampServer) está ativo.<br>
                2. Certifique-se de que as constantes de conexão em <code>config.php</code> condizem com seu ambiente local.</p>
             </div>");
    }
}

ensureDefaultAdminAccounts($pdo);

// Inicializar sessão para persistência
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
