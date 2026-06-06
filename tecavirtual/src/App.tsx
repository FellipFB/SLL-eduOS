import React, { useState, useEffect } from 'react';
import { Book, Loan } from './types';
import Login from './components/Login';
import Dashboard from './components/Dashboard';
import BookForm from './components/BookForm';
import { motion, AnimatePresence } from 'motion/react';

// Pre-populated administrator database
const DEFAULT_BOOKS: Book[] = [
  {
    id: '1',
    title: 'A Prática da Administração de Empresas',
    author: 'Peter Drucker',
    isbn: '978-8547214692',
    publisher: 'Saraiva Uni',
    year: 1954,
    category: 'Administração',
    status: 'disponivel',
    synopsis: 'Obra clássica que define a administração moderna, analisando o papel do administrador, as exigências do cargo e a importância da inovação e da estratégia organizacional.',
    createdAt: new Date().toISOString()
  },
  {
    id: '2',
    title: 'Empresas Feitas para Vencer',
    author: 'Jim Collins',
    isbn: '978-8525062406',
    publisher: 'Editora Globo',
    year: 2001,
    category: 'Liderança & Gestão',
    status: 'emprestado',
    synopsis: 'Jim Collins e sua equipe de pesquisa identificaram o que faz uma empresa comum se tornar excelente, gerando resultados duradouros que superam os concorrentes por décadas.',
    createdAt: new Date().toISOString()
  },
  {
    id: '3',
    title: 'Comece pelo Porquê',
    author: 'Simon Sinek',
    isbn: '978-8543106311',
    publisher: 'Editora Sextante',
    year: 2009,
    category: 'Liderança & Gestão',
    status: 'disponivel',
    synopsis: 'Explora como líderes influentes pensam, agem e se comunicam a partir de um propósito interno claro (o Círculo Dourado), inspirando equipes e clientes de maneira autêntica.',
    createdAt: new Date().toISOString()
  },
  {
    id: '4',
    title: 'A Startup Enxuta',
    author: 'Eric Ries',
    isbn: '978-8535288599',
    publisher: 'Sextante',
    year: 2011,
    category: 'Empreendedorismo',
    status: 'disponivel',
    synopsis: 'Defende a aplicação de métodos científicos no desenvolvimento de novos produtos e serviços, reduzindo desperdícios e encurtando ciclos focados em aprendizado validado.',
    createdAt: new Date().toISOString()
  },
  {
    id: '5',
    title: 'Marketing 5.0: Tecnologia para a Humanidade',
    author: 'Philip Kotler',
    isbn: '978-6555642056',
    publisher: 'Editora Sextante',
    year: 2021,
    category: 'Marketing',
    status: 'reservado',
    synopsis: 'Mostra como os profissionais de marketing podem conciliar o avanço tecnológico (data-driven marketing, IA, internet das coisas) com a busca contínua pelas conexões humanas e sustentabilidade.',
    createdAt: new Date().toISOString()
  }
];

const DEFAULT_LOANS: Loan[] = [
  {
    id: 'l1',
    bookId: '2',
    bookTitle: 'Empresas Feitas para Vencer',
    readerName: 'Mariana Silva Santos',
    readerContact: 'mariana.santos@email.com',
    loanDate: '2026-05-20',
    dueDate: '2026-06-03',
    status: 'ativo'
  }
];

const DEFAULT_CATEGORIES = [
  'Administração',
  'Marketing',
  'Finanças & Economia',
  'Desenvolvimento Pessoal',
  'Tecnologia & Programação',
  'Liderança & Gestão',
  'Empreendedorismo',
  'Comunicação',
  'Ciência & Inovação',
  'Outros'
];

export default function App() {
  const [isLoggedIn, setIsLoggedIn] = useState<boolean>(() => {
    const stored = localStorage.getItem('tecavirtual_auth');
    return stored === 'true';
  });

  const [categories, setCategories] = useState<string[]>(() => {
    const stored = localStorage.getItem('tecavirtual_categories');
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch (e) {
        console.error('Failed to parse categories', e);
      }
    }
    return DEFAULT_CATEGORIES;
  });

  // Sync categories with LocalStorage when state changes
  useEffect(() => {
    localStorage.setItem('tecavirtual_categories', JSON.stringify(categories));
  }, [categories]);

  const handleAddCategory = (newCat: string) => {
    // Trim and normalize name
    const trimmed = newCat.trim();
    if (trimmed && !categories.some(cat => cat.toLowerCase() === trimmed.toLowerCase())) {
      setCategories((prev) => [...prev, trimmed]);
    }
  };

  const handleRemoveCategory = (catToRemove: string) => {
    setCategories((prev) => prev.filter((cat) => cat !== catToRemove));
  };

  const [books, setBooks] = useState<Book[]>(() => {
    const stored = localStorage.getItem('tecavirtual_books');
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch (e) {
        console.error('Failed to parse books', e);
      }
    }
    return DEFAULT_BOOKS;
  });

  const [loans, setLoans] = useState<Loan[]>(() => {
    const stored = localStorage.getItem('tecavirtual_loans');
    if (stored) {
      try {
        return JSON.parse(stored);
      } catch (e) {
        console.error('Failed to parse loans', e);
      }
    }
    return DEFAULT_LOANS;
  });

  const [activeTab, setActiveTab] = useState<'acervo' | 'locacao' | 'categorias'>('acervo');
  const [editingBook, setEditingBook] = useState<Book | null>(null);

  // Sync books with LocalStorage when state changes
  useEffect(() => {
    localStorage.setItem('tecavirtual_books', JSON.stringify(books));
  }, [books]);

  // Sync loans with LocalStorage when state changes
  useEffect(() => {
    localStorage.setItem('tecavirtual_loans', JSON.stringify(loans));
  }, [loans]);

  // Sync session authentication state with LocalStorage
  const handleLoginSuccess = () => {
    setIsLoggedIn(true);
    localStorage.setItem('tecavirtual_auth', 'true');
  };

  const handleLogout = () => {
    setIsLoggedIn(false);
    localStorage.removeItem('tecavirtual_auth');
    setEditingBook(null);
    setActiveTab('acervo');
  };

  // Add Book Logic
  const handleAddBook = (bookData: Omit<Book, 'id' | 'createdAt'>) => {
    const newBook: Book = {
      ...bookData,
      id: crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).substring(2, 9),
      createdAt: new Date().toISOString()
    };
    setBooks((prev) => [newBook, ...prev]);
  };

  // Edit Book Logic
  const handleEditBook = (updatedData: Omit<Book, 'id' | 'createdAt'>) => {
    if (!editingBook) return;
    setBooks((prev) =>
      prev.map((b) => (b.id === editingBook.id ? { ...b, ...updatedData } : b))
    );
    setEditingBook(null); // Close edit mode after saving
  };

  // Delete Book Logic
  const handleDeleteBook = (id: string) => {
    setBooks((prev) => prev.filter((book) => book.id !== id));
    // Also clear associated loans if they exist or handle appropriately
    if (editingBook?.id === id) {
      setEditingBook(null); // Cancel current edit if book is deleted
    }
  };

  // Create Loan Logic
  const handleCreateLoan = (loanData: Omit<Loan, 'id'>) => {
    const newLoan: Loan = {
      ...loanData,
      id: crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).substring(2, 9)
    };
    
    // Add loan
    setLoans((prev) => [newLoan, ...prev]);

    // Update Book Status to 'emprestado'
    setBooks((prev) =>
      prev.map((b) => (b.id === loanData.bookId ? { ...b, status: 'emprestado' } : b))
    );
  };

  // Return Loan Logic
  const handleReturnBook = (loanId: string, bookId: string) => {
    // Mark loan as returned
    setLoans((prev) =>
      prev.map((l) =>
        l.id === loanId
          ? {
              ...l,
              status: 'devolvido',
              returnDate: new Date().toISOString().split('T')[0]
            }
          : l
      )
    );

    // Update Book Status to 'disponivel'
    setBooks((prev) =>
      prev.map((b) => (b.id === bookId ? { ...b, status: 'disponivel' } : b))
    );
  };

  return (
    <div id="app-root-container" className="min-h-screen bg-transparent py-4 sm:py-8 font-sans">
      <AnimatePresence mode="wait">
        {!isLoggedIn ? (
          <motion.div
            key="login-screen"
            id="login-view-wrap"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.3 }}
          >
            <Login onLogin={handleLoginSuccess} />
          </motion.div>
        ) : (
          <motion.div
            key="dashboard-screen"
            id="dashboard-view-wrap"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.3 }}
          >
            <Dashboard
              books={books}
              loans={loans}
              onAddBook={handleAddBook}
              onEditBook={(id, data) => handleEditBook(data)}
              onDeleteBook={handleDeleteBook}
              onLogout={handleLogout}
              onSelectEdit={setEditingBook}
              currentEditingBook={editingBook}
              activeTab={activeTab}
              setActiveTab={setActiveTab}
              onCreateLoan={handleCreateLoan}
              onReturnBook={handleReturnBook}
              categories={categories}
              onAddCategory={handleAddCategory}
              onRemoveCategory={handleRemoveCategory}
            >
              {/* Inject BookForm inside the Dashboard layout dynamically */}
              <BookForm
                onSubmit={editingBook ? handleEditBook : handleAddBook}
                initialBook={editingBook}
                onCancel={editingBook ? () => setEditingBook(null) : undefined}
                categories={categories}
              />
            </Dashboard>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
