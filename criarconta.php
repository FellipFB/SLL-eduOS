<?php
/**
 * TecaVirtual - Página de Criação de Conta
 */
require_once 'config.php';

$erro = '';
$sucesso = '';
$nome = '';
$email = '';
$senha = '';
$confirmar_senha = '';

// Se já estiver logado, redireciona para a vitrina
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Processar formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome)) {
        $erro = 'Nome completo é obrigatório';
    } elseif (empty($email)) {
        $erro = 'E-mail é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido';
    } elseif (empty($senha)) {
        $erro = 'Senha é obrigatória';
    } elseif (strlen($senha) < 6) {
        $erro = 'Senha deve ter no mínimo 6 caracteres';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não conferem';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM `usuarios` WHERE `username` = ?');
            $stmt->execute([$email]);

            if ($stmt->fetchColumn() > 0) {
                $erro = 'Este e-mail já está cadastrado';
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO `usuarios` (`username`, `password`) VALUES (?, ?)');
                $stmt->execute([$email, $senhaHash]);

                $sucesso = 'Conta criada com sucesso! Você já pode fazer o login de leitor.';
                $nome = ''; // limpar campos após sucesso
                $email = '';
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar a conta no banco de dados. Tente novamente mais tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta | TecaVirtual</title>
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
                            border: '#e2e8f0',
                            accent: '#4ade80'
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased text-slate-800">

    <!-- HEADER -->
    <header class="bg-brand-primary text-white py-5 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2.5">
                <i class="fa-solid fa-book-open text-lg text-yellow-400"></i>
                <span class="text-lg font-bold font-serif italic text-white">TecaVirtual</span>
            </a>
            <a href="index.php" class="text-xs text-slate-300 hover:text-white flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Voltar à Vitrina
            </a>
        </div>
    </header>

    <!-- MAIN CARD -->
    <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md bg-white rounded-3xl border border-slate-200/60 shadow-xl p-8 flex flex-col relative overflow-hidden">
            <!-- Top brand stripe -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-brand-secondary to-blue-600"></div>

            <div class="text-center mb-6">
                <div class="w-12 h-12 rounded-2xl bg-brand-secondary/5 border border-brand-secondary/10 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-user-plus text-xl text-brand-secondary"></i>
                </div>
                <h2 class="text-2xl font-black font-serif italic text-brand-primary">Criar Nova Conta</h2>
                <p class="text-xs text-slate-400 mt-1">Cadastre-se para locação imediata de acervo especializado.</p>
            </div>

            <?php if (!empty($erro)): ?>
                <div class="mb-5 p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-800 text-xs flex items-start gap-3 leading-relaxed">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-sm mt-0.5 shrink-0"></i>
                    <div><?php echo $erro; ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)): ?>
                <div class="mb-5 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-800 text-xs flex items-start gap-3 leading-relaxed">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm mt-0.5 shrink-0"></i>
                    <div><?php echo $sucesso; ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                
                <!-- Nome Completo -->
                <div class="flex flex-col gap-1.5">
                    <label for="nome" class="text-[10px] font-black uppercase tracking-widest text-slate-500">Seu Nome Completo *</label>
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-user absolute left-4 text-slate-400 text-sm"></i>
                        <input 
                            type="text" 
                            name="nome" 
                            id="nome"
                            required 
                            placeholder="Ex: Mariana Silva" 
                            value="<?php echo htmlspecialchars($nome); ?>"
                            class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                        >
                    </div>
                </div>

                <!-- E-mail -->
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-[10px] font-black uppercase tracking-widest text-slate-500">Endereço de E-mail *</label>
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-envelope absolute left-4 text-slate-400 text-sm"></i>
                        <input 
                            type="email" 
                            name="email" 
                            id="email"
                            required 
                            placeholder="Ex: mariana@exemplo.com" 
                            value="<?php echo htmlspecialchars($email); ?>"
                            class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                        >
                    </div>
                </div>

                <!-- Senha -->
                <div class="flex flex-col gap-1.5">
                    <label for="senha" class="text-[10px] font-black uppercase tracking-widest text-slate-500">Senha de Acesso *</label>
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-lock absolute left-4 text-slate-400 text-sm"></i>
                        <input 
                            type="password" 
                            name="senha" 
                            id="senha"
                            required 
                            placeholder="••••••••" 
                            onkeyup="verificarForcaSenha()"
                            class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                        >
                    </div>
                    <!-- Password strength visualizer -->
                    <div class="mt-2">
                        <div id="strength-bar" class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div id="strength-fill" class="h-full w-0 rounded-full transition-all duration-300 bg-slate-300"></div>
                        </div>
                        <div id="strength-message" class="text-[10px] font-semibold text-slate-400 mt-1.5 flex items-center gap-1.5 italic" aria-live="polite">
                            <i class="fa-solid fa-shield-halved text-slate-400"></i>
                            <span>Senha curta</span>
                        </div>
                    </div>
                </div>

                <!-- Confirmar Senha -->
                <div class="flex flex-col gap-1.5">
                    <label for="confirmar_senha" class="text-[10px] font-black uppercase tracking-widest text-slate-500">Confirmar Senha *</label>
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-lock absolute left-4 text-slate-400 text-sm"></i>
                        <input 
                            type="password" 
                            name="confirmar_senha" 
                            id="confirmar_senha"
                            required 
                            placeholder="••••••••" 
                            class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                        >
                    </div>
                </div>

                <div class="text-[10px] text-slate-400 leading-relaxed pt-2">
                    Ao criar esta conta, você declara conformidade com as regras de integridade e devolução de obras técnicas da instituição.
                </div>

                <button 
                    type="submit" 
                    class="w-full mt-4 py-3 bg-brand-secondary hover:bg-brand-primary text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    <span>Cadastrar Minha Conta</span>
                </button>

            </form>

            <div class="mt-6 border-t border-slate-100 pt-5 text-center flex flex-col gap-2">
                <p class="text-xs text-slate-500">
                    Já possui cadastro leitor? 
                    <a href="login.php" class="text-brand-secondary font-bold hover:underline">Entre por aqui</a>
                </p>
                <div class="w-2 h-2 rounded-full bg-slate-200 mx-auto"></div>
                <p class="text-[10px] text-slate-450 text-slate-400">
                    Quer gerenciar o acervo? 
                    <a href="loginadm.php" class="text-slate-600 font-bold hover:underline">Área Administrativa</a>
                </p>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-brand-primary text-white py-6 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-400">
            TecaVirtual &copy; 2026 • Plataforma de Biblioteca Digital Unificada.
        </div>
    </footer>

    <script>
        function verificarForcaSenha() {
            const senha = document.getElementById('senha').value;
            const messageEl = document.getElementById('strength-message');
            const fillEl = document.getElementById('strength-fill');
            
            if (!senha) {
                fillEl.style.width = '0%';
                fillEl.className = 'h-full w-0 rounded-full transition-all duration-300 bg-slate-300';
                messageEl.className = 'text-[10px] font-semibold text-slate-400 mt-1.5 flex items-center gap-1.5 italic';
                messageEl.innerHTML = '<i class="fa-solid fa-shield-halved text-slate-400"></i><span>Senha curta</span>';
                return;
            }

            const length = senha.length;
            let colorClass = 'bg-slate-300';
            let messageHtml = '';
            let width = '0%';

            if (length < 6) {
                width = '25%';
                colorClass = 'bg-rose-500';
                messageHtml = '<i class="fa-solid fa-circle-xmark text-rose-500"></i><span class="text-rose-600">Senha curta</span>';
            } else if (length < 10) {
                width = '50%';
                colorClass = 'bg-amber-500';
                messageHtml = '<i class="fa-solid fa-triangle-exclamation text-amber-500"></i><span class="text-amber-600">Senha fraca</span>';
            } else if (length < 14) {
                width = '75%';
                colorClass = 'bg-blue-500';
                messageHtml = '<i class="fa-solid fa-circle-info text-blue-500"></i><span class="text-blue-600">Senha moderada</span>';
            } else {
                width = '100%';
                colorClass = 'bg-emerald-500';
                messageHtml = '<i class="fa-solid fa-circle-check text-emerald-500"></i><span class="text-emerald-600 font-bold">Senha de segurança alta</span>';
            }

            fillEl.style.width = width;
            fillEl.className = `h-full rounded-full transition-all duration-300 ${colorClass}`;
            messageEl.className = `text-[10px] font-semibold mt-1.5 flex items-center gap-1.5 italic ${messageHtml.includes('text-rose-600') ? 'text-rose-600' : messageHtml.includes('text-amber-600') ? 'text-amber-600' : messageHtml.includes('text-blue-600') ? 'text-blue-600' : 'text-emerald-600'}`;
            messageEl.innerHTML = messageHtml;
        }
    </script>

</body>
</html>
