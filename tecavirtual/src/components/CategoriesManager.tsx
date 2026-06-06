import React, { useState } from 'react';
import { Book } from '../types';
import { PlusCircle, Trash2, Tags, Info, ArrowLeft, Bookmark, FolderOpen, HelpCircle } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface CategoriesManagerProps {
  books: Book[];
  categories: string[];
  onAddCategory: (category: string) => void;
  onRemoveCategory: (category: string) => void;
  onClose: () => void;
}

export default function CategoriesManager({
  books,
  categories,
  onAddCategory,
  onRemoveCategory,
  onClose
}: CategoriesManagerProps) {
  const [newCat, setNewCat] = useState('');
  const [errorCode, setErrorCode] = useState<string | null>(null);

  // Calculate book counts for each category
  const getBookCount = (catName: string): number => {
    return books.filter((b) => b.category.toLowerCase() === catName.toLowerCase()).length;
  };

  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const trimmed = newCat.trim();
    if (!trimmed) {
      setErrorCode('Nome da categoria não pode ser vazio.');
      return;
    }

    const exists = categories.some((c) => c.toLowerCase() === trimmed.toLowerCase());
    if (exists) {
      setErrorCode('Esta categoria ou gênero já encontra-se registrado.');
      return;
    }

    onAddCategory(trimmed);
    setNewCat('');
    setErrorCode(null);
  };

  const handleDeleteCategory = (cat: string) => {
    const bookCount = getBookCount(cat);
    if (bookCount > 0) {
      setErrorCode(`Não é possível excluir "${cat}" pois existem ${bookCount} livro(s) vinculados a ela.`);
      return;
    }
    onRemoveCategory(cat);
    setErrorCode(null);
  };

  return (
    <motion.div
      id="categories-manager-view"
      initial={{ opacity: 0, y: 15 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -15 }}
      className="space-y-6"
    >
      {/* Top Back Nav link */}
      <div className="flex items-center justify-between" id="categories-top-bar">
        <button
          onClick={onClose}
          id="btn-back-to-acervo"
          className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-secondary hover:text-brand-dark transition cursor-pointer"
        >
          <span>← Voltar ao Acervo Principal</span>
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="categories-split-layout">
        
        {/* Left Column: Form to register a new category */}
        <div className="lg:col-span-5 space-y-6" id="categories-form-left">
          <div className="bg-[#fdfdfc] p-8 rounded-2xl border border-brand-border shadow-editorial flex flex-col">
            <div className="border-b border-brand-border/40 pb-4 mb-6">
              <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                Taxonomia Literária
              </div>
              <h3 className="text-2xl font-normal text-brand-dark font-serif italic">
                Cadastrar Novo Gênero
              </h3>
            </div>

            <form onSubmit={handleAddSubmit} className="space-y-5" id="form-cadastra-categoria">
              <div className="space-y-2">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  Nome do Gênero / Categoria <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={newCat}
                  onChange={(e) => {
                    setNewCat(e.target.value);
                    if (errorCode) setErrorCode(null);
                  }}
                  placeholder="Ex: Suspense, Ficção Científica, Poesia..."
                  className="w-full border-b-2 border-brand-border py-2 focus:border-brand-primary outline-none text-sm transition-colors bg-transparent font-medium text-brand-dark"
                  autoFocus
                />
                <p className="text-[10px] text-slate-400 italic">
                  Utilize nomes claros e curtos para manter a harmonia dos filtros do acervo.
                </p>
              </div>

              {errorCode && (
                <div className="p-3.5 bg-red-50 border border-red-100 rounded-xl text-[11px] text-red-700 font-medium" id="error-category-alert">
                  ⚠️ {errorCode}
                </div>
              )}

              <button
                type="submit"
                id="btn-confirm-category-add"
                className="w-full py-3 text-xs font-bold uppercase tracking-wider text-white bg-brand-primary hover:bg-brand-secondary rounded-full transition cursor-pointer shadow-md shadow-brand-primary/10 flex items-center justify-center gap-2"
              >
                <PlusCircle className="w-4 h-4" /> Adicionar Categoria
              </button>
            </form>
          </div>

          {/* Guidelines Sidebar box */}
          <div className="p-6 bg-[#fdfdfc] border border-brand-border/80 rounded-2xl text-brand-dark text-xs space-y-3 shadow-editorial" id="category-help-box">
            <p className="font-bold flex items-center gap-1.5 text-brand-secondary text-xs uppercase tracking-wider border-b border-brand-border pb-2">
              <HelpCircle className="w-4 h-4 text-brand-secondary" /> Organização de Prateleiras
            </p>
            <p className="leading-relaxed text-slate-600">
              Categorias corretas facilitam a busca rápida de leitores na fila de locação e mantêm o controle de relatórios consistente.
            </p>
            <p className="leading-relaxed text-slate-600 font-semibold italic text-brand-secondary">
              Nota: Categorias que já possuem obras cadastradas não podem ser descadastradas até que esses livros sejam editados ou removidos.
            </p>
          </div>
        </div>

        {/* Right Column: Listing and counters */}
        <div className="lg:col-span-7 bg-[#fdfdfc] rounded-2xl border border-brand-border shadow-editorial overflow-hidden flex flex-col" id="categories-list-right">
          
          <div className="p-6 border-b border-brand-border/40 bg-[#fbfbf9]/60">
            <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
              Filtros Taxonômicos Registrados
            </div>
            <h3 className="text-2xl font-normal text-brand-dark font-serif italic">
              Lista de Categorias Ativas <span className="font-sans font-semibold text-xs tracking-normal text-brand-light not-italic ml-1">({categories.length} gêneros)</span>
            </h3>
          </div>

          <div className="divide-y divide-brand-border/40" id="categories-directory-list">
            {categories.length === 0 ? (
              <div className="p-16 text-center text-slate-400" id="empty-categories">
                <FolderOpen className="w-12 h-12 mx-auto text-slate-300 mb-3" />
                <p className="font-serif italic text-base">Nenhum gênero configurado</p>
              </div>
            ) : (
              categories.map((cat) => {
                const count = getBookCount(cat);
                return (
                  <div
                    key={cat}
                    id={`category-item-${cat}`}
                    className="p-5 flex items-center justify-between hover:bg-slate-50/40 transition gap-4"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs">
                        🏷️
                      </div>
                      <div>
                        <div className="font-sans font-bold text-brand-dark text-sm">{cat}</div>
                        <div className="text-[11px] text-slate-500 font-medium">
                          {count === 0 ? (
                            <span className="text-slate-400 italic">Sem obras vinculadas</span>
                          ) : (
                            <span className="text-emerald-700 font-semibold">{count} livro(s) no acervo</span>
                          )}
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      {count === 0 ? (
                        <button
                          onClick={() => handleDeleteCategory(cat)}
                          className="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-full transition cursor-pointer"
                          title={`Excluir categoria ${cat}`}
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      ) : (
                        <span className="text-[10.5px] italic text-slate-400 font-medium bg-slate-100 px-2.5 py-1 rounded">
                          Bloqueado (Em uso)
                        </span>
                      )}
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </div>
      </div>
    </motion.div>
  );
}
