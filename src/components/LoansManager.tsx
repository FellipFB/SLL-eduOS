import React, { useState } from 'react';
import { Book, Loan } from '../types';
import { Calendar, User, Phone, CheckCircle, Clock, AlertTriangle, PlusCircle, Search, ToggleLeft, ToggleRight, X, BookmarkCheck, ArrowLeftRight } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface LoansManagerProps {
  books: Book[];
  loans: Loan[];
  onCreateLoan: (loanData: Omit<Loan, 'id'>) => void;
  onReturnBook: (loanId: string, bookId: string) => void;
  onClose: () => void;
}

export default function LoansManager({ books, loans, onCreateLoan, onReturnBook, onClose }: LoansManagerProps) {
  const [readerName, setReaderName] = useState('');
  const [readerContact, setReaderContact] = useState('');
  const [selectedBookId, setSelectedBookId] = useState('');
  
  // Set default due date to 14 days from now
  const getDefaultDueDate = () => {
    const date = new Date();
    date.setDate(date.getDate() + 14);
    return date.toISOString().split('T')[0];
  };
  const [dueDate, setDueDate] = useState(getDefaultDueDate());
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [loanSearch, setLoanSearch] = useState('');
  const [filterActiveOnly, setFilterActiveOnly] = useState(true);

  // Available books for new loans (status must be 'disponivel')
  const availableBooks = books.filter(b => b.status === 'disponivel');

  const validate = (): boolean => {
    const tempErrors: Record<string, string> = {};
    if (!readerName.trim()) tempErrors.readerName = 'Nome do leitor é obrigatório.';
    if (!readerContact.trim()) tempErrors.readerContact = 'Contato do leitor é obrigatório.';
    if (!selectedBookId) tempErrors.selectedBookId = 'Selecione um volume disponível.';
    if (!dueDate) tempErrors.dueDate = 'Defina a data prevista de devolução.';

    setErrors(tempErrors);
    return Object.keys(tempErrors).length === 0;
  };

  const handleRegisterLoan = (e: React.FormEvent) => {
    e.preventDefault();
    if (validate()) {
      const selectedBook = books.find(b => b.id === selectedBookId);
      if (!selectedBook) return;

      onCreateLoan({
        bookId: selectedBookId,
        bookTitle: selectedBook.title,
        readerName: readerName.trim(),
        readerContact: readerContact.trim(),
        loanDate: new Date().toISOString().split('T')[0],
        dueDate: dueDate,
        status: 'ativo'
      });

      // Reset form
      setReaderName('');
      setReaderContact('');
      setSelectedBookId('');
      setDueDate(getDefaultDueDate());
      setErrors({});
    }
  };

  // Filter loans based on search and status
  const filteredLoans = loans.filter((loan) => {
    const matchesSearch = 
      loan.readerName.toLowerCase().includes(loanSearch.toLowerCase()) ||
      loan.bookTitle.toLowerCase().includes(loanSearch.toLowerCase()) ||
      loan.readerContact.includes(loanSearch);

    const matchesStatus = filterActiveOnly ? loan.status === 'ativo' : true;
    return matchesSearch && matchesStatus;
  });

  const getLoanStatusBadge = (status: Loan['status']) => {
    switch (status) {
      case 'ativo':
        return 'bg-amber-50 text-amber-800 border border-amber-200';
      case 'devolvido':
        return 'bg-emerald-50 text-emerald-800 border border-emerald-200';
      case 'atrasado':
        return 'bg-red-50 text-red-800 border border-red-200';
      default:
        return 'bg-slate-50 text-slate-800 border border-slate-200';
    }
  };

  const getLoanStatusLabel = (status: Loan['status']) => {
    switch (status) {
      case 'ativo':
        return 'Emprestado';
      case 'devolvido':
        return 'Devolvido';
      case 'atrasado':
        return 'Atrasado';
      default:
        return status;
    }
  };

  return (
    <motion.div
      id="loans-manager-view"
      initial={{ opacity: 0, y: 15 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -15 }}
      className="space-y-6"
    >
      {/* Mini Breadcrumb Header */}
      <div className="flex items-center justify-between" id="loans-section-top">
        <button
          onClick={onClose}
          id="btn-back-to-books"
          className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-secondary hover:text-brand-dark transition cursor-pointer"
        >
          <span>← Voltar ao Acervo Principal</span>
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="loans-split-layout">
        
        {/* Left Column: Form to Issue New Loan */}
        <div className="lg:col-span-5 space-y-6" id="loans-form-left">
          <div className="bg-[#fdfdfc] p-8 rounded-2xl border border-brand-border shadow-editorial flex flex-col">
            <div className="border-b border-brand-border/40 pb-4 mb-6">
              <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                Serviço de Circulação
              </div>
              <h3 className="text-2xl font-normal text-brand-dark font-serif italic flex items-center gap-2">
                Conceder Empréstimo
              </h3>
            </div>

            {availableBooks.length === 0 ? (
              <div className="p-6 bg-amber-50/50 border border-amber-100 rounded-xl text-center text-xs space-y-2" id="no-books-available-alert">
                <AlertTriangle className="w-8 h-8 text-amber-600 mx-auto" />
                <p className="font-bold text-amber-900 text-sm">Biblioteca Indisponível</p>
                <p className="text-slate-600 leading-relaxed">
                  Não há exemplares no status <strong>Disponível</strong> no momento. Registre novos volumes ou faça a devolução dos ativos para liberar empréstimos.
                </p>
              </div>
            ) : (
              <form onSubmit={handleRegisterLoan} className="space-y-5" id="form-conceder-emp">
                {/* Book selection */}
                <div className="space-y-1" id="loan-field-book">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    Selecione a Obra <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={selectedBookId}
                    onChange={(e) => setSelectedBookId(e.target.value)}
                    className={`w-full border-b-2 py-1.5 focus:border-brand-primary outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark cursor-pointer ${
                      errors.selectedBookId ? 'border-red-400' : 'border-brand-border'
                    }`}
                  >
                    <option value="">-- Escolha um livro do catálogo --</option>
                    {availableBooks.map((book) => (
                      <option key={book.id} value={book.id}>
                        📖 {book.title} ({book.author})
                      </option>
                    ))}
                  </select>
                  {errors.selectedBookId && <p className="text-[10px] text-red-500">{errors.selectedBookId}</p>}
                </div>

                {/* Reader Name */}
                <div className="space-y-1" id="loan-field-reader">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    Nome Completo do Leitor <span className="text-red-500">*</span>
                  </label>
                  <div className="relative flex items-center">
                    <User className="absolute left-1 w-4 h-4 text-brand-light" />
                    <input
                      type="text"
                      value={readerName}
                      onChange={(e) => setReaderName(e.target.value)}
                      placeholder="Ex: Carlos Eduardo de Souza"
                      className={`w-full pl-7 pr-4 py-1.5 border-b-2 focus:border-brand-primary outline-none text-xs transition-colors bg-transparent font-medium ${
                        errors.readerName ? 'border-red-400 text-red-700' : 'border-brand-border text-brand-dark'
                      }`}
                    />
                  </div>
                  {errors.readerName && <p className="text-[10px] text-red-500">{errors.readerName}</p>}
                </div>

                {/* Reader Contact */}
                <div className="space-y-1" id="loan-field-contact">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    Contato / E-mail ou Telefone <span className="text-red-500">*</span>
                  </label>
                  <div className="relative flex items-center">
                    <Phone className="absolute left-1 w-4 h-4 text-brand-light" />
                    <input
                      type="text"
                      value={readerContact}
                      onChange={(e) => setReaderContact(e.target.value)}
                      placeholder="Ex: (11) 98765-4321 / carlos@email.com"
                      className={`w-full pl-7 pr-4 py-1.5 border-b-2 focus:border-brand-primary outline-none text-xs transition-colors bg-transparent font-medium ${
                        errors.readerContact ? 'border-red-400 text-red-700' : 'border-brand-border text-brand-dark'
                      }`}
                    />
                  </div>
                  {errors.readerContact && <p className="text-[10px] text-red-500">{errors.readerContact}</p>}
                </div>

                {/* Return Date limit */}
                <div className="space-y-1" id="loan-field-duedate">
                  <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    Atraso Previsto / Data de Devolução <span className="text-red-500">*</span>
                  </label>
                  <div className="relative flex items-center">
                    <Calendar className="absolute left-1 w-4 h-4 text-brand-light" />
                    <input
                      type="date"
                      value={dueDate}
                      onChange={(e) => setDueDate(e.target.value)}
                      className={`w-full pl-7 pr-4 py-1.5 border-b-2 focus:border-brand-primary outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark ${
                        errors.dueDate ? 'border-red-400' : 'border-brand-border'
                      }`}
                    />
                  </div>
                  {errors.dueDate && <p className="text-[10px] text-red-500">{errors.dueDate}</p>}
                  <p className="text-[9px] text-slate-400 italic">Prazo recomendado de 14 dias úteis.</p>
                </div>

                <button
                  type="submit"
                  id="btn-registra-locacao"
                  className="w-full py-3 text-xs font-bold uppercase tracking-wider text-white bg-brand-primary hover:bg-brand-secondary rounded-full class-transition cursor-pointer shadow-md shadow-brand-primary/15 flex items-center justify-center gap-2"
                >
                  <PlusCircle className="w-4 h-4" /> Conceder Empréstimo
                </button>
              </form>
            )}
          </div>
        </div>

        {/* Right Column: Historical / Active Loans Listing */}
        <div className="lg:col-span-7 bg-[#fdfdfc] rounded-2xl border border-brand-border shadow-editorial overflow-hidden flex flex-col" id="loans-list-right">
          
          <div className="p-6 border-b border-brand-border/40 space-y-5 bg-[#fbfbf9]/60">
            <div>
              <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                Acompanhamento Ativo
              </div>
              <h3 className="text-2xl font-normal text-brand-dark font-serif italic flex items-center gap-2">
                Controle de Locação {loans.length > 0 && <span className="font-sans font-semibold text-xs tracking-normal text-brand-light not-italic ml-1">({filteredLoans.length} registros)</span>}
              </h3>
            </div>

            {/* Controls of loans (Search + Toggle status active only) */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4" id="filters-loans-panel">
              <div className="relative flex-1 flex items-center" id="search-loans-wrapper">
                <Search className="absolute left-3 w-4 h-4 text-brand-light" />
                <input
                  type="text"
                  value={loanSearch}
                  onChange={(e) => setLoanSearch(e.target.value)}
                  placeholder="Pesquisar por leitor, livro ou contato..."
                  className="w-full pl-9 pr-4 py-2 border-b-2 border-brand-border/60 text-xs focus:border-brand-primary outline-none transition-colors bg-transparent text-brand-dark font-medium placeholder-brand-light"
                />
              </div>

              <button
                type="button"
                onClick={() => setFilterActiveOnly(!filterActiveOnly)}
                className="flex items-center gap-2 text-[10px] uppercase font-bold tracking-wider text-[#566e92]"
              >
                <span>Apenas Ativos</span>
                {filterActiveOnly ? (
                  <ToggleRight className="w-7 h-7 text-emerald-600 cursor-pointer" />
                ) : (
                  <ToggleLeft className="w-7 h-7 text-slate-400 cursor-pointer" />
                )}
              </button>
            </div>
          </div>

          {/* Table List */}
          <div className="overflow-x-auto" id="table-loans-wrapper">
            {filteredLoans.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-20 px-4 text-center" id="empty-state-loans">
                <ArrowLeftRight className="w-10 h-10 text-slate-300 mb-3" />
                <h4 className="font-serif italic text-lg text-brand-dark">Nenhum empréstimo ativo</h4>
                <p className="text-xs text-brand-light mt-1 max-w-xs leading-relaxed">
                  Não existem alocações registradas sob as regras aplicadas. Utilize o formulário à esquerda para realizar o primeiro empréstimo.
                </p>
              </div>
            ) : (
              <table className="w-full text-left border-collapse text-brand-dark" id="loans-data-table">
                <thead>
                  <tr className="bg-slate-50 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-500 border-b border-brand-border/60">
                    <th className="py-3 px-6">Leitor / Contato</th>
                    <th className="py-3 px-4">Livro Concedido</th>
                    <th className="py-3 px-4 hidden sm:table-cell">Datas</th>
                    <th className="py-3 px-4 text-center">Status</th>
                    <th className="py-3 px-6 text-right">Ações</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-brand-border/40 text-xs">
                  <AnimatePresence initial={false}>
                    {filteredLoans.map((loan) => (
                      <motion.tr
                        key={loan.id}
                        id={`loan-row-${loan.id}`}
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="hover:bg-slate-50/50 transition duration-150"
                      >
                        <td className="py-4 px-6">
                          <div className="font-bold text-slate-900 text-sm">{loan.readerName}</div>
                          <div className="text-slate-500 mt-0.5 font-mono text-[10px]">{loan.readerContact}</div>
                        </td>

                        <td className="py-4 px-4">
                          <div className="font-serif font-bold text-brand-dark italic">{loan.bookTitle}</div>
                        </td>

                        <td className="py-4 px-4 hidden sm:table-cell whitespace-nowrap">
                          <div className="text-slate-600 text-[10px] font-medium">📅 Retirada: {loan.loanDate}</div>
                          <div className="text-amber-700 text-[10px] font-bold mt-0.5">⌛ Entrega: {loan.dueDate}</div>
                          {loan.returnDate && (
                            <div className="text-emerald-700 text-[10px] font-bold mt-0.5">✅ Devolvido: {loan.returnDate}</div>
                          )}
                        </td>

                        <td className="py-4 px-4 text-center">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-wider uppercase border ${getLoanStatusBadge(loan.status)}`}>
                            {getLoanStatusLabel(loan.status)}
                          </span>
                        </td>

                        <td className="py-4 px-6 text-right whitespace-nowrap">
                          {loan.status === 'ativo' ? (
                            <button
                              onClick={() => onReturnBook(loan.id, loan.bookId)}
                              className="px-4 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white bg-emerald-600 hover:bg-emerald-700 rounded-full transition-all cursor-pointer shadow-sm shadow-emerald-600/10 active:scale-[0.97]"
                            >
                              Devolver
                            </button>
                          ) : (
                            <span className="text-slate-400 italic text-[11px] font-medium">Finalizado</span>
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
    </motion.div>
  );
}
