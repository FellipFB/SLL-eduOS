import React, { useState } from 'react';
import { ShieldCheck, Mail, Lock, AlertCircle } from 'lucide-react';
import { motion } from 'motion/react';

interface LoginProps {
  onLogin: () => void;
  onBackToReader?: () => void;
}

export default function Login({ onLogin, onBackToReader }: LoginProps) {
  const [username, setUsername] = useState('admin@tecavirtual.com');
  const [password, setPassword] = useState('admin123');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    setLoading(true);
    setError('');

    // Simulated login check
    setTimeout(() => {
      // Read dynamic list of admins to allow newly created admins to log in
      const storedAdmins = localStorage.getItem('tecavirtual_admins');
      let currentAdmins = [{ name: 'Administrador Geral', email: 'admin@tecavirtual.com', password: 'admin123' }];
      
      if (storedAdmins) {
        try {
          const parsed = JSON.parse(storedAdmins);
          if (Array.isArray(parsed) && parsed.length > 0) {
            currentAdmins = parsed;
          }
        } catch (e) {}
      }

      const emailToCheck = username.trim().toLowerCase();
      const matchedAdmin = currentAdmins.find(adm => adm.email.toLowerCase() === emailToCheck);

      if (matchedAdmin && matchedAdmin.password === password) {
        onLogin();
      } else {
        setError('Credenciais inválidas. Use uma conta administrativa cadastrada no sistema.');
        setLoading(false);
      }
    }, 800);
  };

  return (
    <div id="login-container" className="min-h-[85vh] flex items-center justify-center p-4">
      <motion.div
        id="login-card"
        initial={{ opacity: 0, y: 30 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
        className="bg-[#fdfdfc] p-10 md:p-12 rounded-2xl shadow-editorial w-full max-w-md border border-brand-border flex flex-col"
      >
        {/* Editorial Logo Flag */}
        <div className="flex items-center gap-3 justify-center mb-6" id="login-app-logo">
          <div className="w-10 h-10 bg-brand-accent rounded-lg flex items-center justify-center text-[#102a55] shadow-sm font-extrabold text-xl font-serif">
            T
          </div>
          <h1 className="text-xl font-extrabold tracking-tight text-brand-dark">
            Teca<span className="text-brand-secondary">Virtual</span>
          </h1>
        </div>

        <div id="login-header" className="text-center mb-8 border-b border-brand-border/40 pb-6">
          <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-2 underline underline-offset-4">Acesso Restrito</div>
          <h2 className="text-3xl font-regular text-brand-dark font-serif italic" id="login-title-text">
            Entrar no Painel
          </h2>
          <p className="text-brand-light text-xs mt-1">Autorize sua credencial para gerenciar o acervo</p>
        </div>

        {error && (
          <motion.div
            id="error-banner"
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2.5"
          >
            <AlertCircle className="w-4 h-4 shrink-0 mt-0.5 text-red-600" />
            <span className="font-medium">{error}</span>
          </motion.div>
        )}

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="form-group" id="group-username">
            <label htmlFor="username" className="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">
              Usuário ou E-mail
            </label>
            <div className="relative flex items-center" id="wrapper-username">
              <Mail className="absolute left-4 w-4 h-4 text-brand-light" />
              <input
                type="text"
                id="username"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                placeholder="admin@tecavirtual.com"
                required
                className="w-full pl-11 pr-4 py-2.5 border-b-2 border-brand-border focus:border-brand-primary outline-none text-sm transition-colors text-brand-dark placeholder-brand-light bg-transparent font-medium"
              />
            </div>
          </div>

          <div className="form-group" id="group-password">
            <label htmlFor="password" className="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">
              Senha de Segurança
            </label>
            <div className="relative flex items-center" id="wrapper-password">
              <Lock className="absolute left-4 w-4 h-4 text-brand-light" />
              <input
                type="password"
                id="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                required
                className="w-full pl-11 pr-4 py-2.5 border-b-2 border-brand-border focus:border-brand-primary outline-none text-sm transition-colors text-brand-dark placeholder-brand-light bg-transparent font-medium"
              />
            </div>
          </div>

          <button
            type="submit"
            id="btn-login"
            disabled={loading}
            className="w-full py-3.5 text-xs font-bold uppercase tracking-wider text-white bg-brand-primary hover:bg-brand-secondary rounded-lg transition-all cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed shadow-md hover:shadow-brand-primary/10 active:scale-[0.98] mt-4 flex justify-center items-center gap-2"
          >
            {loading ? (
              <>
                <svg className="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                <span>Autenticando...</span>
              </>
            ) : (
              <span>Entrar no Painel</span>
            )}
          </button>
          
          {onBackToReader && (
            <button
              type="button"
              onClick={onBackToReader}
              id="btn-back-to-store"
              className="w-full py-2.5 mt-2.5 text-xs font-bold uppercase tracking-wider text-[#566e92] hover:text-brand-primary border border-[#d2deed] hover:border-[#102a55] hover:bg-slate-50/50 rounded-lg transition-all cursor-pointer bg-transparent text-center flex justify-center items-center gap-1.5"
            >
              <span>← Voltar à Livraria / Área do Leitor</span>
            </button>
          )}
        </form>
      </motion.div>
    </div>
  );
}
