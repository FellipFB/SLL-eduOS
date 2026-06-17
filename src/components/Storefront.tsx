import React, { useState, useEffect } from 'react';
import { Book, Loan, AdminAccount } from '../types';
import {
  BookOpen,
  ShoppingCart,
  User,
  UserPlus,
  LogIn,
  LogOut,
  Trash2,
  Plus,
  Minus,
  Search,
  ChevronDown,
  CheckCircle,
  Calendar,
  Mail,
  Lock,
  ArrowRight,
  ShieldCheck,
  Percent,
  Receipt,
  Sparkles,
  ArrowLeft,
  BookMarked,
  Tags,
  AlertCircle
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface StorefrontProps {
  books: Book[];
  categories: string[];
  onCreateLoan: (loanData: Omit<Loan, 'id'>) => void;
  onUpdateBookStatus: (bookId: string, status: Book['status']) => void;
  onSwitchToAdmin: () => void;
  admins: AdminAccount[];
  onAdminLogin: () => void;
}

interface CartItem {
  bookId: string;
  title: string;
  author: string;
  price: number;
  quantity: number;
  category: string;
}

interface ReaderUser {
  name: string;
  email: string;
  isLoggedIn: boolean;
}

export default function Storefront({
  books,
  categories,
  onCreateLoan,
  onUpdateBookStatus,
  onSwitchToAdmin,
  admins,
  onAdminLogin
}: StorefrontProps) {
  // Storefront UI state
  const [cart, setCart] = useState<CartItem[]>(() => {
    const stored = localStorage.getItem('tecavirtual_cart');
    return stored ? JSON.parse(stored) : [];
  });
  const [activeTab, setActiveTab] = useState<'catalog' | 'cart' | 'checkout' | 'success'>('catalog');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('Todas');
  
  // Dialog / Sidebar States
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login');

  // Customer Account state (persisted as client user)
  const [reader, setReader] = useState<ReaderUser>(() => {
    const stored = localStorage.getItem('tecavirtual_reader');
    if (stored) {
      return JSON.parse(stored);
    }
    return { name: '', email: '', isLoggedIn: false };
  });

  // Signup fields
  const [regName, setRegName] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regConfirmPassword, setRegConfirmPassword] = useState('');
  const [passwordStrength, setPasswordStrength] = useState('');
  const [passwordStrengthColor, setPasswordStrengthColor] = useState('');
  const [authError, setAuthError] = useState('');
  const [authSuccess, setAuthSuccess] = useState('');

  // Login fields
  const [loginEmail, setLoginEmail] = useState('');
  const [loginPassword, setLoginPassword] = useState('');

  // Checkout Fields
  const [checkoutDate, setCheckoutDate] = useState(() => {
    const date = new Date();
    date.setDate(date.getDate() + 2);
    return date.toISOString().split('T')[0];
  });
  const [checkoutPhone, setCheckoutPhone] = useState('');
  const [voucherCode, setVoucherCode] = useState('');
  const [lastOrderDetails, setLastOrderDetails] = useState<{
    items: CartItem[];
    subtotal: number;
    fee: number;
    taxes: number;
    total: number;
    readerName: string;
    readerEmail: string;
    pickupDate: string;
    phone: string;
  } | null>(null);

  // Sync cart
  useEffect(() => {
    localStorage.setItem('tecavirtual_cart', JSON.stringify(cart));
  }, [cart]);

  // Sync reader
  useEffect(() => {
    localStorage.setItem('tecavirtual_reader', JSON.stringify(reader));
  }, [reader]);

  // Calculate book deterministic prices
  const getBookPrice = (bookId: string, year: number): number => {
    const presetPrices: Record<string, number> = {
      '1': 49.90,
      '2': 59.90,
      '3': 39.90,
      '4': 45.00,
      '5': 62.50
    };
    if (presetPrices[bookId] !== undefined) {
      return presetPrices[bookId];
    }
    // Deterministic fallback based on year and ID characters
    const textHash = bookId.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    const baseVal = 29.90 + (textHash % 30);
    return Math.round((baseVal + (year % 15)) * 10) / 10;
  };

  // Add to cart logic
  const handleAddToCart = (book: Book) => {
    if (book.status !== 'disponivel') return;
    const price = getBookPrice(book.id, book.year);
    setCart((prev) => {
      const existsIndex = prev.findIndex((item) => item.bookId === book.id);
      if (existsIndex > -1) {
        const updated = [...prev];
        updated[existsIndex].quantity = Math.min(10, updated[existsIndex].quantity + 1);
        return updated;
      } else {
        return [
          ...prev,
          {
            bookId: book.id,
            title: book.title,
            author: book.author,
            price: price,
            quantity: 1,
            category: book.category
          }
        ];
      }
    });
    setIsCartOpen(true); // Open drawer automatically
  };

  // Update cart item quantity
  const handleUpdateQty = (bookId: string, delta: number) => {
    setCart((prev) => {
      return prev
        .map((item) => {
          if (item.bookId === bookId) {
            const newQty = item.quantity + delta;
            return { ...item, quantity: Math.min(10, Math.max(1, newQty)) };
          }
          return item;
        });
    });
  };

  // Remove from cart
  const handleRemoveItem = (bookId: string) => {
    setCart((prev) => prev.filter((item) => item.bookId !== bookId));
  };

  // Clear cart
  const handleClearCart = () => {
    setCart([]);
  };

  // Calculate cart sums
  const cartSubtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const cartQtyCount = cart.reduce((sum, item) => sum + item.quantity, 0);
  const serviceFee = cartSubtotal * 0.05; // 5% Service tax
  const taxSum = (cartSubtotal + serviceFee) * 0.10; // 10% taxes
  const cartGrandTotal = cartSubtotal + serviceFee + taxSum;

  // Password strength checker matching criarconta.php
  const checkPasswordStrength = (pass: string) => {
    setRegPassword(pass);
    if (!pass) {
      setPasswordStrength('');
      setPasswordStrengthColor('');
      return;
    }

    if (pass.length < 6) {
      setPasswordStrength('❌ Mínimo 6 caracteres');
      setPasswordStrengthColor('text-red-600 bg-red-50 border border-red-100');
    } else if (pass.length < 10) {
      setPasswordStrength('⚠️ Senha fraca');
      setPasswordStrengthColor('text-amber-600 bg-amber-50 border border-amber-100');
    } else if (pass.length < 15) {
      setPasswordStrength('✓ Senha moderada');
      setPasswordStrengthColor('text-blue-600 bg-blue-50 border border-blue-100');
    } else {
      setPasswordStrength('✓✓ Senha forte');
      setPasswordStrengthColor('text-emerald-600 bg-emerald-50 border border-emerald-100');
    }
  };

  // Process manual register
  const handleRegister = (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');
    setAuthSuccess('');

    if (!regName.trim()) {
      setAuthError('Nome completo é obrigatório');
      return;
    }
    if (!regEmail.trim() || !regEmail.includes('@')) {
      setAuthError('E-mail inválido');
      return;
    }
    if (regPassword.length < 6) {
      setAuthError('Senha deve ter no mínimo 6 caracteres');
      return;
    }
    if (regPassword !== regConfirmPassword) {
      setAuthError('As senhas não conferem');
      return;
    }

    // Success simulation
    setReader({
      name: regName.trim(),
      email: regEmail.trim(),
      isLoggedIn: true
    });
    setAuthSuccess('Conta criada e autenticada com sucesso!');
    setTimeout(() => {
      setIsAuthModalOpen(false);
      setAuthSuccess('');
      // Clean up fields
      setRegName('');
      setRegEmail('');
      setRegPassword('');
      setRegConfirmPassword('');
      setPasswordStrength('');
    }, 1200);
  };

  // Process manual login
  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    setAuthError('');
    setAuthSuccess('');

    const trimmedEmail = loginEmail.trim();

    if (!trimmedEmail || !trimmedEmail.includes('@')) {
      setAuthError('Insira um e-mail válido');
      return;
    }
    if (!loginPassword) {
      setAuthError('Insira sua senha de segurança');
      return;
    }

    // Check if the e-mail matches an administrative account
    const emailToCheck = trimmedEmail.toLowerCase();
    const foundAdmin = admins.find(adm => adm.email.toLowerCase() === emailToCheck);

    if (foundAdmin) {
      if (foundAdmin.password === loginPassword) {
        setAuthSuccess('Acesso Administrador Autorizado! Redirecionando...');
        setTimeout(() => {
          setIsAuthModalOpen(false);
          setAuthSuccess('');
          setLoginEmail('');
          setLoginPassword('');
          // Trigger admin state and transition view modes
          onAdminLogin();
          onSwitchToAdmin();
        }, 1200);
        return;
      } else {
        setAuthError('Senha incorreta para esta conta administrativa.');
        return;
      }
    }

    // Simulate standard reader login
    setReader({
      name: trimmedEmail.split('@')[0].split('.').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' '),
      email: trimmedEmail,
      isLoggedIn: true
    });
    setAuthSuccess('Bem-vindo de volta ao TecaVirtual!');
    setTimeout(() => {
      setIsAuthModalOpen(false);
      setAuthSuccess('');
      setLoginEmail('');
      setLoginPassword('');
    }, 1200);
  };

  // Sign out customer
  const handleSignOutReader = () => {
    setReader({ name: '', email: '', isLoggedIn: false });
  };

  // Process checkout submission
  const handleCheckoutSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!reader.isLoggedIn) {
      setIsAuthModalOpen(true);
      return;
    }

    if (!checkoutPhone.trim()) {
      alert('Por favor, informe seu contato telefônico.');
      return;
    }

    // Process reservations & update parent state
    cart.forEach((item) => {
      // Find book to get correct title
      const bookObj = books.find(b => b.id === item.bookId);
      const title = bookObj ? bookObj.title : item.title;

      // Register the Loan record in Parent/LocalStorage state
      onCreateLoan({
        bookId: item.bookId,
        bookTitle: title,
        readerName: reader.name,
        readerContact: reader.email,
        loanDate: new Date().toISOString().split('T')[0],
        dueDate: checkoutDate,
        status: 'ativo'
      });

      // Update book status to borrowed (emprestado) or reserved (reservado)
      onUpdateBookStatus(item.bookId, 'emprestado');
    });

    // Generate Voucher code
    const generatedCode = 'TV-' + Math.floor(100000 + Math.random() * 900000);
    setVoucherCode(generatedCode);

    setLastOrderDetails({
      items: [...cart],
      subtotal: cartSubtotal,
      fee: serviceFee,
      taxes: taxSum,
      total: cartGrandTotal,
      readerName: reader.name,
      readerEmail: reader.email,
      pickupDate: checkoutDate,
      phone: checkoutPhone
    });

    // Clear cart and advance
    setCart([]);
    setIsCartOpen(false);
    setActiveTab('success');
  };

  // Filter books matching search/category
  const filteredBooks = books.filter((book) => {
    const matchesSearch =
      book.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      book.author.toLowerCase().includes(searchQuery.toLowerCase()) ||
      book.isbn.includes(searchQuery) ||
      book.publisher.toLowerCase().includes(searchQuery.toLowerCase());

    const matchesCategory = selectedCategory === 'Todas' || book.category === selectedCategory;

    return matchesSearch && matchesCategory;
  });

  return (
    <div className="w-full max-w-7xl mx-auto px-4 py-6 space-y-6" id="storefront-root">
      
      {/* Editorial Banner & Identity Header */}
      <header className="bg-[#fdfdfc] px-6 py-4 rounded-2xl border border-brand-border shadow-editorial flex flex-col md:flex-row md:items-center justify-between gap-4" id="storefront-header">
        <div className="flex items-center gap-3" id="header-logo-container">
          <div className="w-10 h-10 bg-[#1e4ca5] text-white rounded-lg flex items-center justify-center font-serif font-black text-xl shadow-md">
            T
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="text-lg font-extrabold tracking-tight text-brand-dark">
                Teca<span className="text-brand-secondary">Virtual</span>
              </span>
              <span className="text-[9px] bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                Livraria & Acervo
              </span>
            </div>
            <p className="text-[10px] text-brand-light font-bold uppercase tracking-widest mt-0.5">Explore, Alugue e Reserve Grandes Obras</p>
          </div>
        </div>

        {/* Search Input on header */}
        <div className="flex items-center gap-4 flex-wrap" id="header-actions">
          {/* Reader Profile Details */}
          {reader.isLoggedIn ? (
            <div className="flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-brand-border/60 rounded-full" id="reader-chip">
              <div className="w-6 h-6 rounded-full bg-[#1e4ca5] text-white flex items-center justify-center text-[11px] font-bold">
                {reader.name.charAt(0).toUpperCase()}
              </div>
              <div className="text-left py-0.5 max-w-[120px]">
                <p className="text-[11px] font-bold text-brand-dark truncate leading-none">{reader.name}</p>
                <p className="text-[9px] text-[#5a6f95] truncate mt-0.5">Leitor Ativo</p>
              </div>
              <button
                onClick={handleSignOutReader}
                className="text-xs text-[#5a6f95] hover:text-red-600 font-bold ml-1.5 mr-0.5 cursor-pointer"
                title="Sair da conta do leitor"
              >
                <LogOut className="w-3.5 h-3.5" />
              </button>
            </div>
          ) : (
            <button
              onClick={() => {
                setAuthMode('login');
                setIsAuthModalOpen(true);
              }}
              className="flex items-center gap-1 px-4 py-2 hover:bg-slate-50 border border-brand-border/75 rounded-full text-xs font-bold uppercase tracking-wider text-brand-secondary transition cursor-pointer"
              id="btn-trigger-auth"
            >
              <User className="w-3.5 h-3.5" />
              <span>Minha Conta</span>
            </button>
          )}

          {/* Persistent Shopping Cart Trigger */}
          <button
            onClick={() => setIsCartOpen(!isCartOpen)}
            className="relative flex items-center gap-2 px-4 py-2 bg-[#1e4ca5] hover:bg-[#102a55] text-white rounded-full text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-sm active:scale-95"
            id="btn-toggle-cart"
          >
            <ShoppingCart className="w-4 h-4" />
            <span className="hidden sm:inline">Carrinho</span>
            {cartQtyCount > 0 && (
              <span className="absolute -top-1.5 -right-1.5 bg-red-600 text-white font-extrabold text-[10px] w-5 h-5 rounded-full flex items-center justify-center border border-white animate-bounce-short">
                {cartQtyCount}
              </span>
            )}
          </button>
        </div>
      </header>

      {/* Main view container */}
      <AnimatePresence mode="wait">
        {activeTab === 'catalog' && (
          <motion.div
            key="catalog-view"
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start"
            id="storefront-grid-catalog"
          >
            {/* Left Column: Quick Filter Bar & Instructions */}
            <div className="lg:col-span-3 space-y-6" id="catalog-sidebar">
              {/* Category Options */}
              <div className="bg-[#fdfdfc] p-6 rounded-2xl border border-brand-border shadow-editorial space-y-4">
                <div className="border-b border-brand-border/40 pb-3 flex items-center gap-2 text-[#5a6f95]">
                  <Tags className="w-4 h-4 text-brand-secondary" />
                  <h4 className="text-xs font-bold uppercase tracking-wider">Filtrar por Gênero</h4>
                </div>

                <div className="flex flex-col gap-1.5" id="genders-options-list">
                  <button
                    onClick={() => setSelectedCategory('Todas')}
                    className={`text-left text-xs px-3 py-2 rounded-lg font-bold transition whitespace-nowrap overflow-ellipsis truncate cursor-pointer ${
                      selectedCategory === 'Todas'
                        ? 'bg-[#1e4ca5]/5 text-[#1e4ca5]'
                        : 'text-slate-600 hover:bg-slate-50'
                    }`}
                  >
                    📚 Todos os Gêneros
                  </button>
                  {categories.map((cat) => (
                    <button
                      key={cat}
                      onClick={() => setSelectedCategory(cat)}
                      className={`text-left text-xs px-3 py-2 rounded-lg font-bold transition whitespace-nowrap overflow-ellipsis truncate cursor-pointer ${
                        selectedCategory === cat
                          ? 'bg-[#1e4ca5]/5 text-[#1e4ca5]'
                          : 'text-slate-600 hover:bg-slate-50'
                      }`}
                    >
                      🏷️ {cat}
                    </button>
                  ))}
                </div>
              </div>

              {/* Informative tutorial */}
              <div className="p-6 bg-[#fcfcf9] border border-brand-border/80 rounded-2xl text-brand-dark text-xs space-y-3 shadow-editorial">
                <p className="font-bold flex items-center gap-1.5 text-brand-secondary text-xs uppercase tracking-wider border-b border-brand-border/50 pb-2">
                  <CheckCircle className="w-4 h-4 text-brand-secondary" /> Como funciona o aluguel?
                </p>
                <ol className="list-decimal list-inside space-y-1.5 leading-relaxed text-slate-600">
                  <li>Navegue no acervo e adicione obras ao seu carrinho.</li>
                  <li>Insira suas informações de contato em seu checkout.</li>
                  <li>Retire as obras físicas cadastradas na biblioteca.</li>
                  <li>Devolva no prazo para acumular pontos de leitor!</li>
                </ol>
              </div>
            </div>

            {/* Right Column: Search + Grid of Books */}
            <div className="lg:col-span-9 space-y-6" id="catalog-listing-space">
              {/* Filter controls */}
              <div className="bg-[#fdfdfc] p-6 rounded-2xl border border-brand-border shadow-editorial flex flex-col sm:flex-row sm:items-center gap-4 justify-between" id="catalog-controls">
                <div className="relative flex-1 flex items-center" id="catalog-search-wrapper">
                  <Search className="absolute left-3 w-4 h-4 text-[#5a6f95]" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Pesquisar por título, autor, editora ou ISBN no catálogo..."
                    className="w-full pl-9 pr-4 py-2 border-b-2 border-brand-border/60 text-xs focus:border-[#1e4ca5] outline-none transition-colors bg-transparent text-brand-dark font-medium placeholder-brand-light"
                  />
                </div>
                <div className="text-[10.5px] font-semibold text-[#5a6f95] bg-slate-50 px-3.5 py-1.5 rounded-full border border-brand-border/40 whitespace-nowrap mt-2 sm:mt-0" id="results-count">
                  💡 {filteredBooks.length} livro(s) disponíveis
                </div>
              </div>

              {/* Books Grid */}
              {filteredBooks.length === 0 ? (
                <div className="bg-[#fdfdfc] rounded-2xl border border-brand-border shadow-editorial py-24 text-center px-4" id="empty-catalog">
                  <BookOpen className="w-12 h-12 text-slate-300 mx-auto mb-4 animate-pulse" />
                  <h3 className="text-xl font-normal text-brand-dark font-serif italic">Nenhum título disponível</h3>
                  <p className="text-xs text-brand-light mt-1 max-w-sm mx-auto leading-relaxed">
                    Sua pesquisa ou filtros selecionados não retornaram resultados correspondentes no banco de dados ativo.
                  </p>
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6" id="books-store-grid">
                  {filteredBooks.map((book) => {
                    const price = getBookPrice(book.id, book.year);
                    return (
                      <motion.div
                        key={book.id}
                        layout
                        whileHover={{ y: -3 }}
                        className="bg-[#fdfdfc] border border-brand-border/80 rounded-2xl shadow-editorial overflow-hidden flex flex-col h-full"
                        id={`store-book-${book.id}`}
                      >
                        {/* Dynamic category-colored backdrop banner */}
                        <div className="h-6 flex items-center justify-between px-4 bg-brand-primary/5 border-b border-brand-border/40 text-[9.5px] font-bold text-[#1e4ca5] uppercase tracking-wider">
                          <span>🏷️ {book.category}</span>
                          <span>{book.publisher}</span>
                        </div>

                        {/* Middle detailed area */}
                        <div className="p-5 flex-1 flex flex-col gap-3">
                          <div>
                            <h4 className="text-base font-bold font-serif italic text-brand-dark line-clamp-1 group-hover:text-brand-secondary selection:bg-[#4ade80]" title={book.title}>
                              {book.title}
                            </h4>
                            <p className="text-xs font-semibold text-slate-500 mt-0.5">by {book.author}</p>
                          </div>

                          {/* Cover Simulator visual frame */}
                          <div className="h-32 w-full bg-slate-50 border border-brand-border/30 rounded-lg flex items-center justify-center text-center p-3 text-slate-400 font-serif overflow-hidden relative group">
                            {/* Visual effect decorative */}
                            <div className="absolute top-0 bottom-0 left-2 w-px bg-black/5 shadow-r z-10" />
                            <div className="absolute inset-0 bg-gradient-to-tr from-brand-primary/5 to-transparent pointer-events-none" />
                            <p className="font-serif italic font-bold text-xs text-[#1e4ca5]/70 text-center max-w-[200px] line-clamp-3">
                              {book.title}
                            </p>
                          </div>

                          {book.synopsis && (
                            <p className="text-[11px] text-slate-600 line-clamp-2 md:line-clamp-3 leading-relaxed mt-1" title={book.synopsis}>
                              {book.synopsis}
                            </p>
                          )}

                          <div className="mt-auto pt-3 border-t border-brand-border/40 flex items-center justify-between">
                            <div>
                              <p className="text-[9px] uppercase font-bold text-slate-400 tracking-wider">Valor Unitário</p>
                              <span className="font-bold text-base text-emerald-800 font-mono">
                                R$ {price.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                              </span>
                            </div>
                            
                            {/* Small Status badge */}
                            <span className={`text-[8.5px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-full border ${
                              book.status === 'disponivel'
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                                : book.status === 'emprestado'
                                ? 'bg-amber-50 text-amber-700 border-amber-100'
                                : 'bg-blue-50 text-blue-700 border-blue-100'
                            }`}>
                              {book.status === 'disponivel' ? 'Disponível' : book.status === 'emprestado' ? 'Alugado' : 'Reservado'}
                            </span>
                          </div>
                        </div>

                        {/* Bottom action bar */}
                        <div className="px-5 pb-5 pt-0">
                          {book.status === 'disponivel' ? (
                            <button
                              onClick={() => handleAddToCart(book)}
                              className="w-full py-2.5 bg-[#1e4ca5] hover:bg-[#102a55] text-white rounded-lg text-xs font-bold uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                              <ShoppingCart className="w-3.5 h-3.5" />
                              <span>Alugar / Reservar</span>
                            </button>
                          ) : (
                            <button
                              disabled
                              className="w-full py-2.5 bg-slate-100 text-slate-400 border border-slate-200 rounded-lg text-xs font-bold uppercase tracking-wider cursor-not-allowed flex items-center justify-center gap-1.5"
                            >
                              <span>Livro Temporariamente Alocado</span>
                            </button>
                          )}
                        </div>
                      </motion.div>
                    );
                  })}
                </div>
              )}
            </div>
          </motion.div>
        )}

        {/* Dynamic checkout views */}
        {activeTab === 'checkout' && (
          <motion.div
            key="checkout-view"
            initial={{ opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0 }}
            className="columns-1 max-w-2xl mx-auto space-y-6"
            id="checkout-wrap-all"
          >
            <div className="bg-[#fdfdfc] p-8 md:p-10 rounded-2xl border border-brand-border shadow-editorial">
              <button
                onClick={() => setActiveTab('catalog')}
                className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-[#5a6f95] hover:text-brand-dark transition cursor-pointer mb-6"
              >
                <ArrowLeft className="w-3.5 h-3.5" />
                <span>Voltar à Livraria</span>
              </button>

              <div className="border-b border-brand-border/40 pb-4 mb-6">
                <div className="text-[10px] font-bold text-[#1e4ca5] uppercase tracking-[0.2em] mb-1">Passo Final de Locação</div>
                <h3 className="text-3xl font-normal text-brand-dark font-serif italic">Finalizar Pedido</h3>
                <p className="text-[11px] text-[#5a6f95] mt-1">Simule o checkout e agendamento da entrega.</p>
              </div>

              {/* Guard: check if customer is logged in */}
              {!reader.isLoggedIn ? (
                <div className="bg-amber-50/40 p-6 border border-amber-100 rounded-xl text-center space-y-4">
                  <AlertCircle className="w-8 h-8 text-amber-600 mx-auto" />
                  <div>
                    <h4 className="font-bold text-sm text-amber-900">Conta Requerida</h4>
                    <p className="text-[11.5px] text-slate-600 mt-1 max-w-md mx-auto leading-relaxed">
                      Para realizar reservas e registrar empréstimos no acervo, é necessário ter um cadastro de leitor ativo. Crie sua conta ou faça login em instantes.
                    </p>
                  </div>
                  <div className="flex gap-2 justify-center">
                    <button
                      onClick={() => {
                        setAuthMode('login');
                        setIsAuthModalOpen(true);
                      }}
                      className="px-5 py-2 hover:bg-slate-50 border border-brand-border rounded-full text-xs font-extrabold uppercase tracking-wide text-[#1e4ca5] transition cursor-pointer"
                    >
                      Entrar na Conta
                    </button>
                    <button
                      onClick={() => {
                        setAuthMode('register');
                        setIsAuthModalOpen(true);
                      }}
                      className="px-5 py-2 bg-[#1e4ca5] text-white hover:bg-[#102a55] rounded-full text-xs font-extrabold uppercase tracking-wide transition cursor-pointer"
                    >
                      Criar Conta
                    </button>
                  </div>
                </div>
              ) : (
                <form onSubmit={handleCheckoutSubmit} className="space-y-6">
                  {/* Account detail preview */}
                  <div className="p-4 bg-slate-50/80 border border-brand-border/50 rounded-xl flex items-center justify-between text-xs">
                    <div>
                      <p className="font-bold text-brand-dark">Leitor Selecionado: {reader.name}</p>
                      <p className="text-[10px] text-[#5a6f95] font-mono mt-0.5">{reader.email}</p>
                    </div>
                    <span className="text-[9px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded">Autenticado</span>
                  </div>

                  {/* Books summary */}
                  <div className="space-y-2">
                    <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest border-b border-brand-border/40 pb-1">
                      Obras a Locar ({cartQtyCount})
                    </label>
                    <div className="divide-y divide-brand-border/20 text-xs">
                      {cart.map((item) => (
                        <div key={item.bookId} className="py-2.5 flex items-center justify-between gap-4">
                          <div className="font-serif italic font-bold text-brand-dark truncate pr-4">
                            {item.title} <span className="font-sans text-[10.5px] text-slate-400 not-italic ml-1">x{item.quantity}</span>
                          </div>
                          <span className="font-mono text-slate-600 shrink-0 font-semibold">
                            R$ {(item.price * item.quantity).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Fields for phone and checkout pickup dates */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-1">
                      <label htmlFor="checkoutPhone" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        Telefone de Contato <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="text"
                        id="checkoutPhone"
                        value={checkoutPhone}
                        onChange={(e) => setCheckoutPhone(e.target.value)}
                        placeholder="Ex: (11) 98765-4321"
                        required
                        className="w-full border-b-2 border-brand-border py-1.5 focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                      />
                    </div>

                    <div className="space-y-1">
                      <label htmlFor="checkoutDate" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        Data Prevista para Devolução <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="date"
                        id="checkoutDate"
                        value={checkoutDate}
                        onChange={(e) => setCheckoutDate(e.target.value)}
                        required
                        className="w-full border-b-2 border-brand-border py-1.5 focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark cursor-pointer font-sans"
                      />
                    </div>
                  </div>

                  {/* Payment receipt values overview */}
                  <div className="p-5 border border-brand-border/80 rounded-xl bg-[#fcfcf9] space-y-2 text-xs">
                    <div className="flex justify-between">
                      <span className="text-[#5a6f95]">Soma Subtotal:</span>
                      <span className="font-mono">R$ {cartSubtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-[#5a6f95]">Taxa de Serviço (5%):</span>
                      <span className="font-mono">R$ {serviceFee.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-[#5a6f95]">Impostos de Circulação (10%):</span>
                      <span className="font-mono">R$ {taxSum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    </div>
                    <div className="h-px bg-slate-200 mt-2"></div>
                    <div className="flex justify-between font-bold text-sm pt-1 text-[#102a55]">
                      <span>Valor Total Estimado:</span>
                      <span className="font-mono text-emerald-800">
                        R$ {cartGrandTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                      </span>
                    </div>
                  </div>

                  {/* Submission buttons */}
                  <div className="flex gap-4 justify-end pt-4 border-t border-brand-border/40">
                    <button
                      type="button"
                      onClick={() => setActiveTab('catalog')}
                      className="px-5 py-2 text-xs font-bold uppercase tracking-wider text-slate-600 border border-brand-border hover:bg-slate-50 transition rounded-full cursor-pointer bg-transparent"
                    >
                      Cancelar
                    </button>
                    <button
                      type="submit"
                      className="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full text-xs font-bold uppercase tracking-wider transition cursor-pointer shadow-md shadow-emerald-600/10"
                    >
                      Confirmar Locação / Empréstimo
                    </button>
                  </div>
                </form>
              )}
            </div>
          </motion.div>
        )}

        {/* Voucher screen showing successful checkout */}
        {activeTab === 'success' && lastOrderDetails && (
          <motion.div
            key="success-view"
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="max-w-xl mx-auto space-y-6"
            id="voucher-root"
          >
            <div className="bg-[#fdfdfc] p-8 md:p-10 rounded-2xl border-2 border-dashed border-[#1e4ca5]/30 shadow-editorial text-center space-y-6 relative" id="ticket-frame">
              {/* Decorative top dot cutouts */}
              <div className="absolute -top-3 left-1/2 -translate-x-1/2 flex gap-1 bg-transparent z-20">
                {[...Array(6)].map((_, i) => (
                  <div key={i} className="w-5 h-5 rounded-full bg-[#fbfbf9] border border-brand-border/30" />
                ))}
              </div>

              <div className="flex justify-center">
                <div className="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600 border border-emerald-100">
                  <CheckCircle className="w-8 h-8" />
                </div>
              </div>

              <div>
                <span className="text-[9px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-full uppercase tracking-widest">
                  Reserva Processada
                </span>
                <h3 className="text-3xl font-normal font-serif italic text-brand-dark mt-3">Comprovante Digital</h3>
                <p className="text-xs text-[#5a6f95] mt-1.5 leading-relaxed font-semibold">
                  Sua solicitação de empréstimo físico foi registrada no painel com sucesso! Mostre o código abaixo no guichê.
                </p>
              </div>

              {/* Code */}
              <div className="p-4 bg-slate-50 border-2 border-dashed border-brand-border rounded-xl text-center">
                <p className="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Código de Resgate</p>
                <p className="text-3xl font-extrabold font-mono text-[#102a55] tracking-widest mt-1 select-all hover:bg-slate-100 p-1.5 rounded transition">
                  {voucherCode}
                </p>
              </div>

              {/* Order data sheet */}
              <div className="border border-brand-border/60 rounded-xl overflow-hidden text-left bg-white text-xs divide-y divide-brand-border/30">
                <div className="p-4 bg-slate-50/50 flex justify-between gap-2">
                  <div className="font-bold text-brand-dark">Leitor Beneficiário:</div>
                  <div className="text-right text-[#5a6f95] truncate font-medium">{lastOrderDetails.readerName}</div>
                </div>

                <div className="p-4 flex justify-between gap-2">
                  <div className="font-bold text-brand-dark">Obras Resgatadas:</div>
                  <div className="text-right text-[#5a6f95]">
                    {lastOrderDetails.items.map((item, id) => (
                      <p key={id} className="font-serif italic font-semibold">{item.title} (x{item.quantity})</p>
                    ))}
                  </div>
                </div>

                <div className="p-4 flex justify-between gap-2">
                  <div className="font-bold text-brand-dark">Data para Retirada:</div>
                  <div className="text-right text-[#1e4ca5] font-bold">Imediata (Retirar até {lastOrderDetails.pickupDate})</div>
                </div>

                <div className="p-4 bg-[#fcfcf9]" id="success-value-box">
                  <div className="flex justify-between font-bold text-brand-dark">
                    <span>Custo Estimado no Balcão:</span>
                    <span className="font-mono text-emerald-800">
                      R$ {lastOrderDetails.total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </span>
                  </div>
                </div>
              </div>

              <div className="pt-4 border-t border-brand-border/40 text-[9.5px] font-mono text-slate-400 uppercase tracking-widest">
                TecaVirtual v2.4.0 — Impresso em {new Date().toLocaleDateString('pt-BR')}
              </div>

              <div className="flex justify-center gap-3">
                <button
                  onClick={() => {
                    setActiveTab('catalog');
                    setLastOrderDetails(null);
                  }}
                  className="px-8 py-2.5 bg-[#1e4ca5] hover:bg-[#102a55] text-white rounded-full text-xs font-bold uppercase tracking-wider transition cursor-pointer shadow-md inline-block shadow-brand-secondary/15"
                >
                  Continuar Lendo
                </button>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Slide-over Drawer for Cart (Carrinho) */}
      <AnimatePresence>
        {isCartOpen && (
          <>
            {/* Backdrop click dismiss */}
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 0.4 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsCartOpen(false)}
              className="fixed inset-0 bg-[#102a55] z-40 cursor-pointer"
              id="cart-overlay"
            />

            {/* Sidebar cart drawer panel */}
            <motion.div
              initial={{ x: '100%' }}
              animate={{ x: 0 }}
              exit={{ x: '100%' }}
              transition={{ type: 'tween', duration: 0.35 }}
              className="fixed right-0 top-0 bottom-0 w-full max-w-md bg-[#fdfdfc] z-50 border-l border-brand-border shadow-2xl flex flex-col overflow-hidden text-brand-dark"
              id="cart-drawer"
            >
              <div className="p-6 border-b border-brand-border/45 flex items-center justify-between" id="cart-drawer-header">
                <h4 className="text-xl font-normal text-brand-dark font-serif italic flex items-center gap-2">
                  <ShoppingCart className="w-5 h-5 text-brand-secondary" />
                  <span>Seu Carrinho Ativo</span>
                </h4>
                <button
                  onClick={() => setIsCartOpen(false)}
                  className="p-1 px-2.5 border border-brand-border rounded-full hover:bg-slate-100 hover:text-black transition text-xs font-bold uppercase cursor-pointer"
                  id="btn-close-cart-drawer"
                >
                  Fec
                </button>
              </div>

              {/* List space scroll */}
              <div className="flex-1 overflow-y-auto p-6 space-y-4" id="cart-drawer-items">
                {cart.length === 0 ? (
                  <div className="text-center py-20 px-4 text-slate-400 space-y-3" id="cart-drawer-empty">
                    <ShoppingCart className="w-12 h-12 text-slate-300 mx-auto" />
                    <h5 className="font-serif italic text-base">O carrinho está vazio</h5>
                    <p className="text-[11.5px] leading-relaxed max-w-[240px] mx-auto text-[#5a6f95]">
                      Explore nosso catálogo e alugue livros acadêmicos ou de gestão para começar.
                    </p>
                  </div>
                ) : (
                  cart.map((item) => (
                    <div
                      key={item.bookId}
                      className="p-4 border border-brand-border/70 rounded-xl bg-[#fcfcf9] flex gap-3 relative"
                      id={`cart-drawer-item-${item.bookId}`}
                    >
                      {/* Simple miniature icon column */}
                      <div className="w-10 h-12 bg-slate-100 border border-brand-border/30 rounded flex items-center justify-center shrink-0">
                        📖
                      </div>

                      {/* Detail Column */}
                      <div className="flex-1 space-y-1">
                        <div className="font-serif italic font-bold text-xs text-brand-dark truncate pr-1" title={item.title}>
                          {item.title}
                        </div>
                        <p className="text-[9.5px] font-bold text-slate-400">by {item.author}</p>
                        
                        <div className="font-mono text-[11px] text-emerald-800 font-bold select-none pt-1">
                          R$ {item.price.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                        </div>

                        {/* Quantity change buttons */}
                        <div className="flex items-center gap-1.5 pt-2" id="cart-qty-buttons">
                          <button
                            onClick={() => handleUpdateQty(item.bookId, -1)}
                            className="w-5 h-5 bg-slate-100 hover:bg-[#1e4ca5]/10 hover:text-brand-primary active:scale-90 transition rounded flex items-center justify-center text-[10px] font-bold border border-brand-border/50 cursor-pointer"
                          >
                            <Minus className="w-2.5 h-2.5" />
                          </button>
                          <span className="font-mono text-xs w-6 text-center font-bold">{item.quantity}</span>
                          <button
                            onClick={() => handleUpdateQty(item.bookId, 1)}
                            className="w-5 h-5 bg-slate-100 hover:bg-[#1e4ca5]/10 hover:text-brand-primary active:scale-90 transition rounded flex items-center justify-center text-[10px] font-bold border border-brand-border/50 cursor-pointer"
                          >
                            <Plus className="w-2.5 h-2.5" />
                          </button>
                        </div>
                      </div>

                      {/* Delete button absolutely right list action */}
                      <button
                        onClick={() => handleRemoveItem(item.bookId)}
                        className="absolute right-3 bottom-3 p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-full transition cursor-pointer"
                        title="Remover livro"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  ))
                )}
              </div>

              {/* Bottom calculations & Checkout click block */}
              {cart.length > 0 && (
                <div className="bg-[#fbfbf9]/90 border-t border-brand-border p-6 space-y-4" id="cart-drawer-summary">
                  <div className="space-y-2 text-xs" id="cart-receipt-numbers-sidebar">
                    <div className="flex justify-between">
                      <span className="text-[#5a6f95]">Subtotal:</span>
                      <span className="font-mono">R$ {cartSubtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-[#5a6f95]">Taxa de Serviço (5%):</span>
                      <span className="font-mono">R$ {serviceFee.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-[#5a6f95]">Impostos (10%):</span>
                      <span className="font-mono">R$ {taxSum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                    </div>
                    <div className="h-px bg-slate-200 mt-2"></div>
                    <div className="flex justify-between font-bold text-sm pt-1">
                      <span>Valor Total Estimado:</span>
                      <span className="font-mono text-emerald-800">
                        R$ {cartGrandTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                      </span>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 gap-2" id="cart-drawer-actions">
                    <button
                      onClick={() => {
                        setIsCartOpen(false);
                        setActiveTab('checkout');
                      }}
                      className="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-md shadow-emerald-600/10 active:scale-98 text-center flex items-center justify-center gap-2"
                    >
                      <Receipt className="w-4 h-4 shrink-0" />
                      <span>Fechar & Reservar</span>
                    </button>
                    
                    <button
                      onClick={handleClearCart}
                      className="w-full py-2.5 bg-[#fdfdfc] border border-brand-border text-slate-500 hover:border-red-200 hover:text-red-700 hover:bg-red-50/20 rounded-xl text-xs font-bold uppercase tracking-wider transition cursor-pointer text-center"
                    >
                      <span>Limpar Carrinho</span>
                    </button>
                  </div>
                </div>
              )}
            </motion.div>
          </>
        )}
      </AnimatePresence>

      {/* Account Login/Registration Modal (Criar conta & Entrar do Leitor) */}
      <AnimatePresence>
        {isAuthModalOpen && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-4" id="auth-modal-root">
            {/* Backdrop blur */}
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 0.4 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsAuthModalOpen(false)}
              className="fixed inset-0 bg-[#102a55] cursor-pointer"
            />

            {/* Modal Box */}
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="bg-[#fdfdfc] p-8 md:p-10 rounded-2xl border border-brand-border shadow-2xl relative w-full max-w-md z-1"
              id="auth-modal-card"
            >
              <button
                onClick={() => setIsAuthModalOpen(false)}
                className="absolute right-5 top-5 p-1 text-slate-400 hover:text-black rounded-full"
                title="Fechar modal"
              >
                ✕
              </button>

              {/* Tabs buttons */}
              <div className="flex border-b border-brand-border/40 gap-6 pb-2.5 mb-6" id="auth-modal-tabs">
                <button
                  type="button"
                  onClick={() => {
                    setAuthMode('login');
                    setAuthError('');
                    setAuthSuccess('');
                  }}
                  className={`pb-2 text-xs font-bold uppercase tracking-wide cursor-pointer ${
                    authMode === 'login'
                      ? 'border-b-2 border-[#1e4ca5] text-[#1e4ca5]'
                      : 'text-slate-400 hover:text-brand-dark'
                  }`}
                >
                  Entrar na Conta
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setAuthMode('register');
                    setAuthError('');
                    setAuthSuccess('');
                  }}
                  className={`pb-2 text-xs font-bold uppercase tracking-wide cursor-pointer ${
                    authMode === 'register'
                      ? 'border-b-2 border-[#1e4ca5] text-[#1e4ca5]'
                      : 'text-slate-400 hover:text-brand-dark'
                  }`}
                >
                  Criar Conta Leitor
                </button>
              </div>

              {/* Status messages inside fields */}
              {authError && (
                <div className="p-3 bg-red-50 border border-red-100 text-red-700 text-xs rounded-xl flex items-center gap-2 mb-4">
                  <AlertCircle className="w-4 h-4 shrink-0 text-red-600" />
                  <span>{authError}</span>
                </div>
              )}
              {authSuccess && (
                <div className="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs rounded-xl flex items-center gap-2 mb-4">
                  <CheckCircle className="w-4 h-4 shrink-0 text-emerald-600" />
                  <span>{authSuccess}</span>
                </div>
              )}

              {/* Form rendering */}
              {authMode === 'login' ? (
                // Login Form
                <form onSubmit={handleLogin} className="space-y-4">
                  <div className="space-y-1">
                    <label htmlFor="loginEmail" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                      Seu E-mail <span className="text-red-500">*</span>
                    </label>
                    <div className="relative flex items-center">
                      <Mail className="absolute left-1.5 w-4 h-4 text-brand-light" />
                      <input
                        type="email"
                        id="loginEmail"
                        value={loginEmail}
                        onChange={(e) => setLoginEmail(e.target.value)}
                        placeholder="nome@exemplo.com"
                        required
                        className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                      />
                    </div>
                  </div>

                  <div className="space-y-12 pb-5">
                    <div className="space-y-1">
                      <label htmlFor="loginPassword" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        Senha de Segurança <span className="text-red-500">*</span>
                      </label>
                      <div className="relative flex items-center">
                        <Lock className="absolute left-1.5 w-4 h-4 text-brand-light" />
                        <input
                          type="password"
                          id="loginPassword"
                          value={loginPassword}
                          onChange={(e) => setLoginPassword(e.target.value)}
                          placeholder="••••••••"
                          required
                          className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                        />
                      </div>
                    </div>
                  </div>

                  <button
                    type="submit"
                    className="w-full py-3.5 text-xs font-bold uppercase tracking-wider text-white bg-[#1e4ca5] hover:bg-[#102a55] rounded-xl transition cursor-pointer shadow-md"
                  >
                    Entrar no TecaVirtual
                  </button>
                  <p className="text-[10px] text-center text-[#5a6f95] mt-2 italic">
                    Dica: Use qualquer e-mail/senha desejados para simular
                  </p>
                </form>
              ) : (
                // Register Form matching criarconta.php
                <form onSubmit={handleRegister} className="space-y-4">
                  <div className="space-y-1">
                    <label htmlFor="regName" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                      Nome Completo <span className="text-red-500">*</span>
                    </label>
                    <div className="relative flex items-center">
                      <User className="absolute left-1.5 w-4 h-4 text-brand-light" />
                      <input
                        type="text"
                        id="regName"
                        value={regName}
                        onChange={(e) => setRegName(e.target.value)}
                        placeholder="Seu nome"
                        required
                        className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                      />
                    </div>
                  </div>

                  <div className="space-y-1">
                    <label htmlFor="regEmail" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                      Seu E-mail <span className="text-red-500">*</span>
                    </label>
                    <div className="relative flex items-center">
                      <Mail className="absolute left-1.5 w-4 h-4 text-brand-light" />
                      <input
                        type="email"
                        id="regEmail"
                        value={regEmail}
                        onChange={(e) => setRegEmail(e.target.value)}
                        placeholder="seu@email.com"
                        required
                        className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                      />
                    </div>
                  </div>

                  <div className="space-y-1">
                    <label htmlFor="regPassword" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                      Escolha uma Senha <span className="text-red-500">*</span>
                    </label>
                    <div className="relative flex items-center">
                      <Lock className="absolute left-1.5 w-4 h-4 text-brand-light" />
                      <input
                        type="password"
                        id="regPassword"
                        value={regPassword}
                        onChange={(e) => checkPasswordStrength(e.target.value)}
                        placeholder="••••••••"
                        required
                        className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                      />
                    </div>
                    {/* Visual Password Strength message indicator matching criarconta.php */}
                    {passwordStrength && (
                      <div className={`mt-1.5 p-2 rounded text-[10.5px] font-bold ${passwordStrengthColor}`}>
                        {passwordStrength}
                      </div>
                    )}
                  </div>

                  <div className="space-y-1 pb-4">
                    <div className="space-y-1">
                      <label htmlFor="regConfirmPassword" className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        Confirme sua Senha <span className="text-red-500">*</span>
                      </label>
                      <div className="relative flex items-center">
                        <Lock className="absolute left-1.5 w-4 h-4 text-brand-light" />
                        <input
                          type="password"
                          id="regConfirmPassword"
                          value={regConfirmPassword}
                          onChange={(e) => setRegConfirmPassword(e.target.value)}
                          placeholder="••••••••"
                          required
                          className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-[#1e4ca5] outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                        />
                      </div>
                    </div>
                  </div>

                  <button
                    type="submit"
                    className="w-full py-3.5 text-xs font-bold uppercase tracking-wider text-white bg-[#1e4ca5] hover:bg-[#102a55] rounded-xl transition cursor-pointer shadow-md"
                  >
                    Criar Conta & Acessar
                  </button>

                  <p className="text-[9.5px] text-[#5a6f95] mt-3 leading-relaxed">
                    Ao criar uma conta, você concorda com nossos <a href="#" className="underline font-bold text-[#1e4ca5]">Termos de Serviço</a> e <a href="#" className="underline font-bold text-[#1e4ca5]">Políticas de Privacidade</a> do TecaVirtual.
                  </p>
                </form>
              )}
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Modern page decorative footer */}
      <footer className="pt-6 border-t border-brand-border/40 text-[10px] text-[#5a6f95] font-mono tracking-widest flex flex-col sm:flex-row justify-between uppercase gap-2" id="storefront-page-footer">
        <span>TecaVirtual Storefront v2.4.0</span>
        <span>Modo Ativo: Consulta de Alunos e Leitores</span>
        <span>© {new Date().getFullYear()} TECAVIRTUAL</span>
      </footer>
    </div>
  );
}
