<?php
/**
 * TecaVirtual - Página de Login de Leitores / Alunos
 */
require_once 'config.php';

// Se já estiver logado, redireciona para a vitrina
if (isset($_SESSION['user_id']) && !isset($_SESSION['is_admin'])) {
    header("Location: index.php");
    exit;
}

$erro = '';
$sucesso = '';
$emailInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $erro = 'Por favor, preencha todos os campos do formulário.';
    } else {
        try {
            // Se o usuário tentar logar com o admin padrão aqui, redirecionamos ou fazemos login admin
            if (strtolower($email) === 'admin@tecavirtual.com') {
                $stmtAdm = $pdo->prepare("SELECT * FROM `adm_usuarios` WHERE `email` = ?");
                $stmtAdm->execute([$email]);
                $admin = $stmtAdm->fetch();
                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['email'];
                    $_SESSION['user_name'] = $admin['name'];
                    $_SESSION['is_admin'] = true;
                    header("Location: adm.php");
                    exit;
                }
            }

            // Consulta usuários / leitores
            $stmt = $pdo->prepare("SELECT * FROM `usuarios` WHERE `username` = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Sucesso no login de leitor
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['name'] ?? explode('@', $user['username'])[0];
                unset($_SESSION['is_admin']); // Remove flag admin para leitores normais
                
                header("Location: index.php");
                exit;
            } else {
                $erro = 'Dados de login inválidos. Verifique seu e-mail e senha cadastrados.';
                $emailInput = $email;
            }
        } catch (PDOException $e) {
            $erro = 'Erro no banco de dados: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | TecaVirtual Leitores</title>
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

    <!-- CONTENT -->
    <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-sm bg-white rounded-3xl border border-slate-200/60 shadow-xl p-8 flex flex-col relative overflow-hidden">
            
            <!-- Top brand stripe -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-brand-secondary to-blue-600"></div>

            <div class="text-center mb-6">
                <div class="w-12 h-12 rounded-2xl bg-brand-secondary/5 border border-brand-secondary/10 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-right-to-bracket text-xl text-brand-secondary"></i>
                </div>
                <h2 class="text-2xl font-black font-serif italic text-brand-primary">Entrar no TecaVirtual</h2>
                <p class="text-xs text-slate-400 mt-1">Acesse a sua conta leitor para reservar e retirar acervo.</p>
                <p class="text-[10px] text-slate-500 mt-2">Dica: use <span class="font-semibold text-brand-secondary">admin@tecavirtual.com</span> com a senha <span class="font-semibold text-brand-secondary">admin123</span> para entrar no painel administrativo.</p>
            </div>

            <?php if (!empty($erro)): ?>
                <div class="mb-5 p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-800 text-xs flex items-start gap-3 leading-relaxed">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-sm mt-0.5 shrink-0"></i>
                    <div><?php echo $erro; ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                
                <!-- Nome / E-mail -->
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-[10px] font-black uppercase tracking-widest text-slate-500 font-sans">E-mail de Cadastro *</label>
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-envelope absolute left-4 text-slate-400 text-sm"></i>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            required 
                            placeholder="mariana@exemplo.com" 
                            value="<?php echo htmlspecialchars($emailInput); ?>"
                            class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                        >
                    </div>
                </div>

                <!-- Senha -->
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="text-[10px] font-black uppercase tracking-widest text-slate-500 font-sans">Sua Senha *</label>
                        <a href="#" class="text-[10px] text-brand-secondary font-bold hover:underline">Esqueceu?</a>
                    </div>
                    <div class="relative flex items-center">
                        <i class="fa-solid fa-lock absolute left-4 text-slate-400 text-sm"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required 
                            placeholder="••••••••" 
                            class="w-full pl-11 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-secondary focus:bg-white transition-all"
                        >
                    </div>
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full py-3 bg-brand-secondary hover:bg-brand-primary text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Acessar Minha Estante</span>
                    </button>
                </div>

            </form>

            <div class="mt-6 border-t border-slate-100 pt-5 text-center flex flex-col gap-2">
                <p class="text-xs text-slate-500">
                    Ainda não possui login? 
                    <a href="criarconta.php" class="text-brand-secondary font-bold hover:underline">Cadastre-se aqui</a>
                </p>
                <div class="w-1.5 h-1.5 rounded-full bg-slate-250 mx-auto"></div>
                <p class="text-[10px] text-slate-400">
                    Acesso à administração? 
                    <a href="loginadm.php" class="text-slate-600 font-bold hover:underline">Acesse o Painel Adm</a>
                </p>
            </div>

        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-brand-primary text-white py-6 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-400">
            TecaVirtual &copy; 2026 • Acesso Leitor e Administração Integrado.
        </div>
    </footer>

</body>
</html>
