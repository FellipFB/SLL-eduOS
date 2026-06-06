import React, { useState } from 'react';
import { Book, Loan, DashboardStats } from '../types';
import LoansManager from './LoansManager';
import CategoriesManager from './CategoriesManager';
import {
  BookOpen,
  CheckCircle,
  Clock,
  Tags,
  Search,
  Filter,
  Trash2,
  Edit,
  Leaf,
  LogOut,
  ChevronDown,
  Info,
  SlidersHorizontal,
  BookmarkCheck,
  ArrowLeftRight
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface DashboardProps {
  books: Book[];
  loans: Loan[];
  onAddBook: (bookData: Omit<Book, 'id' | 'createdAt'>) => void;
  onEditBook: (id: string, updatedData: Omit<Book, 'id' | 'createdAt'>) => void;
  onDeleteBook: (id: string) => void;
  onLogout: () => void;
  onSelectEdit: (book: Book | null) => void;
  currentEditingBook: Book | null;
  activeTab: 'acervo' | 'locacao' | 'categorias';
  setActiveTab: (tab: 'acervo' | 'locacao' | 'categorias') => void;
  onCreateLoan: (loanData: Omit<Loan, 'id'>) => void;
  onReturnBook: (loanId: string, bookId: string) => void;
  categories: string[];
  onAddCategory: (category: string) => void;
  onRemoveCategory: (category: string) => void;
  children?: React.ReactNode; // For rendering the form in the dashboard
}

export default function Dashboard({
  books,
  loans,
  onAddBook,
  onEditBook,
  onDeleteBook,
  onLogout,
  onSelectEdit,
  currentEditingBook,
  activeTab,
  setActiveTab,
  onCreateLoan,
  onReturnBook,
  categories,
  onAddCategory,
  onRemoveCategory,
  children
}: DashboardProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('Todas');
  const [selectedStatus, setSelectedStatus] = useState('Todos');
  const [confirmDeleteId, setConfirmDeleteId] = useState<string | null>(null);

  // Compute stats
  const totalBooks = books.length;
  const availableBooks = books.filter((b) => b.status === 'disponivel').length;
  const borrowedBooks = books.filter((b) => b.status === 'emprestado').length;
  const uniqueCategories = new Set(books.map((b) => b.category)).size;

  // Filter books matching search/category/status
  const filteredBooks = books.filter((book) => {
    const matchesSearch =
      book.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      book.author.toLowerCase().includes(searchQuery.toLowerCase()) ||
      book.isbn.includes(searchQuery) ||
      book.publisher.toLowerCase().includes(searchQuery.toLowerCase());

    const matchesCategory = selectedCategory === 'Todas' || book.category === selectedCategory;
    const matchesStatus = selectedStatus === 'Todos' || book.status === selectedStatus;

    return matchesSearch && matchesCategory && matchesStatus;
  });

  const handleDeleteClick = (id: string) => {
    setConfirmDeleteId(id);
  };

  const confirmDelete = () => {
    if (confirmDeleteId) {
      onDeleteBook(confirmDeleteId);
      setConfirmDeleteId(null);
    }
  };

  const getStatusBadgeStyle = (status: Book['status']) => {
    switch (status) {
      case 'disponivel':
        return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
      case 'emprestado':
        return 'bg-amber-50 text-amber-700 border border-amber-200';
      case 'reservado':
        return 'bg-blue-50 text-blue-700 border border-blue-200';
      default:
        return 'bg-slate-50 text-slate-700 border border-slate-200';
    }
  };

  const getStatusLabel = (status: Book['status']) => {
    switch (status) {
      case 'disponivel':
        return 'Disponível';
      case 'emprestado':
        return 'Emprestado';
      case 'reservado':
        return 'Reservado';
      default:
        return status;
    }
  };

  return (
    <div id="dashboard-layout" className="w-full max-w-7xl mx-auto px-4 py-8 space-y-8">
      {/* Top Navbar Header */}
      <header id="dashboard-navbar" className="bg-[#fdfdfc] px-8 py-5 rounded-2xl border border-brand-border shadow-editorial flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div className="flex items-center gap-3.5" id="navbar-logo-area">
          <div className="w-11 h-11 bg-brand-accent rounded-lg flex items-center justify-center text-brand-dark shrink-0 shadow-sm font-serif font-black text-2xl" id="logo-icon-box">
            T
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h1 className="text-xl font-extrabold text-brand-dark tracking-tight font-sans">
                Teca<span className="text-brand-secondary">Virtual</span>
              </h1>
              <span className="text-[9px] bg-brand-primary/5 text-brand-primary border border-brand-border/60 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                Painel Admin
              </span>
            </div>
            <p className="text-[10px] text-brand-light font-bold uppercase tracking-widest mt-0.5">Gestão de Acervo e Catalogação</p>
          </div>
        </div>

        <div className="flex items-center gap-4 justify-between md:justify-end" id="navbar-user-area">
          <div className="text-right hidden sm:block">
            <p className="text-sm font-bold text-brand-dark">Administrador Geral</p>
            <p className="text-xs text-brand-light italic">admin@tecavirtual.com</p>
          </div>
          <button
            onClick={onLogout}
            id="btn-logout"
            className="flex items-center gap-2 px-5 py-2.5 border border-brand-border hover:border-red-200 hover:text-red-700 rounded-full text-xs font-bold uppercase tracking-wider transition text-[#566e92] cursor-pointer hover:bg-red-50/50"
            title="Sair do painel administrador"
          >
            <LogOut className="w-3.5 h-3.5" />
            <span>Sair</span>
          </button>
        </div>
      </header>

      {/* Sub-navigation Tabs */}
      <div className="flex border-b border-brand-border/40 gap-8 pb-1 pt-2" id="dashboard-tabs">
        <button
          onClick={() => {
            setActiveTab('acervo');
            onSelectEdit(null); // Cancel current book editorial review when switching tabs
          }}
          className={`pb-3 text-xs font-bold uppercase tracking-widest border-b-2 transition-all cursor-pointer flex items-center gap-2 ${
            activeTab === 'acervo'
              ? 'border-brand-primary border-b-2 text-brand-primary'
              : 'border-transparent text-slate-400 hover:text-brand-dark'
          }`}
        >
          <BookOpen className="w-3.5 h-3.5" />
          <span>Diretório do Acervo</span>
        </button>
        <button
          onClick={() => {
            setActiveTab('locacao');
            onSelectEdit(null); // Cancel active book review
          }}
          className={`pb-3 text-xs font-bold uppercase tracking-widest border-b-2 transition-all cursor-pointer flex items-center gap-2 ${
            activeTab === 'locacao'
              ? 'border-brand-primary border-b-2 text-brand-primary'
              : 'border-transparent text-slate-400 hover:text-brand-dark'
          }`}
        >
          <ArrowLeftRight className="w-3.5 h-3.5" />
          <span>Fila de Locação</span>
        </button>
        <button
          onClick={() => {
            setActiveTab('categorias');
            onSelectEdit(null); // Cancel active book review
          }}
          className={`pb-3 text-xs font-bold uppercase tracking-widest border-b-2 transition-all cursor-pointer flex items-center gap-2 ${
            activeTab === 'categorias'
              ? 'border-brand-primary border-b-2 text-brand-primary'
              : 'border-transparent text-slate-400 hover:text-brand-dark'
          }`}
        >
          <Tags className="w-3.5 h-3.5" />
          <span>Gêneros & Categorias</span>
        </button>
      </div>

      {/* Grid Status Cards - Key Metrics in Editorial Layout */}
      <div id="stats-dashboard-grid" className="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1 */}
        <motion.div
          id="stat-card-total"
          whileHover={{ y: -2 }}
          className="bg-[#fdfdfc] p-6 rounded-2xl border border-brand-border/60 shadow-editorial flex items-center gap-4"
        >
          <div className="w-12 h-12 rounded-xl bg-slate-50 text-[#102a55] flex items-center justify-center shrink-0 border border-brand-border/30">
            <BookOpen className="w-5 h-5" />
          </div>
          <div>
            <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total Acervo</p>
            <p className="text-3xl font-normal font-serif text-brand-dark italic mt-0.5">{totalBooks}</p>
          </div>
        </motion.div>

        {/* Card 2 */}
        <motion.div
          id="stat-card-available"
          whileHover={{ y: -2 }}
          className="bg-[#fdfdfc] p-6 rounded-2xl border border-brand-border/60 shadow-editorial flex items-center gap-4"
        >
          <div className="w-12 h-12 rounded-xl bg-emerald-50/50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
            <CheckCircle className="w-5 h-5" />
          </div>
          <div>
            <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Disponíveis</p>
            <p className="text-3xl font-normal font-serif text-emerald-800 italic mt-0.5">{availableBooks}</p>
          </div>
        </motion.div>

        {/* Card 3 */}
        <motion.div
          id="stat-card-borrowed"
          whileHover={{ y: -2 }}
          className="bg-[#fdfdfc] p-6 rounded-2xl border border-brand-border/60 shadow-editorial flex items-center gap-4"
        >
          <div className="w-12 h-12 rounded-xl bg-amber-50/50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-100">
            <Clock className="w-5 h-5" />
          </div>
          <div>
            <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Emprestados</p>
            <p className="text-3xl font-normal font-serif text-amber-800 italic mt-0.5">{borrowedBooks}</p>
          </div>
        </motion.div>

        {/* Card 4 */}
        <motion.div
          id="stat-card-categories"
          whileHover={{ y: -2 }}
          className="bg-[#fdfdfc] p-6 rounded-2xl border border-brand-border/60 shadow-editorial flex items-center gap-4"
        >
          <div className="w-12 h-12 rounded-xl bg-purple-100/30 text-purple-700 flex items-center justify-center shrink-0 border border-purple-100">
            <Tags className="w-5 h-5" />
          </div>
          <div>
            <p className="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Categorias</p>
            <p className="text-3xl font-normal font-serif text-purple-900 italic mt-0.5">{uniqueCategories}</p>
          </div>
        </motion.div>
      </div>

      {/* Conditionally render Acervo management or Circulations/Loans module */}
      {activeTab === 'acervo' ? (
        <div id="content-main-split" className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* Left Column representing the Form */}
          <div id="left-form-space" className="lg:col-span-5 space-y-6">
            <div id="form-container-in-dashboard">
              {children}
            </div>
            
            {/* Informational Help Box */}
            <div id="info-side-box" className="p-6 bg-[#fdfdfc] border border-brand-border/80 rounded-2xl text-brand-dark text-xs space-y-3 shadow-editorial">
              <p className="font-bold flex items-center gap-1.5 text-brand-secondary text-xs uppercase tracking-wider border-b border-brand-border pb-2">
                <Info className="w-4 h-4 shrink-0 text-brand-secondary" /> Dica de Organização
              </p>
              <p className="leading-relaxed text-slate-600">
                Preencher o registro de <strong className="text-brand-dark">ISBN-13</strong> ajuda a catalogar os volumes com sistemas internacionais unificados. O status de reserva impede conflitos de empréstimo temporários no catálogo ativo.
              </p>
            </div>
          </div>

          {/* Right Column representing the Book Directory & Listings */}
          <div id="right-list-space" className="lg:col-span-7 bg-[#fdfdfc] rounded-2xl border border-brand-border shadow-editorial overflow-hidden flex flex-col">
            {/* Header/Filters area of listings */}
            <div id="listings-controls" className="p-6 border-b border-brand-border/40 space-y-5 bg-[#fbfbf9]/60">
              <div className="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2" id="search-bar-title-row">
                <div>
                  <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">Catálogo Ativo</div>
                  <h3 className="text-2xl font-normal text-brand-dark font-serif italic flex items-center gap-2">
                    Acervo Registrado {books.length > 0 && <span className="font-sans font-semibold text-xs tracking-normal text-brand-light not-italic ml-1">({filteredBooks.length} de {books.length} obras)</span>}
                  </h3>
                </div>
              </div>

              {/* Compact Search and Grid Filters */}
              <div className="flex flex-col sm:flex-row gap-4" id="filters-inner-container">
                {/* Search text input */}
                <div className="relative flex-1 flex items-center" id="search-input-wrapper">
                  <Search className="absolute left-3 w-4 h-4 text-brand-light" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Pesquisar por título, autor, editora ou ISBN..."
                    className="w-full pl-9 pr-4 py-2 border-b-2 border-brand-border/60 text-xs focus:border-brand-primary outline-none transition-colors bg-transparent text-brand-dark font-medium placeholder-brand-light"
                  />
                </div>

                {/* Collapsed filters toggle or dropdown selections */}
                <div className="flex gap-2" id="filters-dropdowns">
                  <div className="relative" id="filter-category-dropdown">
                    <select
                      value={selectedCategory}
                      onChange={(e) => setSelectedCategory(e.target.value)}
                      className="appearance-none bg-transparent font-medium border-b-2 border-brand-border/60 rounded-none pl-2 pr-6 py-2 text-[11px] text-brand-dark cursor-pointer focus:border-brand-primary outline-none max-w-[130px]"
                    >
                      <option value="Todas">📚 Todas</option>
                      {categories.map((cat) => (
                        <option key={cat} value={cat}>
                          {cat}
                        </option>
                      ))}
                    </select>
                    <ChevronDown className="absolute right-1 top-3 w-3 h-3 text-brand-light pointer-events-none" />
                  </div>

                  <div className="relative" id="filter-status-dropdown">
                    <select
                      value={selectedStatus}
                      onChange={(e) => setSelectedStatus(e.target.value)}
                      className="appearance-none bg-transparent font-medium border-b-2 border-brand-border/60 rounded-none pl-2 pr-6 py-2 text-[11px] text-brand-dark cursor-pointer focus:border-brand-primary outline-none"
                    >
                      <option value="Todos">⚖️ Todos Status</option>
                      <option value="disponivel">Disponível</option>
                      <option value="emprestado">Emprestado</option>
                      <option value="reservado">Reservado</option>
                    </select>
                    <ChevronDown className="absolute right-1 top-3 w-3 h-3 text-brand-light pointer-events-none" />
                  </div>
                </div>
              </div>
            </div>

            {/* Table list of books in scholarly Editorial style */}
            <div className="overflow-x-auto" id="table-scroll-container">
              {filteredBooks.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-20 px-4 text-center" id="empty-state-list">
                  <BookOpen className="w-10 h-10 text-slate-300 mb-3" />
                  <h4 className="font-serif italic text-lg text-brand-dark">Nenhum livro encontrado</h4>
                  <p className="text-xs text-brand-light mt-1 max-w-xs leading-relaxed">
                    Modifique os termos de busca, selecione outras categorias ou cadastre novas obras para alimentar seu catálogo.
                  </p>
                </div>
              ) : (
                <table className="w-full text-left border-collapse text-brand-dark" id="books-data-table">
                  <thead>
                    <tr className="bg-slate-50 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500 border-b border-brand-border/60">
                      <th className="py-3 px-6 h-full">Livro & Autor</th>
                      <th className="py-3 px-4 hidden sm:table-cell">Categoria</th>
                      <th className="py-3 px-4 hidden md:table-cell">Identificação</th>
                      <th className="py-3 px-4 text-center">Status</th>
                      <th className="py-3 px-6 text-right">Ações</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-brand-border/40 text-xs">
                    <AnimatePresence initial={false}>
                      {filteredBooks.map((book) => (
                        <motion.tr
                          key={book.id}
                          id={`book-row-${book.id}`}
                          initial={{ opacity: 0 }}
                          animate={{ opacity: 1 }}
                          exit={{ opacity: 0 }}
                          className={`hover:bg-slate-50/50 transition duration-150 ${
                            currentEditingBook?.id === book.id ? 'bg-amber-50/50 hover:bg-amber-50/60' : ''
                          }`}
                        >
                          {/* Title & Author column */}
                          <td className="py-4 px-6">
                            <div className="font-serif font-bold text-slate-900 text-sm italic group hover:text-brand-secondary transition-colors line-clamp-1">{book.title}</div>
                            <div className="text-slate-500 mt-0.5 font-medium">{book.author}</div>
                            {/* Mobile-only secondary metrics */}
                            <div className="block sm:hidden text-[10px] text-brand-light mt-1 space-y-0.5">
                              <span>🏷️ {book.category}</span>
                              {book.isbn && <span className="ml-2">🆔 {book.isbn}</span>}
                            </div>
                          </td>

                          {/* Category column */}
                          <td className="py-4 px-4 hidden sm:table-cell">
                            <span className="font-semibold text-slate-700 bg-slate-100 rounded px-2.5 py-0.5 text-[10px] uppercase tracking-wider">
                              {book.category}
                            </span>
                          </td>

                          {/* ISBN & publisher/year */}
                          <td className="py-4 px-4 hidden md:table-cell">
                            <div className="font-mono text-slate-600 text-[11px]">{book.isbn || 'Sem Registro'}</div>
                            <div className="text-[10px] text-brand-light mt-0.5 font-medium">
                              {book.publisher} ({book.year})
                            </div>
                          </td>

                          {/* Status badge column */}
                          <td className="py-4 px-4 text-center">
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-wider uppercase border ${getStatusBadgeStyle(book.status)}`}>
                              {getStatusLabel(book.status)}
                            </span>
                          </td>

                          {/* Actions column */}
                          <td className="py-4 px-6 text-right whitespace-nowrap">
                            {confirmDeleteId === book.id ? (
                              <div className="flex items-center justify-end gap-1.5" id={`confirm-actions-${book.id}`}>
                                <span className="text-[10px] font-bold text-red-600 mr-1 animate-pulse">Excluir?</span>
                                <button
                                  onClick={confirmDelete}
                                  className="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-bold hover:bg-red-700 transition cursor-pointer"
                                >
                                  Sim
                                </button>
                                <button
                                  onClick={() => setConfirmDeleteId(null)}
                                  className="px-2 py-1 bg-slate-200 text-brand-dark rounded text-[10px] font-bold hover:bg-slate-300 transition cursor-pointer"
                                >
                                  Não
                                </button>
                              </div>
                            ) : (
                              <div className="flex items-center justify-end gap-2" id={`action-buttons-${book.id}`}>
                                <button
                                  onClick={() => onSelectEdit(book)}
                                  className="p-1.5 text-slate-400 hover:text-brand-primary hover:bg-[#f0f4f8] rounded-full transition cursor-pointer"
                                  title="Editar livro"
                                >
                                  <Edit className="w-3.5 h-3.5" />
                                </button>
                                <button
                                  onClick={() => handleDeleteClick(book.id)}
                                  className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-full transition cursor-pointer"
                                  title="Excluir livro"
                                >
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              </div>
                            )}
                          </td>
                        </motion.tr>
                      ))}
                    </AnimatePresence>
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>
      ) : activeTab === 'locacao' ? (
        <LoansManager
          books={books}
          loans={loans}
          onCreateLoan={onCreateLoan}
          onReturnBook={onReturnBook}
          onClose={() => setActiveTab('acervo')}
        />
      ) : (
        <CategoriesManager
          books={books}
          categories={categories}
          onAddCategory={onAddCategory}
          onRemoveCategory={onRemoveCategory}
          onClose={() => setActiveTab('acervo')}
        />
      )}

      {/* Decorative Editorial Page Bottom Footer */}
      <footer className="pt-6 border-t border-brand-border/40 text-[10px] text-slate-400 font-mono tracking-widest flex flex-col sm:flex-row justify-between uppercase gap-2" id="panel-bottom-footer-decor">
        <span>TecaVirtual Management v2.4.0</span>
        <span>Painel Ativo: Biblioteca / Acervo Administrativo</span>
        <span>© 2026 TECAVIRTUAL</span>
      </footer>
    </div>
  );
}
