<?php
/**
 * TecaVirtual - Página de Login Administrativo
 */
require_once 'config.php';

// Se já estiver logado, redireciona para o painel principal
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$usernameInput = 'admin@tecavirtual.com'; // Preenchido por padrão como na versão React

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `usuarios` WHERE `username` = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Sucesso no Login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = 'Dados de autenticação inválidos. Utilize as credenciais de demonstração listadas.';
                $usernameInput = $username;
            }
        } catch (PDOException $e) {
            $error = 'Erro no servidor: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | TecaVirtual Management</title>
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
        .editorial-title {
            font-family: 'Playfair Display', serif;
        }
        .editorial-shadow {
            box-shadow: 0 10px 30px rgba(16, 42, 85, 0.04);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 selection:bg-brand-primary/10 select-none">

    <div class="w-full max-w-sm" id="login-container">
        <!-- Editorial Logo Flag -->
        <div class="flex items-center gap-3 justify-center mb-6" id="login-header-logo">
            <div class="w-10 h-10 bg-brand-accent rounded-lg flex items-center justify-center text-[#102a55] shadow-sm font-extrabold text-xl font-serif">
                T
            </div>
            <h1 class="text-xl font-extrabold tracking-tight text-brand-dark">
                Teca<span class="text-brand-secondary">Virtual</span>
            </h1>
        </div>

        <!-- Login Card Box -->
        <div class="bg-white p-8 rounded-2xl border border-brand-border editorial-shadow flex flex-col relative overflow-hidden" id="card-box">
            <!-- Top Subtle Stripe Accent -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-brand-primary"></div>

            <div class="mb-6 border-b border-brand-border/40 pb-4 text-center">
                <div class="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                    Garantia de Integridade
                </div>
                <h2 class="text-2xl font-normal text-brand-dark font-serif italic text-center">
                    Autenticação Interna
                </h2>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 bg-red-50 text-red-700 border border-red-200 text-xs rounded-xl p-3 flex items-start gap-2" role="alert">
                    <svg class="w-4 h-4 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-5" autocomplete="on">
                <div id="group-username">
                    <label for="username" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                        E-mail de Acesso <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            name="username"
                            id="username"
                            value="<?= htmlspecialchars($usernameInput) ?>"
                            required
                            placeholder="admin@tecavirtual.com"
                            class="w-full pl-11 pr-4 py-2.5 border-b-2 border-brand-border focus:border-brand-primary outline-none text-sm transition-colors text-brand-dark placeholder-brand-light bg-transparent font-medium"
                        />
                    </div>
                    <p class="text-[10px] text-brand-light/80 mt-1.5 italic">Usuário padrão: admin@tecavirtual.com</p>
                </div>

                <div id="group-password">
                    <label for="password" class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                        Senha Secreta <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-brand-light">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            value="admin123"
                            required
                            placeholder="*************"
                            class="w-full pl-11 pr-4 py-2.5 border-b-2 border-brand-border focus:border-brand-primary outline-none text-sm transition-colors text-brand-dark placeholder-brand-light bg-transparent font-medium"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    id="btn-submit-action"
                    class="w-full py-3 bg-brand-primary hover:bg-[#153468] text-white rounded-full text-xs font-bold uppercase tracking-widest transition flex items-center justify-center gap-2 shadow-md shadow-brand-primary/10 mt-6 cursor-pointer"
                >
                    <span>Autenticar Usuário</span>
                </button>
            </form>
        </div>

        <p class="text-center text-[9px] text-slate-400 font-mono uppercase tracking-widest mt-8">
            Painel Administrativo v2.4.0
        </p>
    </div>

</body>
</html>
