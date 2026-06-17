/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

export interface Book {
  id: string;
  title: string;
  author: string;
  isbn: string;
  publisher: string;
  year: number;
  category: string;
  status: 'disponivel' | 'emprestado' | 'reservado';
  synopsis?: string;
  createdAt: string;
}

export interface Loan {
  id: string;
  bookId: string;
  bookTitle: string;
  readerName: string;
  readerContact: string;
  loanDate: string;
  dueDate: string;
  returnDate?: string;
  status: 'ativo' | 'devolvido' | 'atrasado';
}

export interface DashboardStats {
  totalBooks: number;
  availableBooks: number;
  borrowedBooks: number;
  categoriesCount: number;
}

export interface AdminAccount {
  name: string;
  email: string;
  password?: string; // Optional password or plain text for local simulation
}

