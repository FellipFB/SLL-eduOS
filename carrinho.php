<?php
session_start();
require_once 'config.php';

// Inicializar carrinho se não existir
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Determinar o preço fixado de forma consistente
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

// Processar ações do carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'remover') {
        $book_id = $_POST['book_id'] ?? null;
        if ($book_id !== null) {
            unset($_SESSION['carrinho'][$book_id]);
        }
    } elseif ($action === 'adicionar') {
        $book_id = $_POST['book_id'] ?? null;
        if ($book_id !== null) {
            if (isset($_SESSION['carrinho'][$book_id])) {
                $_SESSION['carrinho'][$book_id]['quantidade']++;
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM `livros` WHERE `id` = ?");
                    $stmt->execute([$book_id]);
                    $book = $stmt->fetch();
                    if ($book) {
                        $p_calc = getBookPrice($book['id'], $book['year']);
                        $_SESSION['carrinho'][$book_id] = [
                            'titulo' => $book['title'],
                            'autor' => $book['author'],
                            'preco' => $p_calc,
                            'quantidade' => 1
                        ];
                    }
                } catch (Exception $e) {
                    // Ignora
                }
            }
        }
    } elseif ($action === 'atualizar_quantidade') {
        $book_id = $_POST['book_id'] ?? null;
        $quantidade = max(1, min(10, intval($_POST['quantidade'] ?? 1)));
        if ($book_id !== null && isset($_SESSION['carrinho'][$book_id])) {
            $_SESSION['carrinho'][$book_id]['quantidade'] = $quantidade;
        }
    } elseif ($action === 'limpar_carrinho') {
        $_SESSION['carrinho'] = [];
    } elseif ($action === 'checkout') {
        header('Location: checkout.php');
        exit;
    }
    
    header('Location: carrinho.php');
    exit;
}

// Calcular totais
$subtotal = 0;
$quantidade_total = 0;
$taxa_servico = 0;
$impostos = 0;
$total = 0;

foreach ($_SESSION['carrinho'] as $item) {
    $item_total = $item['preco'] * $item['quantidade'];
    $subtotal += $item_total;
    $quantidade_total += $item['quantidade'];
}

if ($subtotal > 0) {
    // Aluguel da biblioteca digital é gratuito na prática escolar, mas mostramos os valores simulados
    $taxa_servico = $subtotal * 0.05; // 5% taxa de serviço
    $impostos = ($subtotal + $taxa_servico) * 0.10; // 10% impostos
    $total = $subtotal + $taxa_servico + $impostos;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho de Reservas | TecaVirtual</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="min-h-screen text-slate-800 antialiased flex flex-col justify-between">

    <!-- HEADER -->
    <header class="bg-brand-primary text-white py-6 shadow-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <i class="fa-solid fa-book-open text-xl text-yellow-400"></i>
                <div class="flex flex-col">
                    <span class="text-xl font-bold font-serif italic text-white block">TecaVirtual</span>
                    <span class="text-[9px] uppercase tracking-widest text-slate-300 block -mt-1 font-extrabold">Minha Seleção</span>
                </div>
            </a>
            <a href="index.php" class="text-xs text-slate-300 hover:text-white flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Catálogo
            </a>
        </div>
    </header>

    <!-- MAIN AREA -->
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <div class="mb-6 flex flex-col gap-1">
            <h2 class="text-2xl font-black font-serif italic text-brand-primary flex items-center gap-2">
                <i class="fa-solid fa-cart-shopping text-blue-600"></i>
                <span>Carrinho de Reservas</span>
            </h2>
            <p class="text-xs text-slate-400">Gerencie as obras selecionadas antes de consolidar seu agendamento escolar.</p>
        </div>

        <?php if (empty($_SESSION['carrinho'])): ?>
            <!-- SE CARRINHO ESTIVER VAZIO -->
            <div class="bg-white rounded-3xl p-12 text-center border border-slate-200/60 max-w-lg mx-auto shadow-sm mt-8">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-basket-shopping text-3xl text-brand-secondary"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 font-serif italic">Seu carrinho está vazio</h3>
                <p class="text-xs text-slate-505 text-slate-500 mt-2 max-w-sm mx-auto leading-relaxed">
                    Você ainda não selecionou nenhuma obra para aluguel. Navegue pela nossa vitrina e adicione as obras que deseja consultar.
                </p>
                <a href="index.php" class="inline-flex mt-6 px-6 py-3 bg-[#1e4ca5] hover:bg-[#102a55] text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow">
                    Explorar Acervo Técnico
                </a>
            </div>

        <?php else: ?>

            <!-- GRID PRINCIPAL (ITENS VS RESUMO) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mt-8">
                
                <!-- Lista de obras (8 colunas) -->
                <div class="lg:col-span-8 flex flex-col gap-5">
                    
                    <div class="bg-white rounded-3xl border border-slate-200/50 p-6 shadow-sm">
                        <div class="border-b border-slate-100 pb-3 mb-5 flex justify-between items-center text-xs font-bold uppercase tracking-wider text-slate-500">
                            <h3>Volumes no Carrinho (<?php echo $quantidade_total; ?>)</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="limpar_carrinho">
                                <button type="submit" class="text-rose-600 hover:text-rose-700 hover:underline flex items-center gap-1 cursor-pointer">
                                    <i class="fa-solid fa-trash-can"></i> Limpar Tudo
                                </button>
                            </form>
                        </div>

                        <div class="divide-y divide-slate-100">
                            <?php foreach ($_SESSION['carrinho'] as $book_id => $item): ?>
                                <article class="py-5 flex flex-col sm:flex-row justify-between gap-4 items-start sm:items-center">
                                    
                                    <!-- Identidade do Livro -->
                                    <div class="flex gap-4">
                                        <div class="w-14 h-16 bg-gradient-to-tr from-brand-primary to-slate-900 rounded-xl flex items-center justify-center text-white shrink-0 shadow-md">
                                            <i class="fa-solid fa-book text-xl text-yellow-500"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold font-serif italic text-brand-primary line-clamp-1"><?php echo htmlspecialchars($item['titulo']); ?></h4>
                                            <p class="text-xs text-slate-400 mt-0.5">por <?php echo htmlspecialchars($item['autor'] ?? 'Autor Não Informado'); ?></p>
                                            <span class="inline-block px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[9px] font-extrabold uppercase tracking-wider rounded-md border border-emerald-150/10 mt-1.5 shadow-sm">
                                                Suporte Digital
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Modificador de Quantidades -->
                                    <div class="flex items-center gap-6 self-end sm:self-auto w-full sm:w-auto justify-between sm:justify-start">
                                        
                                        <!-- Quantidade e inputs -->
                                        <div class="flex items-center bg-slate-100/80 p-1 rounded-xl border border-slate-200">
                                            
                                            <!-- Decrementor -->
                                            <form method="POST">
                                                <input type="hidden" name="action" value="atualizar_quantidade">
                                                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                                                <input type="hidden" name="quantidade" value="<?php echo max(1, $item['quantidade'] - 1); ?>">
                                                <button type="submit" <?php echo ($item['quantidade'] <= 1) ? 'disabled' : ''; ?> class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all font-black cursor-pointer">
                                                    <i class="fa-solid fa-minus text-[10px]"></i>
                                                </button>
                                            </form>

                                            <span class="px-3.5 text-xs font-bold text-slate-700 font-mono"><?php echo $item['quantidade']; ?></span>

                                            <!-- Incrementor -->
                                            <form method="POST">
                                                <input type="hidden" name="action" value="atualizar_quantidade">
                                                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                                                <input type="hidden" name="quantidade" value="<?php echo min(10, $item['quantidade'] + 1); ?>">
                                                <button type="submit" <?php echo ($item['quantidade'] >= 10) ? 'disabled' : ''; ?> class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all font-black cursor-pointer">
                                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                                </button>
                                            </form>

                                        </div>

                                        <!-- Preço Simulado -->
                                        <div class="text-right">
                                            <span class="text-xs text-slate-400 block font-semibold">Preço Estimado</span>
                                            <strong class="text-sm font-extrabold text-[#1e4ca5] font-mono block mt-0.5">
                                                R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?>
                                            </strong>
                                        </div>

                                        <!-- Remover Item -->
                                        <form method="POST">
                                            <input type="hidden" name="action" value="remover">
                                            <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                                            <button type="submit" class="p-2 text-rose-500 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all cursor-pointer" title="Remover obra">
                                                <i class="fa-solid fa-trash-can text-base"></i>
                                            </button>
                                        </form>

                                    </div>

                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Instruções de locação -->
                    <div class="p-5 bg-blue-50/50 border border-blue-100 rounded-3xl flex gap-3 text-blue-850 text-xs leading-relaxed max-w-4xl">
                        <i class="fa-solid fa-circle-question text-base text-blue-600 mt-0.5 shrink-0"></i>
                        <div>
                            <strong>Informação sobre Aluguel de Acervo:</strong> Na TecaVirtual, todas as reservas escolares e o empréstimo físico das obras literárias de referência técnica são <strong>inteiramente isentos de custos de transação</strong>. Os valores acima representam apenas índices simulatórios para registros de seguros patrimoniais das obras.
                        </div>
                    </div>
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex gap-3 text-emerald-800 text-xs leading-relaxed max-w-4xl">
                        <i class="fa-solid fa-leaf text-base text-emerald-600 mt-0.5 shrink-0"></i>
                        <div>
                            <strong>Retirada física gratuita:</strong> apesar dos números simulados exibidos para organização e seguro, o atendimento no balcão da biblioteca continua <strong>100% gratuito</strong> para o leitor.
                        </div>
                    </div>

                </div>

                <!-- Resumo lateral (4 colunas) -->
                <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/50 shadow-lg p-6 flex flex-col gap-5 sticky top-24">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-600 border-b border-slate-100 pb-3 mb-2">
                        Resumo Técnico de Reserva
                    </h3>

                    <div class="space-y-3 pb-4 border-b border-slate-100 text-xs">
                        <div class="flex justify-between text-slate-550">
                            <span>Soma das Obras</span>
                            <span class="font-bold text-slate-700">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between text-slate-550">
                            <span>Taxa de Seguro Institucional (5%)</span>
                            <span class="font-bold text-slate-700">R$ <?php echo number_format($taxa_servico, 2, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between text-slate-550">
                            <span>Impostos Patrimoniais (10%)</span>
                            <span class="font-bold text-slate-700">R$ <?php echo number_format($impostos, 2, ',', '.'); ?></span>
                        </div>
                        
                        <div class="border-t border-slate-100 pt-3 flex justify-between text-sm font-extrabold text-[#102a55]">
                            <span>Valor para Seguro</span>
                            <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                        </div>

                        <!-- Real Cost Disclaimer -->
                        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 rounded-xl p-3 mt-4 text-[11px] leading-relaxed flex gap-2">
                            <i class="fa-solid fa-leaf text-emerald-600 text-sm mt-0.5"></i>
                            <p><strong>Custo para Aluno:</strong> Isento. Toda reserva é de caráter estritamente acadêmico.</p>
                        </div>
                    </div>

                    <!-- Botão de submissão do agendamento -->
                    <form method="POST">
                        <input type="hidden" name="action" value="checkout">
                        <button 
                            type="submit" 
                            class="w-full py-4 bg-[#1e4ca5] hover:bg-[#102a55] text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md hover:scale-[1.01] flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-solid fa-rectangle-list text-yellow-400"></i>
                            <span>Proceder Para Agendamento</span>
                        </button>
                    </form>

                    <a href="index.php" class="w-full text-center py-2.5 text-xs text-slate-500 hover:text-slate-800 font-bold hover:underline transition-colors block">
                        Continuar Escolhendo Livros
                    </a>
                </div>

            </div>

        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer class="bg-brand-primary text-white py-8 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-slate-400">
            <span class="font-serif italic font-semibold text-white">TecaVirtual Minha Seleção</span>
            <p>&copy; 2026 TecaVirtual • Todos os direitos reservados.</p>
        </div>
    </footer>

</body>
</html>
