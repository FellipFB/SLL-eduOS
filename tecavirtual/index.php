<?php
/**
 * TecaVirtual - Painel de Controle Principal Administrativo
 */
require_once 'config.php';

// Se não estiver autenticado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inicializar mensagens de feedback do usuário
$success_message = '';
$error_message = '';

// Definição de guias ativas (Tabs)
$activeTab = $_GET['tab'] ?? 'acervo'; // 'acervo', 'locacao', 'categorias'

// --- TRATAMENTO DE REQUISIÇÕES (POST/AÇÕES) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Cadastrar Livro
    if ($action === 'add_book') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $publisher = trim($_POST['publisher'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $category = $_POST['category'] ?? '';
        $status = $_POST['status'] ?? 'disponivel';
        $synopsis = trim($_POST['synopsis'] ?? '');

        // Validação
        if (empty($title) || empty($author) || empty($publisher)) {
            $error_message = 'Título, autor e editora são obrigatórios.';
        } elseif ($year < 1000 || $year > (date('Y') + 2)) {
            $error_message = 'Ano de publicação inválido.';
        } else {
            // Validar ISBN se preenchido
            $isbnClean = preg_replace('/[-\s]/', '', $isbn);
            if (!empty($isbnClean) && strlen($isbnClean) !== 10 && strlen($isbnClean) !== 13) {
                $error_message = 'O ISBN deve conter exatamente 10 ou 13 dígitos.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO `livros` (`title`, `author`, `isbn`, `publisher`, `year`, `category`, `status`, `synopsis`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $author, $isbn, $publisher, $year, $category, $status, $synopsis]);
                    $success_message = 'Livro "' . htmlspecialchars($title) . '" cadastrado com sucesso!';
                } catch (PDOException $e) {
                    $error_message = 'Erro ao cadastrar livro: ' . $e->getMessage();
                }
            }
        }
    }

    // 2. Editar Livro
    if ($action === 'edit_book') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $publisher = trim($_POST['publisher'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $category = $_POST['category'] ?? '';
        $status = $_POST['status'] ?? 'disponivel';
        $synopsis = trim($_POST['synopsis'] ?? '');

        // Validação
        if ($id <= 0 || empty($title) || empty($author) || empty($publisher)) {
            $error_message = 'O preenchimento de campos obrigatórios é necessário para alteração.';
        } elseif ($year < 1000 || $year > (date('Y') + 2)) {
            $error_message = 'Ano de publicação inválido.';
        } else {
            // Validar ISBN se preenchido
            $isbnClean = preg_replace('/[-\s]/', '', $isbn);
            if (!empty($isbnClean) && strlen($isbnClean) !== 10 && strlen($isbnClean) !== 13) {
                $error_message = 'O ISBN deve conter exatamente 10 ou 13 dígitos.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE `livros` SET `title` = ?, `author` = ?, `isbn` = ?, `publisher` = ?, `year` = ?, `category` = ?, `status` = ?, `synopsis` = ? WHERE `id` = ?");
                    $stmt->execute([$title, $author, $isbn, $publisher, $year, $category, $status, $synopsis, $id]);
                    $success_message = 'O livro "' . htmlspecialchars($title) . '" foi atualizado com sucesso.';
                    // Redireciona para tirar o edit_id da URL
                    header("Location: index.php?tab=acervo&status=edited");
                    exit;
                } catch (PDOException $e) {
                    $error_message = 'Erro ao editar livro: ' . $e->getMessage();
                }
            }
        }
    }

    // 3. Excluir Livro
    if ($action === 'delete_book') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                // Obter título do livro antes de deletar
                $stmtTitle = $pdo->prepare("SELECT `title` FROM `livros` WHERE `id` = ?");
                $stmtTitle->execute([$id]);
                $bookTitle = $stmtTitle->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM `livros` WHERE `id` = ?");
                $stmt->execute([$id]);
                $success_message = 'Volume "' . htmlspecialchars($bookTitle) . '" removido com sucesso!';
            } catch (PDOException $e) {
                $error_message = 'Erro ao remover livro: ' . $e->getMessage();
            }
        }
    }

    // 4. Cadastrar Categoria
    if ($action === 'add_category') {
        $nome = trim($_POST['nome'] ?? '');
        if (empty($nome)) {
            $error_message = 'Escreva o nome da categoria.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO `categorias` (`nome`) VALUES (?)");
                $stmt->execute([$nome]);
                $success_message = 'Categoria "' . htmlspecialchars($nome) . '" adicionada!';
            } catch (PDOException $e) {
                $error_message = 'Erro ao adicionar categoria ou item já existente.';
            }
        }
    }

    // 5. Excluir Categoria
    if ($action === 'delete_category') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmtName = $pdo->prepare("SELECT `nome` FROM `categorias` WHERE `id` = ?");
                $stmtName->execute([$id]);
                $catName = $stmtName->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM `categorias` WHERE `id` = ?");
                $stmt->execute([$id]);
                $success_message = 'Categoria "' . htmlspecialchars($catName) . '" removida.';
            } catch (PDOException $e) {
                $error_message = 'Erro ao remover categoria.';
            }
        }
    }

    // 6. Conceder Empréstimo
    if ($action === 'add_loan') {
        $book_id = intval($_POST['book_id'] ?? 0);
        $reader_name = trim($_POST['reader_name'] ?? '');
        $reader_contact = trim($_POST['reader_contact'] ?? '');
        $due_date = $_POST['due_date'] ?? '';

        if ($book_id <= 0 || empty($reader_name) || empty($reader_contact) || empty($due_date)) {
            $error_message = 'Por favor, preencha todos os campos do empréstimo.';
        } else {
            try {
                // Verificar status do livro
                $stmtBook = $pdo->prepare("SELECT `status`, `title` FROM `livros` WHERE `id` = ?");
                $stmtBook->execute([$book_id]);
                $book = $stmtBook->fetch();

                if (!$book) {
                    $error_message = 'Livro selecionado inválido.';
                } elseif ($book['status'] !== 'disponivel') {
                    $error_message = 'Este livro não está disponível para novos empréstimos no momento.';
                } else {
                    // Iniciar Transação
                    $pdo->beginTransaction();

                    $loan_date = date('Y-m-d');
                    
                    // Inserir registro de empréstimo
                    $stmtLoan = $pdo->prepare("INSERT INTO `emprestimos` (`book_id`, `reader_name`, `reader_contact`, `loan_date`, `due_date`, `status`) VALUES (?, ?, ?, ?, ?, 'ativo')");
                    $stmtLoan->execute([$book_id, $reader_name, $reader_contact, $loan_date, $due_date]);

                    // Alterar status do livro
                    $stmtUpdateBook = $pdo->prepare("UPDATE `livros` SET `status` = 'emprestado' WHERE `id` = ?");
                    $stmtUpdateBook->execute([$book_id]);

                    $pdo->commit();
                    $success_message = 'Empréstimo de "' . htmlspecialchars($book['title']) . '" concedido para ' . htmlspecialchars($reader_name) . '.';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error_message = 'Erro ao conceder empréstimo: ' . $e->getMessage();
            }
        }
    }

    // 7. Devolver Livro
    if ($action === 'return_book') {
        $loan_id = intval($_POST['loan_id'] ?? 0);
        $book_id = intval($_POST['book_id'] ?? 0);

        if ($loan_id > 0 && $book_id > 0) {
            try {
                // Iniciar Transação
                $pdo->beginTransaction();

                // Atualizar empréstimo
                $return_date = date('Y-m-d');
                $stmtLoan = $pdo->prepare("UPDATE `emprestimos` SET `status` = 'devolvido', `return_date` = ? WHERE `id` = ?");
                $stmtLoan->execute([$return_date, $loan_id]);

                // Atualizar livro
                $stmtBook = $pdo->prepare("UPDATE `livros` SET `status` = 'disponivel' WHERE `id` = ?");
                $stmtBook->execute([$book_id]);

                $pdo->commit();
                $success_message = 'Obra devolvida e liberada com sucesso no catálogo!';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error_message = 'Erro ao finalizar devolução: ' . $e->getMessage();
            }
        }
    }
}

// Pegar status de edição resolvido via GET anterior
if (isset($_GET['status']) && $_GET['status'] === 'edited') {
    $success_message = 'O livro foi atualizado com sucesso.';
}

// --- BUSCAS E FILTRAGENS ---
try {
    // Categorias cadastradas para selects/filtros
    $categorias = $pdo->query("SELECT * FROM `categorias` ORDER BY `nome` ASC")->fetchAll();
    $array_categorias_nomes = array_column($categorias, 'nome');

    // Contagem dos cards gerais (Estatísticas unificadas)
    $totalBooksCount = $pdo->query("SELECT COUNT(*) FROM `livros`")->fetchColumn();
    $availableBooksCount = $pdo->query("SELECT COUNT(*) FROM `livros` WHERE `status` = 'disponivel'")->fetchColumn();
    $borrowedBooksCount = $pdo->query("SELECT COUNT(*) FROM `livros` WHERE `status` = 'emprestado'")->fetchColumn();
    $totalCategoriesCount = count($categorias);

    // Filtros da aba de acervo
    $searchQuery = $_GET['search'] ?? '';
    $selectedCategory = $_GET['category_filter'] ?? 'Todas';
    $selectedStatus = $_GET['status_filter'] ?? 'Todos';

    // Construção da consulta de livros com filtros
    $sqlBooks = "SELECT * FROM `livros` WHERE 1=1";
    $paramsBooks = [];

    if (!empty($searchQuery)) {
        $sqlBooks .= " AND (`title` LIKE :search OR `author` LIKE :search OR `isbn` LIKE :search OR `publisher` LIKE :search)";
        $paramsBooks[':search'] = '%' . $searchQuery . '%';
    }
    if ($selectedCategory !== 'Todas') {
        $sqlBooks .= " AND `category` = :category";
        $paramsBooks[':category'] = $selectedCategory;
    }
    if ($selectedStatus !== 'Todos') {
        $sqlBooks .= " AND `status` = :status";
        $paramsBooks[':status'] = $selectedStatus;
    }
    $sqlBooks .= " ORDER BY `title` ASC";

    $stmtBooks = $pdo->prepare($sqlBooks);
    $stmtBooks->execute($paramsBooks);
    $livros = $stmtBooks->fetchAll();

    // Listagem de Empréstimos com nomes de livros conectados
    $sqlLoans = "SELECT e.*, l.title AS book_title 
                 FROM `emprestimos` e
                 JOIN `livros` l ON e.book_id = l.id";

    $loanSearch = $_GET['loan_search'] ?? '';
    $filterActiveOnly = isset($_GET['active_only']) ? intval($_GET['active_only']) : 1;
    $paramsLoans = [];

    $conditionsLoans = [];
    if (!empty($loanSearch)) {
        $conditionsLoans[] = "(e.reader_name LIKE :search OR e.reader_contact LIKE :search OR l.title LIKE :search)";
        $paramsLoans[':search'] = '%' . $loanSearch . '%';
    }
    if ($filterActiveOnly === 1) {
        $conditionsLoans[] = "e.status = 'ativo'";
    }

    if (!empty($conditionsLoans)) {
        $sqlLoans .= " WHERE " . implode(" AND ", $conditionsLoans);
    }
    $sqlLoans .= " ORDER BY e.id DESC";

    $stmtLoans = $pdo->prepare($sqlLoans);
    $stmtLoans->execute($paramsLoans);
    $loans = $stmtLoans->fetchAll();

    // Se houver comando de edição de livro, carregar livro específico
    $editingBook = null;
    $edit_id = intval($_GET['edit_id'] ?? 0);
    if ($edit_id > 0) {
        $stmtEdit = $pdo->prepare("SELECT * FROM `livros` WHERE `id` = ?");
        $stmtEdit->execute([$edit_id]);
        $editingBook = $stmtEdit->fetch();
    }
} catch (PDOException $e) {
    die("<div style='background-color:#fffbeb; border:1px solid #fef3c7; padding:24px; font-family:sans-serif; border-radius:12px; max-width:650px; margin:50px auto; color:#92400e; box-shadow: 0 4px 20px rgba(0,0,0,0.05);'>
            <h2 style='margin-top:0; color:#78350f;'>Divergência de Estrutura do Banco de Dados</h2>
            <p>Sua conexão com o MySQL foi estabelecida com sucesso. No entanto, houve um erro ao consultar as tabelas do sistema.</p>
            <p>Isso geralmente ocorre se o banco de dados <code>tecavirtual</code> já existia na sua máquina com uma estrutura de tabelas incompatível ou vazia.</p>
            <p><strong>Detalhes técnicos:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr style='border:none; border-top:1px solid #fef3c7; margin:20px 0;'>
            <p style='font-size:14px; font-weight:bold; color:#78350f; margin-bottom:8px;'>Como resolver isso de forma rápida:</p>
            <ol style='font-size:13px; line-height:1.6; margin-top:0; padding-left:20px; color:#78350f;'>
                <li>Importe as tabelas corretas usando o arquivo <code>db.sql</code> fornecido no projeto.</li>
                <li><strong>OU</strong> de maneira limpa: Acesse o phpMyAdmin ou seu cliente SQL, remova (DROP DATABASE) o banco de dados <code>tecavirtual</code> existente e recarregue a página. O sistema recriará todo o acervo e tabelas de exemplo automaticamente!</li>
            </ol>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle | TecaVirtual</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '#102a55',
                            secondary: '#1e4ca5',
                            dark: '#102a55',
                            light: '#5a6f95',
                            border: '#d2deed',
                            accent: '#4ade80'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'Georgia', 'serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #fbfbf9;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }
        .editorial-shadow {
            box-shadow: 0 10px 30px rgba(16, 42, 85, 0.04);
        }
    </style>
</head>
<body class="selection:bg-brand-primary/10">

    <div class="w-full max-w-7xl mx-auto px-4 py-8 space-y-8" id="main-layout">
        
        <!-- Header / Top Navbar -->
        <header id="dashboard-navbar" class="bg-[#fdfdfc] px-8 py-5 rounded-2xl border border-brand-border editorial-shadow flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5" id="navbar-logo-area">
                <div class="w-11 h-11 bg-brand-accent rounded-lg flex items-center justify-center text-brand-dark shrink-0 shadow-sm font-serif font-black text-2xl" id="logo-icon-box">
                    T
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-extrabold text-brand-dark tracking-tight font-sans">
                            Teca<span class="text-brand-secondary">Virtual</span>
                        </h1>
                        <span class="text-[9px] bg-brand-primary/5 text-brand-primary border border-brand-border/60 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                            Painel Admin
                        </span>
                    </div>
                    <p class="text-[10px] text-brand-light font-bold uppercase tracking-widest mt-0.5">Gestão de Acervo e Catalogação</p>
                </div>
            </div>

            <div class="flex items-center gap-4 justify-between md:justify-end" id="navbar-user-area">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-brand-dark">Administrador Geral</p>
                    <p class="text-xs text-brand-light italic"><?= htmlspecialchars($_SESSION['username']) ?></p>
                </div>
                <a
                    href="logout.php"
                    id="btn-logout"
                    class="flex items-center gap-2 px-5 py-2.5 border border-brand-border hover:border-red-200 hover:text-red-700 rounded-full text-xs font-bold uppercase tracking-wider transition text-[#566e92] cursor-pointer hover:bg-red-50/50"
                    title="Encerrar sessão"
                >
                    <!-- LogOut Icon -->
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Sair</span>
                </a>
            </div>
        </header>

        <!-- Dynamic Feedback alerts -->
        <?php if (!empty($success_message)): ?>
            <div class="bg-emerald-50 text-emerald-800 border-l-4 border-emerald-500 rounded-r-xl p-4 text-xs font-medium transition-all" role="alert">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= htmlspecialchars($success_message) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 text-red-800 border-l-4 border-red-500 rounded-r-xl p-4 text-xs font-medium transition-all" role="alert">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span><?= htmlspecialchars($error_message) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bento Grid Statistics Dashboard Panel -->
        <section id="bento-statistics" class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <!-- Stat 1 -->
            <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow flex flex-col justify-between hover:border-brand-primary/20 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[9px] uppercase font-bold tracking-widest text-[#566e92]">Títulos Catalogados</span>
                    <div class="p-2 bg-indigo-50 text-indigo-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-serif text-brand-dark font-normal tracking-tight leading-none"><?= $totalBooksCount ?></h3>
                    <p class="text-[10px] text-brand-light mt-1 uppercase tracking-wider font-mono">Volumes e Registros</p>
                </div>
            </div>

            <!-- Stat 2 -->
            <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow flex flex-col justify-between hover:border-brand-primary/20 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[9px] uppercase font-bold tracking-widest text-[#566e92]">Disponíveis</span>
                    <div class="p-2 bg-emerald-50 text-emerald-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-serif text-emerald-700 font-normal tracking-tight leading-none"><?= $availableBooksCount ?></h3>
                    <p class="text-[10px] text-emerald-600/90 mt-1 uppercase tracking-wider font-mono">Prontos para Locar</p>
                </div>
            </div>

            <!-- Stat 3 -->
            <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow flex flex-col justify-between hover:border-brand-primary/20 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[9px] uppercase font-bold tracking-widest text-[#566e92]">Locações Ativas</span>
                    <div class="p-2 bg-amber-50 text-amber-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-serif text-amber-700 font-normal tracking-tight leading-none"><?= $borrowedBooksCount ?></h3>
                    <p class="text-[10px] text-amber-600/90 mt-1 uppercase tracking-wider font-mono">Em circulação externa</p>
                </div>
            </div>

            <!-- Stat 4 -->
            <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow flex flex-col justify-between hover:border-brand-primary/20 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[9px] uppercase font-bold tracking-widest text-[#566e92]">Categorias</span>
                    <div class="p-2 bg-blue-50 text-blue-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-serif text-brand-dark font-normal tracking-tight leading-none"><?= $totalCategoriesCount ?></h3>
                    <p class="text-[10px] text-brand-light mt-1 uppercase tracking-wider font-mono">Divisões de Acervo</p>
                </div>
            </div>
        </section>

        <!-- Navigation Tabs Selector -->
        <nav class="flex border-b border-brand-border/40 pb-0.5 gap-6" id="dashboard-tab-navigation">
            <a 
                href="?tab=acervo" 
                class="pb-4 px-1 border-b-2 text-xs font-bold uppercase tracking-widest transition-all gap-2 flex items-center <?= $activeTab === 'acervo' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-brand-light hover:text-brand-dark' ?>"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span>Acervo e Registros</span>
            </a>
            <a 
                href="?tab=locacao" 
                class="pb-4 px-1 border-b-2 text-xs font-bold uppercase tracking-widest transition-all gap-2 flex items-center <?= $activeTab === 'locacao' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-brand-light hover:text-brand-dark' ?>"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Gestão de Empréstimos</span>
            </a>
            <a 
                href="?tab=categorias" 
                class="pb-4 px-1 border-b-2 text-xs font-bold uppercase tracking-widest transition-all gap-2 flex items-center <?= $activeTab === 'categorias' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-brand-light hover:text-brand-dark' ?>"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Gêneros & Categorias</span>
            </a>
        </nav>

        <!-- Render active view based on tab selection -->
        <?php if ($activeTab === 'acervo'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="view-acervo">
                
                <!-- 1. LEFT COLUMN: Books List Table with Full Search + Filters -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white rounded-2xl border border-brand-border editorial-shadow p-6">
                        
                        <!-- Search and filter controls -->
                        <form method="GET" class="space-y-4 mb-6">
                            <input type="hidden" name="tab" value="acervo">
                            
                            <div class="flex flex-col md:flex-row gap-3">
                                <div class="relative flex-1">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </span>
                                    <input 
                                        type="text" 
                                        name="search" 
                                        value="<?= htmlspecialchars($searchQuery) ?>" 
                                        placeholder="Buscar título, autor, editora ou ISBN..."
                                        class="w-full pl-11 pr-4 py-2 bg-[#f8fafc] border border-brand-border/60 focus:border-brand-primary outline-none text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0]"
                                    />
                                </div>
                                
                                <div class="flex gap-2">
                                    <select 
                                        name="category_filter" 
                                        class="px-3 py-2 bg-[#f8fafc] border border-brand-border/60 rounded-xl text-xs text-brand-dark focus:border-brand-primary outline-none"
                                        onchange="this.form.submit()"
                                    >
                                        <option value="Todas" <?= $selectedCategory === 'Todas' ? 'selected' : '' ?>>Todas as Categorias</option>
                                        <?php foreach ($array_categorias_nomes as $cat_name): ?>
                                            <option value="<?= htmlspecialchars($cat_name) ?>" <?= $selectedCategory === $cat_name ? 'selected' : '' ?>><?= htmlspecialchars($cat_name) ?></option>
                                        <?php foreach ($categorias as $cat): ?>
                                        <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </select>

                                    <select 
                                        name="status_filter" 
                                        class="px-3 py-2 bg-[#f8fafc] border border-brand-border/60 rounded-xl text-xs text-brand-dark focus:border-brand-primary outline-none"
                                        onchange="this.form.submit()"
                                    >
                                        <option value="Todos" <?= $selectedStatus === 'Todos' ? 'selected' : '' ?>>Todos os Status</option>
                                        <option value="disponivel" <?= $selectedStatus === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                                        <option value="emprestado" <?= $selectedStatus === 'emprestado' ? 'selected' : '' ?>>Emprestado</option>
                                        <option value="reservado" <?= $selectedStatus === 'reservado' ? 'selected' : '' ?>>Reservado</option>
                                    </select>

                                    <button type="submit" class="px-4 py-2 bg-brand-primary hover:bg-[#153468] text-white text-xs font-bold rounded-xl transition">
                                        Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Books Table / Grid -->
                        <?php if (empty($livros)): ?>
                            <div class="py-12 text-center text-brand-light" id="empty-state">
                                <svg class="w-10 h-10 text-brand-light/50 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <p class="text-xs font-bold">Nenhum livro localizado</p>
                                <p class="text-[11px] mt-1 leading-relaxed text-slate-400">Tente ajustar seus termos de busca e os seletores de filtros.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="border-b border-brand-border/40 text-[10px] text-slate-400 font-bold uppercase tracking-widest bg-slate-50/50">
                                            <th class="py-3 px-4">Título / Autor</th>
                                            <th class="py-3 px-4">Gênero</th>
                                            <th class="py-3 px-4">Editora / Ano</th>
                                            <th class="py-3 px-4">Status</th>
                                            <th class="py-3 px-4 text-center">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-brand-border/35">
                                        <?php foreach ($livros as $row): ?>
                                            <tr class="hover:bg-slate-50/70 transition-colors">
                                                <td class="py-4 px-4">
                                                    <div class="font-bold text-brand-dark text-[13px]"><?= htmlspecialchars($row['title']) ?></div>
                                                    <div class="text-[11px] text-[#5a6f95] mt-0.5"><?= htmlspecialchars($row['author']) ?></div>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <span class="bg-indigo-50/40 border border-indigo-100 text-[#475569] px-2 py-0.5 rounded-md text-[10px] font-semibold">
                                                        <?= htmlspecialchars($row['category']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <div class="font-medium text-slate-600"><?= htmlspecialchars($row['publisher']) ?></div>
                                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">Ano: <?= $row['year'] ?></div>
                                                </td>
                                                <td class="py-4 px-4">
                                                    <?php if ($row['status'] === 'disponivel'): ?>
                                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">Disponível</span>
                                                    <?php elseif ($row['status'] === 'emprestado'): ?>
                                                        <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider font-sans">Emprestado</span>
                                                    <?php else: ?>
                                                        <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">Reservado</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-4 px-4 text-center">
                                                    <div class="flex gap-2 justify-center">
                                                        <!-- Edit Trigger -->
                                                        <a 
                                                            href="?tab=acervo&edit_id=<?= $row['id'] ?>" 
                                                            class="p-1 px-2 border border-brand-border/60 hover:bg-yellow-50 hover:border-yellow-200 text-yellow-700 rounded-lg transition text-[11px]"
                                                            title="Editar livro"
                                                        >
                                                            Editar
                                                        </a>
                                                        <!-- Delete trigger form -->
                                                        <form method="POST" onsubmit="return confirm('Tem certeza absoluta que deseja remover este livro permanentemente?');">
                                                            <input type="hidden" name="action" value="delete_book">
                                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                            <button 
                                                                type="submit" 
                                                                class="p-1 px-2 border border-brand-border/60 hover:bg-red-50 hover:border-red-200 text-red-700 rounded-lg transition text-[11px]"
                                                                title="Remover livro"
                                                            >
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- 2. RIGHT COLUMN: Dynamic Form Card (Cadastro or Edição!) -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-[#fdfdfc] p-6 rounded-2xl border border-brand-border editorial-shadow flex flex-col relative">
                        
                        <div class="border-b border-brand-border/40 pb-4 mb-5">
                            <div class="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                                <?= $editingBook ? 'Revisão Editorial' : 'Novo Registro' ?>
                            </div>
                            <h3 class="text-xl font-normal text-brand-dark font-serif italic flex items-center gap-2">
                                <?= $editingBook ? '✏️ Editar Volume' : 'Cadastro de Acervo' ?>
                            </h3>
                            <?php if ($editingBook): ?>
                                <a href="index.php?tab=acervo" class="text-[10px] text-brand-secondary hover:underline tracking-tight mt-1 inline-block">← Cancelar Edição / Novo Cadastro</a>
                            <?php endif; ?>
                        </div>

                        <!-- Form -->
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="<?= $editingBook ? 'edit_book' : 'add_book' ?>">
                            <?php if ($editingBook): ?>
                                <input type="hidden" name="id" value="<?= $editingBook['id'] ?>">
                            <?php endif; ?>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Título do Livro <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    name="title" 
                                    required 
                                    value="<?= htmlspecialchars($editingBook['title'] ?? '') ?>"
                                    placeholder="Ex: Comece pelo Porquê"
                                    class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary"
                                />
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Autor <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    name="author" 
                                    required
                                    value="<?= htmlspecialchars($editingBook['author'] ?? '') ?>"
                                    placeholder="Ex: Simon Sinek"
                                    class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Categoria</label>
                                    <select 
                                        name="category" 
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark outline-none cursor-pointer focus:border-brand-primary"
                                    >
                                        <?php foreach ($array_categorias_nomes as $cat_name): ?>
                                            <option value="<?= htmlspecialchars($cat_name) ?>" <?= (isset($editingBook['category']) && $editingBook['category'] === $cat_name) ? 'selected' : '' ?>><?= htmlspecialchars($cat_name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Status</label>
                                    <select 
                                        name="status" 
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark outline-none cursor-pointer focus:border-brand-primary"
                                    >
                                        <option value="disponivel" <?= (isset($editingBook['status']) && $editingBook['status'] === 'disponivel') ? 'selected' : '' ?>>Disponível</option>
                                        <option value="emprestado" <?= (isset($editingBook['status']) && $editingBook['status'] === 'emprestado') ? 'selected' : '' ?>>Emprestado</option>
                                        <option value="reservado" <?= (isset($editingBook['status']) && $editingBook['status'] === 'reservado') ? 'selected' : '' ?>>Reservado</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <div class="col-span-2">
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Editora <span class="text-red-500">*</span></label>
                                    <input 
                                        type="text" 
                                        name="publisher" 
                                        required 
                                        value="<?= htmlspecialchars($editingBook['publisher'] ?? '') ?>"
                                        placeholder="Sextante"
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Ano <span class="text-red-500">*</span></label>
                                    <input 
                                        type="number" 
                                        name="year" 
                                        required 
                                        value="<?= htmlspecialchars($editingBook['year'] ?? date('Y')) ?>"
                                        placeholder="2009"
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">ISBN-13 ou Registro</label>
                                <input 
                                    type="text" 
                                    name="isbn" 
                                    value="<?= htmlspecialchars($editingBook['isbn'] ?? '') ?>"
                                    placeholder="978-8543106311"
                                    class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Resumo / Notas Editoriais</label>
                                <textarea 
                                    name="synopsis" 
                                    rows="3" 
                                    placeholder="Breve descrição da obra..." 
                                    class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary resize-none"
                                ><?= htmlspecialchars($editingBook['synopsis'] ?? '') ?></textarea>
                            </div>

                            <button 
                                type="submit" 
                                class="w-full py-2.5 bg-brand-primary hover:bg-[#153468] text-white text-xs font-bold rounded-full uppercase tracking-wider transition-colors shadow-sm"
                            >
                                <?= $editingBook ? 'Salvar Alterações' : 'Registrar Obra no Acervo' ?>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        <?php endif; ?>

        <?php if ($activeTab === 'locacao'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="view-locacao">
                
                <!-- 1. LEFT COLUMN: Issue New Loan Form -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow flex flex-col">
                        
                        <div class="border-b border-brand-border/40 pb-4 mb-5">
                            <div class="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                                Serviço de Circulação
                            </div>
                            <h3 class="text-xl font-normal text-brand-dark font-serif italic flex items-center gap-2">
                                Conceder Empréstimo
                            </h3>
                        </div>

                        <!-- Obter livros 'Disponíveis' do banco -->
                        <?php
                        $availableBooksList = $pdo->query("SELECT * FROM `livros` WHERE `status` = 'disponivel' ORDER BY `title` ASC")->fetchAll();
                        ?>

                        <?php if (empty($availableBooksList)): ?>
                            <div class="p-6 bg-amber-50/50 border border-amber-100 rounded-xl text-center text-xs space-y-2">
                                <svg class="w-8 h-8 text-amber-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="font-bold text-amber-900 text-sm">Nenhum exemplar livre</p>
                                <p class="text-slate-600 leading-relaxed">Não há exemplares com status "Disponível". Cadastre ou configure devoluções para realizar um empréstimo.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="add_loan">

                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Selecione o Livro <span class="text-red-500">*</span></label>
                                    <select 
                                        name="book_id" 
                                        required 
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2.5 text-xs rounded-xl text-brand-dark outline-none cursor-pointer focus:border-brand-primary"
                                    >
                                        <option value="">Selecione um volume disponível...</option>
                                        <?php foreach ($availableBooksList as $avail): ?>
                                            <option value="<?= $avail['id'] ?>"><?= htmlspecialchars($avail['title']) ?> (Por: <?= htmlspecialchars($avail['author']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Nome Completo do Leitor <span class="text-red-500">*</span></label>
                                    <input 
                                        type="text" 
                                        name="reader_name" 
                                        required 
                                        placeholder="Ex: Mariana Silva Santos"
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary"
                                    />
                                </div>

                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Informações de Contato <span class="text-red-500">*</span></label>
                                    <input 
                                        type="text" 
                                        name="reader_contact" 
                                        required 
                                        placeholder="Ex: (11) 98765-4321 ou e-mail"
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary"
                                    />
                                </div>

                                <?php
                                $defaultDueDate = date('Y-m-d', strtotime('+14 days'));
                                ?>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Data de Devolução Prevista <span class="text-red-500">*</span></label>
                                    <input 
                                        type="date" 
                                        name="due_date" 
                                        required 
                                        value="<?= $defaultDueDate ?>"
                                        class="w-full bg-[#f8fafc] border border-brand-border/60 p-2.5 text-xs rounded-xl text-brand-dark outline-none focus:border-brand-primary font-mono"
                                    />
                                </div>

                                <button 
                                    type="submit" 
                                    class="w-full py-2.5 bg-brand-primary hover:bg-[#153468] text-white text-xs font-bold rounded-full uppercase tracking-wider transition-colors shadow-sm"
                                >
                                    Conceder Locação
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- 2. RIGHT COLUMN: Active / Log history of Loans -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow">
                        
                        <div class="flex items-center justify-between mb-6 border-b border-brand-border/40 pb-4">
                            <h3 class="text-xl font-normal text-brand-dark font-serif italic">Registro de Movimentações</h3>
                            <!-- Active filter toggle link -->
                            <a 
                                href="?tab=locacao&active_only=<?= $filterActiveOnly ? '0' : '1' ?>&loan_search=<?= urlencode($loanSearch) ?>" 
                                class="text-[10px] uppercase font-bold tracking-widest text-brand-secondary hover:text-brand-dark transition-all"
                            >
                                <?= $filterActiveOnly ? '🔘 Apenas ativos (Mostrar todos)' : '⚪ Mostrar todos (Ver apenas ativos)' ?>
                            </a>
                        </div>

                        <!-- Loan Search Form -->
                        <form method="GET" class="mb-5">
                            <input type="hidden" name="tab" value="locacao">
                            <input type="hidden" name="active_only" value="<?= $filterActiveOnly ?>">
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </span>
                                <input 
                                    type="text" 
                                    name="loan_search" 
                                    value="<?= htmlspecialchars($loanSearch) ?>" 
                                    placeholder="Buscar por leitor ou título de obra..."
                                    class="w-full pl-11 pr-4 py-2 bg-[#f8fafc] border border-brand-border/60 focus:border-brand-primary outline-none text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0]"
                                />
                            </div>
                        </form>

                        <?php if (empty($loans)): ?>
                            <div class="py-12 text-center text-brand-light">
                                <p class="text-xs font-bold">Nenhum empréstimo localizado</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($loans as $loan): ?>
                                    <div class="p-4 rounded-xl border border-brand-border/60 bg-[#fdfdfc] flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-brand-border transition-all">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-[13px] text-brand-dark"><?= htmlspecialchars($loan['book_title']) ?></h4>
                                                <?php if ($loan['status'] === 'ativo'): ?>
                                                    <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold">Ativo</span>
                                                <?php else: ?>
                                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold">Devolvido</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-[#5a6f95]">
                                                <div>👤 <b>Leitor:</b> <?= htmlspecialchars($loan['reader_name']) ?></div>
                                                <div>📞 <b>Contato:</b> <?= htmlspecialchars($loan['reader_contact']) ?></div>
                                                <div>📅 <b>Retirada:</b> <?= date('d/m/Y', strtotime($loan['loan_date'])) ?></div>
                                                <div>📅 <b>Devolução:</b> <?= date('d/m/Y', strtotime($loan['due_date'])) ?></div>
                                            </div>
                                        </div>

                                        <?php if ($loan['status'] === 'ativo'): ?>
                                            <!-- Devolver action trigger -->
                                            <form method="POST" onsubmit="return confirm('Confirmar devolução do livro e liberação imediata no acervo?');">
                                                <input type="hidden" name="action" value="return_book">
                                                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                                <input type="hidden" name="book_id" value="<?= $loan['book_id'] ?>">
                                                <button 
                                                    type="submit" 
                                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold text-[11px] uppercase tracking-wide transition shadow-sm cursor-pointer"
                                                >
                                                    Devolver
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="text-right">
                                                <span class="text-[10px] text-slate-400 font-mono">Retornado em: <br><?= date('d/m/Y', strtotime($loan['return_date'])) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        <?php endif; ?>

        <?php if ($activeTab === 'categorias'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="view-categorias">
                
                <!-- 1. LEFT COLUMN: Add Category Form -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow flex flex-col">
                        
                        <div class="border-b border-brand-border/40 pb-4 mb-5">
                            <div class="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                                Administração Estrutural
                            </div>
                            <h3 class="text-xl font-normal text-brand-dark font-serif italic">Nova Divisão / Gênero</h3>
                        </div>

                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add_category">

                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Nome da Categoria <span class="text-red-500">*</span></label>
                                <input 
                                    type="text" 
                                    name="nome" 
                                    required 
                                    placeholder="Ex: Filosofia & Filosofia Política"
                                    class="w-full bg-[#f8fafc] border border-brand-border/60 p-2.5 text-xs rounded-xl text-brand-dark placeholder-[#8ea2c0] outline-none focus:border-brand-primary"
                                />
                            </div>

                            <button 
                                type="submit" 
                                class="w-full py-2.5 bg-brand-primary hover:bg-[#153468] text-white text-xs font-bold rounded-full uppercase tracking-wider transition-colors shadow-sm"
                            >
                                Adicionar aos Registros
                            </button>
                        </form>

                    </div>
                </div>

                <!-- 2. RIGHT COLUMN: Catalog Categories List with book count calculation dynamically -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-brand-border editorial-shadow">
                        
                        <div class="mb-5 border-b border-brand-border/40 pb-4">
                            <h3 class="text-xl font-normal text-brand-dark font-serif italic">Gêneros e Segmentos Ativos</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($categorias as $cat): ?>
                                <?php
                                // Calcular a contagem de livros associados de forma dinâmica
                                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `livros` WHERE `category` = ?");
                                $stmtCount->execute([$cat['nome']]);
                                $countBooks = $stmtCount->fetchColumn();
                                ?>
                                <div class="p-4 rounded-xl border border-brand-border/50 bg-[#fdfdfc] flex items-center justify-between text-xs hover:border-brand-border transition-all">
                                    <div class="space-y-0.5">
                                        <span class="font-bold text-brand-dark text-[13px]"><?= htmlspecialchars($cat['nome']) ?></span>
                                        <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider"><?= $countBooks ?> volume(s) associado(s)</div>
                                    </div>
                                    
                                    <form method="POST" onsubmit="return confirm('Tem certeza absoluta que deseja remover esta categoria? Livros que dependem dela continuarão listados.');">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button 
                                            type="submit" 
                                            class="p-1 px-2 border border-brand-border hover:bg-red-50 hover:border-red-200 text-red-600 rounded-lg text-[10px] font-bold tracking-wide uppercase transition duration-150 cursor-pointer"
                                            title="Remover categoria"
                                        >
                                            Deletar
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>

            </div>
        <?php endif; ?>

        <!-- Balanced Subtle Editorial Grid decorative bottom footer -->
        <footer class="pt-6 border-t border-brand-border/40 text-[10px] text-slate-400 font-mono tracking-widest flex flex-col sm:flex-row justify-between uppercase gap-2" id="panel-bottom-footer-decor">
            <span>TecaVirtual Management v2.4.0</span>
            <span>Painel Ativo: Biblioteca / Acervo Administrativo</span>
            <span>© 2026 TECAVIRTUAL</span>
        </footer>

    </div>

</body>
</html>
