import React, { useState, useEffect } from 'react';
import { Book } from '../types';
import { Save, X, CheckCircle } from 'lucide-react';
import { motion } from 'motion/react';

interface BookFormProps {
  onSubmit: (bookData: Omit<Book, 'id' | 'createdAt'>) => void;
  initialBook?: Book | null;
  onCancel?: () => void;
  categories: string[];
}

export default function BookForm({ onSubmit, initialBook, onCancel, categories }: BookFormProps) {
  const [title, setTitle] = useState('');
  const [author, setAuthor] = useState('');
  const [isbn, setIsbn] = useState('');
  const [publisher, setPublisher] = useState('');
  const [year, setYear] = useState(new Date().getFullYear());
  const [category, setCategory] = useState('');
  const [status, setStatus] = useState<Book['status']>('disponivel');
  const [synopsis, setSynopsis] = useState('');

  const [errors, setErrors] = useState<Record<string, string>>({});

  // Set default category when categories list changes or is initialized
  useEffect(() => {
    if (categories && categories.length > 0 && !category) {
      setCategory(categories[0]);
    }
  }, [categories, category]);

  useEffect(() => {
    if (initialBook) {
      setTitle(initialBook.title);
      setAuthor(initialBook.author);
      setIsbn(initialBook.isbn || '');
      setPublisher(initialBook.publisher);
      setYear(initialBook.year);
      setCategory(initialBook.category);
      setStatus(initialBook.status);
      setSynopsis(initialBook.synopsis || '');
    } else {
      resetForm();
    }
  }, [initialBook]);

  const resetForm = () => {
    setTitle('');
    setAuthor('');
    setIsbn('');
    setPublisher('');
    setYear(new Date().getFullYear());
    if (categories && categories.length > 0) {
      setCategory(categories[0]);
    } else {
      setCategory('');
    }
    setStatus('disponivel');
    setSynopsis('');
    setErrors({});
  };

  const validate = (): boolean => {
    const tempErrors: Record<string, string> = {};
    if (!title.trim()) tempErrors.title = 'O título do livro é disponível / obrigatório.';
    if (!author.trim()) tempErrors.author = 'O nome do autor é obrigatório.';
    if (!publisher.trim()) tempErrors.publisher = 'A editora é obrigatória.';
    
    const currentYear = new Date().getFullYear();
    if (!year || year < 1000 || year > currentYear + 2) {
      tempErrors.year = `O ano deve ser entre 1000 e ${currentYear + 2}.`;
    }

    if (isbn.trim()) {
      const isbnClean = isbn.replace(/[-\s]/g, '');
      if (isbnClean.length !== 10 && isbnClean.length !== 13) {
        tempErrors.isbn = 'O ISBN deve conter 10 ou 13 dígitos.';
      }
    }

    setErrors(tempErrors);
    return Object.keys(tempErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (validate()) {
      onSubmit({
        title: title.trim(),
        author: author.trim(),
        isbn: isbn.trim(),
        publisher: publisher.trim(),
        year: Number(year),
        category,
        status,
        synopsis: synopsis.trim()
      });
      if (!initialBook) {
        resetForm();
      }
    }
  };

  return (
    <motion.div
      id="book-form-card"
      initial={{ opacity: 0, y: 15 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -15 }}
      className="bg-[#fdfdfc] p-8 rounded-2xl border border-brand-border shadow-editorial flex flex-col"
    >
      <div className="flex items-center justify-between mb-8 border-b border-brand-border/40 pb-4" id="form-heading-row">
        <div>
          <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
            {initialBook ? 'Revisão Editorial' : 'Novo Registro'}
          </div>
          <h3 className="text-2xl font-normal text-brand-dark font-serif italic flex items-center gap-2" id="form-heading-text">
            {initialBook ? (
              <span className="text-yellow-600">✏️ Editar Volume</span>
            ) : (
              <span>Cadastro de Acervo</span>
            )}
          </h3>
        </div>
        {onCancel && (
          <button
            type="button"
            id="btn-cancel-form"
            onClick={onCancel}
            className="p-1.5 rounded-full text-brand-light hover:bg-[#f0f4f8] hover:text-brand-dark transition cursor-pointer"
            title="Cancelar formulário"
          >
            <X className="w-5 h-5" />
          </button>
        )}
      </div>

      <div className="flex flex-col md:flex-row gap-8 items-start mb-6" id="form-layout-with-cover">
        {/* Decorative Cover Section */}
        <div className="w-full md:w-44 shrink-0 sm:block" id="form-cover-column">
          <div className="aspect-[3/4] w-full bg-[#f0f4f8] rounded-xl border-2 border-dashed border-[#d2deed] flex flex-col items-center justify-center text-center p-4 text-slate-400">
            <svg className="w-8 h-8 text-brand-light/70 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <div className="text-[10px] font-bold uppercase tracking-wider text-brand-light">Capa do Livro</div>
            <div className="text-[9px] text-brand-light/75 mt-0.5">Identificador Digital</div>
          </div>
        </div>

        {/* Input Details */}
        <form onSubmit={handleSubmit} className="flex-1 space-y-6" id="form-book-cadastro">
          {/* Row 1: Title and Author */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5" id="form-fields-grid-1">
            <div className="space-y-1" id="field-title">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                Título da Obra <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Ex: O Senhor dos Anéis"
                className={`w-full border-b-2 py-1.5 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium ${
                  errors.title ? 'border-red-400 text-red-700' : 'border-brand-border text-brand-dark'
                }`}
              />
              {errors.title && <p className="text-[10px] text-red-500" id="error-title">{errors.title}</p>}
            </div>

            <div className="space-y-1" id="field-author">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                Autor <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={author}
                onChange={(e) => setAuthor(e.target.value)}
                placeholder="Ex: J.R.R. Tolkien"
                className={`w-full border-b-2 py-1.5 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium ${
                  errors.author ? 'border-red-400 text-red-700' : 'border-brand-border text-brand-dark'
                }`}
              />
              {errors.author && <p className="text-[10px] text-red-500" id="error-author">{errors.author}</p>}
            </div>
          </div>

          {/* Row 2: Genre/Category and Catalog Status */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5" id="form-fields-grid-2">
            <div className="space-y-1" id="field-category">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                Gênero / Categoria
              </label>
              <select
                value={category}
                onChange={(e) => setCategory(e.target.value)}
                className="w-full border-b-2 border-brand-border py-1.5 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium text-brand-dark cursor-pointer"
              >
                {categories.length === 0 ? (
                  <option value="">Sem categorias cadastradas</option>
                ) : (
                  categories.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))
                )}
              </select>
            </div>

            <div className="space-y-1" id="field-status">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                Status no Catálogo
              </label>
              <select
                value={status}
                onChange={(e) => setStatus(e.target.value as Book['status'])}
                className="w-full border-b-2 border-brand-border py-1.5 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium text-brand-dark cursor-pointer font-sans"
              >
                <option value="disponivel">🟢 Disponível para Empréstimo</option>
                <option value="emprestado">🟡 Sob Empréstimo Ativo</option>
                <option value="reservado">🔵 Reservado / Aguardando</option>
              </select>
            </div>
          </div>

          {/* Row 3: Publisher, Publication Year, and ISBN */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5" id="form-fields-grid-3">
            <div className="space-y-1" id="field-publisher">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                Editora <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={publisher}
                onChange={(e) => setPublisher(e.target.value)}
                placeholder="Ex: HarperCollins"
                className={`w-full border-b-2 py-1.5 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium ${
                  errors.publisher ? 'border-red-400 text-red-700' : 'border-brand-border text-brand-dark'
                }`}
              />
              {errors.publisher && <p className="text-[10px] text-red-500" id="error-publisher">{errors.publisher}</p>}
            </div>

            <div className="space-y-1" id="field-year">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                Ano de Publicação <span className="text-red-500">*</span>
              </label>
              <input
                type="number"
                value={year}
                onChange={(e) => setYear(Number(e.target.value))}
                placeholder="Ex: 2023"
                className={`w-full border-b-2 py-1.5 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium ${
                  errors.year ? 'border-red-400 text-red-700' : 'border-brand-border text-brand-dark'
                }`}
              />
              {errors.year && <p className="text-[10px] text-red-500" id="error-year">{errors.year}</p>}
            </div>

            <div className="space-y-1" id="field-isbn">
              <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                ISBN-13 ou Registro
              </label>
              <input
                type="text"
                value={isbn}
                onChange={(e) => setIsbn(e.target.value)}
                placeholder="978-8533613379"
                className={`w-full border-b-2 py-1.5 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium font-mono ${
                  errors.isbn ? 'border-red-400 text-red-700' : 'border-brand-border text-brand-dark'
                }`}
              />
              {errors.isbn && <p className="text-[10px] text-red-500" id="error-isbn">{errors.isbn}</p>}
            </div>
          </div>

          {/* Row 4: Synopsis / Editorial Observations */}
          <div className="space-y-1" id="field-synopsis">
            <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
              Resumo / Observações Editorial
            </label>
            <textarea
              value={synopsis}
              onChange={(e) => setSynopsis(e.target.value)}
              placeholder="Descreva brevemente a sinopse do livro, estado de conservação ou anotações internas."
              rows={4}
              className="w-full bg-[#f8fafc] border border-brand-border/60 rounded-xl p-3 text-sm focus:border-brand-primary outline-none transition-colors resize-none text-brand-dark"
            />
          </div>

          {/* Centered Actions button(s) */}
          <div className="flex gap-3 justify-center pt-4 border-t border-brand-border/40" id="form-actions-row">
            {initialBook && onCancel && (
              <button
                type="button"
                id="btn-cancel-edit"
                onClick={onCancel}
                className="px-5 py-2 text-xs font-bold uppercase tracking-wider text-[#566e92] border border-[#d2deed] hover:bg-slate-50 transition rounded-full cursor-pointer"
              >
                Cancelar
              </button>
            )}
            <button
              type="submit"
              id="btn-submit-cadastro"
              className="px-8 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-brand-primary hover:bg-brand-secondary transition rounded-full flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-brand-primary/10 min-w-[200px]"
            >
              {initialBook ? (
                <>
                  <CheckCircle className="w-4 h-4" /> Salvar Alterações
                </>
              ) : (
                <>
                  <Save className="w-4 h-4" /> Registrar Obra
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </motion.div>
  );
}
