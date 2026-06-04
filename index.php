<?php
// Importa o array de livros
require_once 'dados.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioVerde - Livraria Online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #1e4ca5;    
            --secondary-color: #4f7bdb;  
            --light-green: #eef4ff;      
            --dark-text: #102a55;        
            --light-text: #5a6f95;      
            --white: #ffffff;
            --border-color: #d2deed;
            --success-color: #2ec4b6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            color: var(--dark-text);
            background-color: #f8faff;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }

        .search-bar {
            display: flex;
            align-items: center;
            position: relative;
            width: 100%;
            max-width: 450px;
            margin: 0 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 76, 165, 0.1);
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            color: var(--light-text);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-actions a {
            color: var(--dark-text);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-icon { position: relative; padding: 5px; }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -8px;
            background-color: var(--primary-color);
            color: var(--white);
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 700;
            transition: transform 0.2s ease;
        }

        .cart-count.bump { transform: scale(1.3); }

        .menu-toggle {
            display: none;
            font-size: 22px;
            cursor: pointer;
        }

        /* Banner */
        .hero-banner {
            background-color: var(--light-green);
            padding: 60px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .hero-banner h1 { font-size: 36px; margin-bottom: 10px; }
        .hero-banner p { color: var(--light-text); font-size: 18px; max-width: 600px; margin: 0 auto; }

        /* Vitrine */
        .store-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 24px;
            margin-bottom: 25px;
            border-left: 5px solid var(--primary-color);
            padding-left: 15px;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 30px;
        }

        .book-card {
            background-color: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(30, 76, 165, 0.08);
        }

        .book-cover {
            width: 100%;
            height: 260px;
            background-color: var(--light-green);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .book-card:hover .book-cover img { transform: scale(1.05); }

        .book-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;  
            overflow: hidden;
            min-height: 44px;
        }

        .book-author { font-size: 13px; color: var(--light-text); margin-bottom: 15px; }
        .book-price { font-size: 20px; font-weight: 700; margin-bottom: 15px; }

        .btn-buy {
            width: 100%;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .btn-buy:hover { background-color: var(--secondary-color); }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            background-color: var(--dark-text);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: slideIn 0.3s ease forwards;
            border-left: 4px solid var(--success-color);
        }

        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .toast.fade-out { animation: slideOut 0.3s ease forwards; }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(120%); opacity: 0; }
        }

        footer {
            background-color: var(--white);
            border-top: 1px solid var(--border-color);
            padding: 30px;
            text-align: center;
            color: var(--light-text);
            font-size: 14px;
            margin-top: 60px;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .menu-toggle { display: block; order: 2; }
            .nav-logo { order: 1; }
            .nav-actions {
                display: none;
                position: absolute;
                top: 100%; left: 0; width: 100%;
                background-color: var(--white);
                flex-direction: column; padding: 20px; gap: 20px;
                border-bottom: 1px solid var(--border-color);
            }
            .nav-actions.active { display: flex; }
            .search-bar { max-width: 100%; order: 3; margin: 15px 0 0 0; }
            .navbar { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="#" class="nav-logo">
            <i class="fa-solid fa-book-open"></i>
            BiblioVerde
        </a>

        <div class="menu-toggle" id="mobile-menu">
            <i class="fa-solid fa-bars"></i>
        </div>

        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Busque por títulos, autores ou ISBN...">
        </div>

        <div class="nav-actions" id="nav-menu">
            <a href="#" class="cart-icon">
                <i class="fa-solid fa-basket-shopping"></i>
                <span class="cart-count" id="cart-counter">0</span>
            </a>
            <a href="LOGIN.php"><i class="fa-solid fa-circle-user"></i> LOGIN</a>
            <a href="#"><i class="fa-solid fa-user-plus"></i> CRIAR CONTA</a>
        </div>
    </nav>

    <header class="hero-banner">
        <h1>Sua próxima grande história está aqui</h1>
        <p>Explore centenas de títulos físicos e digitais com entrega rápida para todo o Brasil.</p>
    </header>

    <main class="store-container">
        <h2 class="section-title">Livros em Destaque</h2>

        <div class="books-grid">
            
            <?php foreach ($livros as $livro): ?>
                <div class="book-card">
                    <div>
                        <div class="book-cover">
                            <img src="<?php echo $livro['imagem']; ?>" alt="Capa de <?php echo htmlspecialchars($livro['titulo']); ?>">
                        </div>
                        <h3 class="book-title"><?php echo htmlspecialchars($livro['titulo']); ?></h3>
                        <p class="book-author"><?php echo htmlspecialchars($livro['autor']); ?></p>
                    </div>
                    <div>
                        <p class="book-price">R$ <?php echo number_format($livro['preco'], 2, ',', '.'); ?></p>
                        
                        <button class="btn-buy" onclick="adicionarAoCarrinho('<?php echo addslashes($livro['titulo']); ?>')">
                            <i class="fa-solid fa-cart-plus"></i> Comprar
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </main>

    <div class="toast-container" id="toast-container"></div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> BiblioVerde Livraria Online. Todos os direitos reservados.</p>
    </footer>

    <script>
        let contadorCarrinho = 0;
        
        // Menu Mobile
        const mobileMenu = document.getElementById('mobile-menu');
        const navMenu = document.getElementById('nav-menu');

        mobileMenu.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileMenu.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-xmark');
        });

        // Carrinho
        function adicionarAoCarrinho(nomeLivro) {
            contadorCarrinho++;
            const counterElement = document.getElementById('cart-counter');
            counterElement.innerText = contadorCarrinho;
            
            counterElement.classList.add('bump');
            setTimeout(() => counterElement.classList.remove('bump'), 200);

            mostrarNotificacao(`"${nomeLivro}" adicionado ao carrinho!`);
        }

        function mostrarNotificacao(mensagem) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `<i class="fa-solid fa-circle-check" style="color: var(--success-color)"></i> <span>${mensagem}</span>`;
            
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('fade-out');
                toast.addEventListener('animationend', () => toast.remove());
            }, 3000);
        }
    </script>
</body>
</html>
