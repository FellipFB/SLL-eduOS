<?php
/**
 * TecaVirtual - Finalização de Locação (Checkout) e Geração de Comprovante
 */
require_once 'config.php';

// Redireciona se o carrinho estiver vazio
if (empty($_SESSION['carrinho'])) {
    header("Location: index.php");
    exit;
}

$erro = '';
$sucesso = false;
$order_details = [];

// Processar a confirmação de locação do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_checkout'])) {
    $reader_name = trim($_POST['reader_name'] ?? '');
    $reader_contact = trim($_POST['reader_contact'] ?? '');
    $due_date = $_POST['due_date'] ?? '';

    // Validações básicas
    if (empty($reader_name) || empty($reader_contact) || empty($due_date)) {
        $erro = 'Todos os campos de agendamento são obrigatórios para a reserva de acervo.';
    } elseif (strtotime($due_date) < strtotime(date('Y-m-d'))) {
        $erro = 'A data de devolução prevista não pode ser uma data retroativa.';
    } else {
        try {
            $pdo->beginTransaction();

            $loan_date = date('Y-m-d');
            $saved_books = [];

            // Iterar sobre cada livro no carrinho e registrar o empréstimo
            foreach ($_SESSION['carrinho'] as $book_id => $item) {
                // Verificar se o livro ainda está disponível
                $stmtCheck = $pdo->prepare("SELECT `status`, `title` FROM `livros` WHERE `id` = ?");
                $stmtCheck->execute([$book_id]);
                $bookData = $stmtCheck->fetch();

                if (!$bookData || $bookData['status'] !== 'disponivel') {
                    throw new Exception("O livro '" . ($bookData['title'] ?? 'Indefinido') . "' não está mais disponível para aluguel.");
                }

                // Inserir registro na tabela de empréstimos
                $stmtInsert = $pdo->prepare("INSERT INTO `emprestimos` (`book_id`, `reader_name`, `reader_contact`, `loan_date`, `due_date`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtInsert->execute([$book_id, $reader_name, $reader_contact, $loan_date, $due_date, 'ativo']);

                // Atualizar o status do livro para 'emprestado'
                $stmtUpdate = $pdo->prepare("UPDATE `livros` SET `status` = 'emprestado' WHERE `id` = ?");
                $stmtUpdate->execute([$book_id]);

                $saved_books[] = [
                    'titulo' => $item['titulo'],
                    'autor' => $item['autor'],
                    'preco' => $item['preco']
                ];
            }

            $pdo->commit();

            // Salvar detalhes para exibição do cupom antes de limpar o carrinho
            $order_details = [
                'reader_name' => $reader_name,
                'reader_contact' => $reader_contact,
                'loan_date' => $loan_date,
                'due_date' => $due_date,
                'books' => $saved_books,
                'voucher_code' => 'KLA-' . mt_rand(100000, 999999)
            ];

            // Limpar o carrinho da sessão
            $_SESSION['carrinho'] = [];
            $sucesso = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = 'Erro de Transação: ' . $e->getMessage();
        }
    }
}

// Calcular totais dos livros pré-checkout
$subtotal = 0;
$carrinho_itens = $_SESSION['carrinho'];
foreach ($carrinho_itens as $item) {
    $subtotal += $item['preco'] * $item['quantidade'];
}
$taxa_servico = $subtotal * 0.05;
$impostos = ($subtotal + $taxa_servico) * 0.10;
$total = $subtotal + $taxa_servico + $impostos;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Acervo | TecaVirtual</title>
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
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased flex flex-col">

    <!-- HEADER HEADER -->
    <header class="bg-brand-primary text-white py-6 shadow-md">
        <div class="max-w-4xl mx-auto px-4 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <i class="fa-solid fa-book-open text-xl text-yellow-400"></i>
                <span class="text-xl font-bold font-serif italic text-white">TecaVirtual</span>
            </a>
            <a href="index.php" class="text-xs text-slate-300 hover:text-white flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Acervo
            </a>
        </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto px-4 py-10 w-full flex flex-col justify-center">

        <?php if (!$sucesso): ?>

            <!-- ETAPA DE FORMULÁRIO DE CHECKOUT -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                
                <!-- Formulário lateral (6 colunas) -->
                <div class="md:col-span-7 bg-white rounded-3xl border border-slate-200/50 shadow-xl p-6 sm:p-8">
                    <h2 class="text-2xl font-bold font-serif italic text-brand-primary mb-2">Finalizar Agendamento</h2>
                    <p class="text-xs text-slate-500 mb-6">Complete os dados para reservarmos suas obras para retirada física.</p>

                    <?php if (!empty($erro)): ?>
                        <div class="mb-5 p-4 bg-rose-550/10 border border-rose-350 bg-rose-50 rounded-2xl flex items-start gap-3 text-rose-800 text-xs leading-relaxed">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-sm mt-0.5"></i>
                            <div><?php echo $erro; ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="checkout.php" method="POST" class="space-y-5">
                        
                        <!-- Nome Completo -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[#415372]">Seu Nome Completo *</label>
                            <div class="relative flex items-center">
                                <i class="fa-solid fa-user absolute left-4 text-slate-400 text-sm"></i>
                                <input 
                                    type="text" 
                                    name="reader_name" 
                                    required 
                                    placeholder="Ex: Mariana Silva Santos" 
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50/50 border border-slate-250/60 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                                >
                            </div>
                        </div>

                        <!-- E-mail ou Telefone de Contato -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[#415372]">E-mail / Telefone de Contato *</label>
                            <div class="relative flex items-center">
                                <i class="fa-solid fa-address-book absolute left-4 text-slate-400 text-sm"></i>
                                <input 
                                    type="text" 
                                    name="reader_contact" 
                                    required 
                                    placeholder="Ex: mariana.santos@email.com" 
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50/50 border border-slate-250/60 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                                >
                            </div>
                        </div>

                        <!-- Data Limite de Devolução -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[#415372]">Data Limite para Devolução *</label>
                            <div class="relative flex items-center">
                                <i class="fa-solid fa-calendar-days absolute left-4 text-slate-400 text-sm"></i>
                                <input 
                                    type="date" 
                                    name="due_date" 
                                    required 
                                    min="<?php echo date('Y-m-d'); ?>" 
                                    value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>"
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50/50 border border-slate-250/60 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                                >
                            </div>
                            <span class="text-[9px] text-slate-400 italic">Recomendamos até 14 dias para leitura acadêmica confortável.</span>
                        </div>

                        <!-- Alertas explicativos de devolução -->
                        <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-xl">
                            <div class="flex gap-2 text-[10px] text-blue-800 leading-relaxed">
                                <i class="fa-solid fa-circle-info text-xs text-blue-600 mt-0.5"></i>
                                <p><strong>Retirada Imediata:</strong> Os volumes estarão prontos para retirada no balcão da TecaVirtual 2 horas após a consolidação desta reserva.</p>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            name="confirm_checkout" 
                            class="w-full py-3.5 bg-[#1e4ca5] hover:bg-[#102a55] text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-md hover:scale-[1.01] flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Consolidar Reserva de Acervo</span>
                        </button>

                    </form>
                </div>

                <!-- Resumo Lateral do Pedido (5 colunas) -->
                <div class="md:col-span-5 flex flex-col gap-6">
                    <div class="bg-white rounded-3xl border border-slate-200/50 shadow-lg p-6">
                        <h4 class="text-xs font-black uppercase tracking-wider text-[#415372] border-b border-slate-100 pb-3 mb-4">
                            Livros a Reservar
                        </h4>

                        <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                            <?php foreach ($carrinho_itens as $book_id => $item): ?>
                                <div class="py-3.5 flex justify-between gap-3 items-start">
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-800 font-serif italic line-clamp-1"><?php echo htmlspecialchars($item['titulo']); ?></h5>
                                        <p class="text-[10px] text-slate-500 mt-0.5">por <?php echo htmlspecialchars($item['autor']); ?></p>
                                        <span class="inline-block mt-1.5 px-2 py-0.5 bg-slate-550/10 border border-slate-200 rounded-lg text-[8.5px] font-semibold text-slate-500">1 Volume</span>
                                    </div>
                                    <span class="text-xs font-bold text-slate-700 font-mono">Gratuito</span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="border-t border-slate-150/80 pt-4 mt-4 space-y-2">
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Subtotal da Reserva</span>
                                <span class="font-bold text-slate-700">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Taxa Seguritária (5%)</span>
                                <span class="font-bold text-slate-700">R$ <?php echo number_format($taxa_servico, 2, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500">
                                <span>Imposto Patrimonial (10%)</span>
                                <span class="font-bold text-slate-700">R$ <?php echo number_format($impostos, 2, ',', '.'); ?></span>
                            </div>
                            <div class="border-t border-slate-100 pt-3 flex justify-between text-sm font-extrabold text-[#102a55]">
                                <span>Valor Simulado para Seguro</span>
                                <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex gap-3 text-emerald-800 text-xs leading-relaxed">
                        <i class="fa-solid fa-leaf text-lg mt-0.5 text-emerald-600"></i>
                        <div>
                            <strong>Retirada escolar:</strong> mesmo com os valores simulados para seguro, a retirada física no balcão da biblioteca permanece <strong>100% gratuita</strong>.
                        </div>
                    </div>
                </div>

            </div>

        <?php else: ?>

            <!-- EXIBIÇÃO DE COMPROVANTE / VOUCHER DE RETIRADA (SUCESSO) -->
            <div class="max-w-xl mx-auto bg-white rounded-3xl border border-slate-200/50 shadow-2xl overflow-hidden">
                
                <!-- Topo festivo do comprovante -->
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-center p-8 relative">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 shadow-inner">
                        <i class="fa-solid fa-circle-check text-3xl text-white"></i>
                    </div>
                    <span class="text-[9px] uppercase tracking-widest text-emerald-100 font-black block">Reserva Homologada!</span>
                    <h2 class="text-xl sm:text-2xl font-black font-serif italic mt-0.5">Comprovante de Reserva</h2>
                    
                    <!-- Efeito serrilhado de recibo comercial -->
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-[linear-gradient(135deg,transparent_25%,#fff_25%,#fff_50%,transparent_50%,transparent_75%,#fff_75%)] [background-size:10px_10px]"></div>
                </div>

                <!-- Detalhes do Voucher -->
                <div class="p-6 sm:p-8 space-y-6">
                    
                    <!-- Código do Voucher -->
                    <div class="text-center bg-slate-50 border border-dashed border-slate-200 p-4 rounded-2xl">
                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-extrabold block">Apresente este código na retirada</span>
                        <span class="text-2xl font-black tracking-widest font-mono text-brand-secondary block mt-1"><?php echo $order_details['voucher_code']; ?></span>
                    </div>

                    <!-- Dados do Leitor -->
                    <div class="space-y-3.5 border-b border-slate-100 pb-5">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-[#415372]">Reserva agendada para:</h4>
                        
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-400 block">Estudante / Leitor</span>
                                <strong class="text-slate-800 text-sm block mt-0.5"><?php echo htmlspecialchars($order_details['reader_name']); ?></strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Contato Registrado</span>
                                <span class="text-slate-700 block mt-0.5"><?php echo htmlspecialchars($order_details['reader_contact']); ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Data de Emissão</span>
                                <span class="text-slate-700 block mt-0.5"><?php echo date('d/m/Y', strtotime($order_details['loan_date'])); ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Data Limite de Devolução</span>
                                <strong class="text-rose-600 block mt-0.5"><?php echo date('d/m/Y', strtotime($order_details['due_date'])); ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Obras -->
                    <div class="space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-[#415372]">Obras de Acervo Reservadas:</h4>
                        
                        <div class="divide-y divide-slate-100">
                            <?php foreach ($order_details['books'] as $book): ?>
                                <div class="py-2.5 flex items-center justify-between text-xs">
                                    <div class="flex items-start gap-2.5">
                                        <i class="fa-solid fa-bookmark text-indigo-500 mt-0.5"></i>
                                        <div>
                                            <h5 class="font-bold text-slate-800 font-serif italic"><?php echo htmlspecialchars($book['titulo']); ?></h5>
                                            <p class="text-[9px] text-slate-500 mt-0.5">por <?php echo htmlspecialchars($book['autor']); ?></p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wide">Pendente de Retirada</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Ações de Retorno -->
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
                        <a 
                            href="index.php" 
                            class="flex-1 py-3 bg-[#1e4ca5] hover:bg-[#102a55] text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-house"></i>
                            <span>Ir para a Vitrina Principal</span>
                        </a>
                        <button 
                            onclick="window.print()" 
                            class="py-3 px-5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-center rounded-xl text-xs font-bold uppercase tracking-widest transition-all border border-slate-250/25 flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span>Imprimir</span>
                        </button>
                    </div>

                </div>
            </div>

        <?php endif; ?>

    </main>

    <footer class="bg-brand-primary text-white py-6 border-t border-white/5 mt-auto">
        <div class="max-w-4xl mx-auto px-4 text-center text-xs text-slate-400">
            TecaVirtual &copy; 2026 • Apresente os seus comprovantes digitais de reserva na área de atendimento ao leitor.
        </div>
    </footer>

</body>
</html>
