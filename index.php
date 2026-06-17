<?php
/**
 * TecaVirtual - Vitrine de Livros e Acervo para Leitores (Página Inicial)
 */
require_once 'config.php';

// Inicializar carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Obter a lista de categorias cadastrada
try {
    $stmtCats = $pdo->query("SELECT * FROM `categorias` ORDER BY `nome` ASC");
    $categorias = $stmtCats->fetchAll();
} catch (Exception $e) {
    // Fallback de segurança se falhar
    $categorias = [];
}

// Obter filtros de pesquisa
$search = trim($_GET['search'] ?? '');
$category_filter = trim($_GET['category'] ?? '');

// Consultar acervo de livros com filtros ativos
try {
    $query = "SELECT * FROM `livros` WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (`title` LIKE ? OR `author` LIKE ? OR `isbn` LIKE ? OR `publisher` LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($category_filter) && $category_filter !== 'Todas') {
        $query .= " AND `category` = ?";
        $params[] = $category_filter;
    }

    $query .= " ORDER BY `id` DESC";
    $stmtBooks = $pdo->prepare($query);
    $stmtBooks->execute($params);
    $books = $stmtBooks->fetchAll();
} catch (Exception $e) {
    $books = [];
}

// Calcular quantidade de itens no carrinho
$cart_count = 0;
foreach ($_SESSION['carrinho'] as $item) {
    $cart_count += $item['quantidade'];
}

// Determinar o preço fixado ou simulado de forma consistente
function getBookPrice($book_id, $year) {
    $presetPrices = [
        '1' => 49.90,
        '2' => 59.90,
        '3' => 39.90,
        '4' => 45.00,
        '5' => 62.50
    ];
    if (isset($presetPrices[$book_id])) {
        return $presetPrices[$book_id];
    }
    // Fallback determínistico
    $textHash = array_sum(array_map('ord', str_split($book_id)));
    $baseVal = 29.90 + ($textHash % 30);
    return round($baseVal + ($year % 15), 2);
}

// Função para retornar uma cor baseada na categoria para personificar capas das obras
function getCategoryColorGradient($category) {
    $colors = [
        'Administração' => 'from-blue-600 to-indigo-800',
        'Marketing' => 'from-pink-500 to-rose-700',
        'Finanças & Economia' => 'from-amber-500 to-orange-700',
        'Desenvolvimento Pessoal' => 'from-purple-500 to-indigo-700',
        'Tecnologia & Programação' => 'from-cyan-500 to-blue-700',
        'Liderança & Gestão' => 'from-violet-600 to-purple-800',
        'Empreendedorismo' => 'from-emerald-500 to-teal-700',
        'Comunicação' => 'from-fuchsia-500 to-pink-700',
        'Ciência & Inovação' => 'from-sky-500 to-indigo-600',
    ];
    return $colors[$category] ?? 'from-slate-600 to-slate-800';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecaVirtual | Biblioteca Digital & Aluguel de Livros</title>
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
                            dark: '#0f172a',
                            light: '#f8fafc',
                            border: '#e2e8f0'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                        serif: ['Playfair Display', 'Georgia', 'serif']
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts & Font Awesome Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        .scroller::-webkit-scrollbar {
            height: 6px;
        }
        .scroller::-webkit-scrollbar-track {
            background: transparent;
        }
        .scroller::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>
<body id="storefront-body-root" class="min-h-screen flex flex-col antialiased text-slate-800">

    <!-- TOP HEADER / BARRA DE NAVEGAÇÃO -->
    <header class="sticky top-0 z-40 bg-brand-primary text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <!-- LOGO E MARCA -->
                <a href="index.php" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/20 shadow-inner group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-book-open text-xl text-yellow-400"></i>
                    </div>
                    <div>
                        <span class="text-xl sm:text-2xl font-black tracking-tight font-serif italic text-white block">TecaVirtual</span>
                        <span class="text-[9px] uppercase tracking-widest text-slate-300 font-extrabold block -mt-1">Biblioteca Inteligente</span>
                    </div>
                </a>

                <!-- NAVEGAÇÃO E AÇÕES -->
                <div class="flex items-center gap-4 sm:gap-6">
                    
                    <?php if (isset($_SESSION['user_name'])): ?>
                        <!-- Informação do Usuário Logado (Leitor ou Admin) -->
                        <div class="hidden md:flex flex-col text-right">
                            <span class="text-xs text-slate-300">Olá, bem-vindo</span>
                            <span class="text-sm font-semibold text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- CARRINHO BUTTON -->
                    <a href="carrinho.php" class="relative group p-2.5 rounded-xl hover:bg-white/10 border border-transparent hover:border-white/10 transition-all flex items-center justify-center">
                        <i class="fa-solid fa-cart-shopping text-xl text-slate-200 group-hover:text-yellow-400 transition-colors"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white rounded-full text-[10px] font-black flex items-center justify-center shadow-md animate-bounce">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- AÇÕES DE LOGIN / SAÍDA -->
                    <?php if (isset($_SESSION['user_id']) && empty($_SESSION['is_admin'])): ?>
                        <!-- Leitor logado -->
                        <div class="flex items-center gap-2">
                            <a href="logout.php" title="Sair" class="p-2.5 text-rose-300 hover:text-rose-400 hover:bg-white/5 rounded-xl transition-all">
                                <i class="fa-solid fa-right-from-bracket text-lg"></i>
                            </a>
                        </div>
                    <?php elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <!-- Administrador logado -->
                        <div class="flex items-center gap-2">
                            <a href="logout.php" title="Sair" class="p-2.5 text-rose-300 hover:text-rose-400 hover:bg-white/5 rounded-xl transition-all">
                                <i class="fa-solid fa-right-from-bracket text-lg"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Visitante / leitor não autenticado -->
                        <div class="flex items-center gap-2">
                            <a href="login.php" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-400 text-brand-primary rounded-xl text-xs sm:text-sm font-extrabold uppercase tracking-wider transition-all shadow-md flex items-center gap-2">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                <span>Entrar</span>
                            </a>
                            <a href="criarconta.php" class="hidden sm:inline-block px-4 py-2 text-xs text-slate-300 hover:text-white hover:underline transition-all font-semibold">
                                Criar Conta
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </header>

    <!-- HERO SECTION BANNER -->
    <section class="relative bg-gradient-to-b from-brand-primary to-slate-900 text-white overflow-hidden py-12 sm:py-20">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:20px_20px] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-3xl mx-auto flex flex-col gap-4">
                
                <div class="inline-flex self-center items-center gap-2 px-3 py-1 bg-yellow-500/10 border border-yellow-500/30 text-yellow-400 rounded-full text-xs font-bold tracking-wide uppercase">
                    <i class="fa-solid fa-sparkles"></i>
                    <span>Sua Estante Digital Autônoma</span>
                </div>
                
                <h1 class="text-3xl sm:text-5xl font-black tracking-tight font-serif italic text-white leading-tight">
                    Sua Porta de Entrada para o <span class="text-yellow-400">Conhecimento</span>
                </h1>
                
                <p class="text-sm sm:text-lg text-slate-300 leading-relaxed max-w-2xl mx-auto mt-2">
                    Navegue por nosso acervo literário de excelência técnica. Escolha suas obras, agende a locação no carrinho e retire pessoalmente na biblioteca.
                </p>

                <!-- BUSCADOR INTERATIVO -->
                <div class="mt-8 bg-white p-2 rounded-2xl shadow-xl border border-slate-200/50 max-w-xl mx-auto w-full">
                    <form action="index.php" method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                        <div class="flex-1 flex items-center pl-3 gap-2">
                            <i class="fa-solid fa-magnifying-glass text-slate-400 text-lg"></i>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>" 
                                placeholder="Buscar por título, autor, editora ou ISBN..."
                                class="w-full bg-transparent py-2.5 text-slate-800 placeholder-slate-400 text-sm focus:outline-none focus:ring-0"
                            >
                        </div>
                        <button type="submit" class="px-6 py-3 bg-brand-secondary hover:bg-brand-primary text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow cursor-pointer">
                            Pesquisar
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <!-- CONTEÚDO PRINCIPAL (CATÁLOGO DE LIVROS) -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 w-full">
        
        <!-- BARRA HORIZONTAL DE FILTRAGEM POR CATEGORIAS -->
        <div class="mb-10">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-5">
                <h3 class="text-sm font-extrabold uppercase tracking-widest text-slate-500">Filtrar por Categorias</h3>
                <?php if (!empty($category_filter) || !empty($search)): ?>
                    <a href="index.php" class="text-xs text-brand-secondary font-bold hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-circle-xmark"></i> Limpar Filtros
                    </a>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-3 scroller">
                <!-- Botão "Todas" -->
                <a 
                    href="index.php?category=Todas&search=<?php echo urlencode($search); ?>" 
                    class="px-5 py-2.5 rounded-full text-xs font-bold tracking-wide border transition-all whitespace-nowrap <?php echo ($category_filter === 'Todas' || empty($category_filter)) ? 'bg-brand-primary text-white border-brand-primary shadow-md' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:border-slate-300' ?>"
                >
                    <i class="fa-solid fa-layer-group mr-1.5"></i> Todas as Categorias
                </a>

                <!-- Demais categorias cadastradas -->
                <?php foreach ($categorias as $cat): ?>
                    <a 
                        href="index.php?category=<?php echo urlencode($cat['nome']); ?>&search=<?php echo urlencode($search); ?>" 
                        class="px-5 py-2.5 rounded-full text-xs font-bold tracking-wide border transition-all whitespace-nowrap <?php echo ($category_filter === $cat['nome']) ? 'bg-brand-primary text-white border-brand-primary shadow-md' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:border-slate-300' ?>"
                    >
                        <?php echo htmlspecialchars($cat['nome']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- EXIBIÇÃO DO ENCABEÇADO DO FLUXO -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-xl sm:text-2xl font-serif italic font-bold text-brand-primary">Obras Disponíveis</h2>
                <p class="text-xs text-slate-500 mt-1">
                    <?php if (count($books) === 0): ?>
                        Nenhuma obra encontrada correspondente aos filtros aplicados.
                    <?php else: ?>
                        Exibindo <span class="font-extrabold text-slate-700"><?php echo count($books); ?></span> obra(s) catalogada(s).
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if (count($books) === 0): ?>
            <!-- EMPTY STATE CARD -->
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/60 max-w-xl mx-auto shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                </div>
                <h4 class="text-lg font-bold text-slate-800">Nenhum livro foi encontrado</h4>
                <p class="text-xs text-slate-500 mt-2 max-w-sm mx-auto">
                    Não encontramos resultados para a sua pesquisa. Tente buscar por outros termos ou selecione outra categoria.
                </p>
                <a href="index.php" class="inline-flex mt-6 px-5 py-2.5 bg-brand-secondary hover:bg-brand-primary text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm">
                    Ir para o Início
                </a>
            </div>
        <?php else: ?>
            
            <!-- GRID DE LIVROS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php foreach ($books as $book): ?>
                    <?php 
                        $price = getBookPrice($book['id'], $book['year']);
                        $gradient = getCategoryColorGradient($book['category']);
                    ?>
                    <article class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col group relative">
                        
                        <!-- Header do Card (Editora + Categoria) -->
                        <div class="px-5 pt-5 pb-3 border-b border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-extrabold uppercase tracking-widest bg-slate-50/50">
                            <span class="text-brand-secondary truncate max-w-[120px]" title="<?php echo htmlspecialchars($book['category']); ?>">
                                <i class="fa-solid fa-tag mr-1 text-[9px]"></i><?php echo htmlspecialchars($book['category']); ?>
                            </span>
                            <span class="truncate max-w-[120px]" title="<?php echo htmlspecialchars($book['publisher']); ?>">
                                <?php echo htmlspecialchars($book['publisher']); ?>
                            </span>
                        </div>

                        <!-- Detalhes do Livro -->
                        <div class="p-5 flex-1 flex flex-col gap-4">
                            <div>
                                <h4 class="text-base font-bold font-serif italic text-brand-dark line-clamp-1 group-hover:text-brand-secondary transition-colors" title="<?php echo htmlspecialchars($book['title']); ?>">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </h4>
                                <p class="text-xs font-semibold text-slate-500 mt-0.5">por <?php echo htmlspecialchars($book['author']); ?></p>
                            </div>

                            <!-- Capa Simulada Dinâmica -->
                            <div class="h-32 w-full bg-gradient-to-tr <?php echo $gradient; ?> rounded-2xl flex flex-col justify-end p-4 text-white font-serif relative overflow-hidden shadow-inner">
                                <div class="absolute inset-0 bg-black/10"></div>
                                <div class="absolute top-0 bottom-0 left-2 w-px bg-white/20 shadow-r z-10"></div>
                                <p class="font-serif italic font-black text-sm text-white/95 leading-snug line-clamp-2 max-w-[210px] z-20">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </p>
                                <span class="text-[9px] uppercase tracking-widest text-white/70 font-extrabold block mt-1 z-20"><?php echo htmlspecialchars($book['author']); ?></span>
                            </div>

                            <!-- Sinopse -->
                            <?php if (!empty($book['synopsis'])): ?>
                                <p class="text-[11px] text-slate-500 line-clamp-3 leading-relaxed" title="<?php echo htmlspecialchars($book['synopsis']); ?>">
                                    <?php echo htmlspecialchars($book['synopsis']); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-[11px] text-slate-400 italic line-clamp-3 leading-relaxed">
                                    Nenhuma sinopse disponível para esta obra literária técnica.
                                </p>
                            <?php endif; ?>

                            <!-- Detalhes Finais (Ano, ISBN, Preço, Status) -->
                            <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between">
                                <div>
                                    <p class="text-[8px] uppercase font-extrabold text-slate-400 tracking-wider">Ano / ISBN</p>
                                    <span class="block text-xs text-slate-600 font-mono">
                                        <?php echo $book['year']; ?> <?php echo !empty($book['isbn']) ? '| ISBN: ' . htmlspecialchars($book['isbn']) : ''; ?>
                                    </span>
                                </div>
                                
                                <!-- Status Badge -->
                                <?php 
                                    $status = $book['status'];
                                    if ($status === 'disponivel') {
                                        $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                        $statusText = 'Disponível';
                                    } elseif ($status === 'emprestado') {
                                        $statusClass = 'bg-amber-50 text-amber-700 border-amber-100';
                                        $statusText = 'Alugado';
                                    } else {
                                        $statusClass = 'bg-blue-50 text-blue-700 border-blue-100';
                                        $statusText = 'Reservado';
                                    }
                                ?>
                                <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Barra Inferior de Ação -->
                        <div class="px-5 pb-5 pt-0">
                            <?php if ($status === 'disponivel'): ?>
                                <form action="carrinho.php" method="POST" class="w-full">
                                    <input type="hidden" name="action" value="adicionar">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <button 
                                        type="submit" 
                                        class="w-full py-2.5 bg-brand-secondary hover:bg-brand-primary text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <i class="fa-solid fa-cart-plus text-sm"></i>
                                        <span>Adicionar ao Carrinho</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button 
                                    disabled 
                                    class="w-full py-2.5 bg-slate-100 text-slate-400 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 cursor-not-allowed border border-slate-250/20"
                                >
                                    <i class="fa-solid fa-ban text-sm"></i>
                                    <span>Indisponível para Aluguel</span>
                                </button>
                            <?php endif; ?>
                        </div>

                    </article>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </main>

    <!-- FOOTER / RODAPÉ -->
    <footer class="bg-brand-primary text-white border-t border-white/5 py-10 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                <!-- Logotipo inferior -->
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center border border-white/10">
                        <i class="fa-solid fa-book-open text-xs text-yellow-500"></i>
                    </div>
                    <span class="text-[#f8fafc]/90 font-serif italic font-bold">TecaVirtual</span>
                </div>
                
                <!-- Informações do workspace e créditos -->
                <p class="text-xs text-slate-400 text-center sm:text-right">
                    &copy; 2026 TecaVirtual • Todos os direitos reservados.
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
