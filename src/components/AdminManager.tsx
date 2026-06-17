import React, { useState } from 'react';
import { AdminAccount } from '../types';
import { UserPlus, ShieldAlert, Shield, Mail, Lock, Trash2, ArrowLeft, Users, AlertCircle, CheckCircle } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface AdminManagerProps {
  admins: AdminAccount[];
  onAddAdmin: (newAdmin: AdminAccount) => void;
  onRemoveAdmin: (email: string) => void;
  onClose: () => void;
}

export default function AdminManager({
  admins,
  onAddAdmin,
  onRemoveAdmin,
  onClose
}: AdminManagerProps) {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);
    setSuccessMsg(null);

    const trimmedName = name.trim();
    const trimmedEmail = email.trim().toLowerCase();

    if (!trimmedName) {
      setErrorMsg('O nome completo é obrigatório.');
      return;
    }
    if (!trimmedEmail || !trimmedEmail.includes('@')) {
      setErrorMsg('Por favor, informe um e-mail válido.');
      return;
    }
    if (password.length < 6) {
      setErrorMsg('A senha deve conter pelo menos 6 caracteres.');
      return;
    }

    // Check if duplicate admin
    const exists = admins.some((adm) => adm.email.toLowerCase() === trimmedEmail);
    if (exists) {
      setErrorMsg('Já existe uma conta administrativa cadastrada com este e-mail.');
      return;
    }

    onAddAdmin({
      name: trimmedName,
      email: trimmedEmail,
      password: password
    });

    // Reset Form
    setName('');
    setEmail('');
    setPassword('');
    setSuccessMsg('Novo administrador cadastrado com sucesso!');
    setTimeout(() => setSuccessMsg(null), 3000);
  };

  return (
    <motion.div
      id="admins-manager-view"
      initial={{ opacity: 0, y: 15 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -15 }}
      className="space-y-6"
    >
      {/* Top Back Nav Link */}
      <div className="flex items-center justify-between" id="admins-top-bar">
        <button
          onClick={onClose}
          id="btn-back-to-dashboard"
          className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-secondary hover:text-brand-dark transition cursor-pointer"
        >
          <span>← Voltar ao Acervo Principal</span>
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="admins-split-layout">
        
        {/* Left Column: Form to register new admins */}
        <div className="lg:col-span-5 space-y-6" id="admins-form-left">
          <div className="bg-[#fdfdfc] p-8 rounded-2xl border border-brand-border shadow-editorial flex flex-col">
            <div className="border-b border-brand-border/40 pb-4 mb-6">
              <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
                Controle de Acessos
              </div>
              <h3 className="text-2xl font-normal text-brand-dark font-serif italic">
                Cadastrar Administrador
              </h3>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4" id="form-cadastra-admin">
              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  Nome Completo <span className="text-red-500">*</span>
                </label>
                <div className="relative flex items-center">
                  <Shield className="absolute left-1.5 w-4 h-4 text-brand-light" />
                  <input
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="Nome do novo administrador"
                    className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-brand-primary outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                  />
                </div>
              </div>

              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  E-mail Administrativo <span className="text-red-500">*</span>
                </label>
                <div className="relative flex items-center">
                  <Mail className="absolute left-1.5 w-4 h-4 text-brand-light" />
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="email@adm.com"
                    className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-brand-primary outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                  />
                </div>
              </div>

              <div className="space-y-1">
                <label className="block text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  Senha de Acesso <span className="text-red-500">*</span>
                </label>
                <div className="relative flex items-center">
                  <Lock className="absolute left-1.5 w-4 h-4 text-brand-light" />
                  <input
                    type="password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="Mínimo 6 caracteres"
                    className="w-full pl-7 pr-4 py-1.5 border-b-2 border-brand-border focus:border-brand-primary outline-none text-xs transition-colors bg-transparent font-medium text-brand-dark"
                  />
                </div>
              </div>

              {errorMsg && (
                <div className="p-3 bg-red-50 border border-red-100 text-red-700 text-xs rounded-xl flex items-center gap-2" id="error-admin-alert">
                  <AlertCircle className="w-4 h-4 shrink-0 text-red-600" />
                  <span>{errorMsg}</span>
                </div>
              )}

              {successMsg && (
                <div className="p-3 bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs rounded-xl flex items-center gap-2" id="success-admin-alert">
                  <CheckCircle className="w-4 h-4 shrink-0 text-emerald-600" />
                  <span>{successMsg}</span>
                </div>
              )}

              <button
                type="submit"
                id="btn-confirm-admin-add"
                className="w-full py-3 mt-2 text-xs font-bold uppercase tracking-wider text-white bg-[#1e4ca5] hover:bg-[#102a55] rounded-full transition cursor-pointer shadow-md flex items-center justify-center gap-2 animate-pulse-short"
              >
                <UserPlus className="w-4 h-4" /> Cadastrar Acesso
              </button>
            </form>
          </div>

          <div className="p-6 bg-[#fdfdfc] border border-brand-border/80 rounded-2xl text-brand-dark text-xs space-y-3 shadow-editorial">
            <p className="font-bold flex items-center gap-1.5 text-brand-secondary text-xs uppercase tracking-wider border-b border-brand-border pb-2">
              <ShieldAlert className="w-4 h-4 text-brand-secondary" /> Segurança do Sistema
            </p>
            <p className="leading-relaxed text-slate-600">
              Todas as credenciais de administradores dão acesso total ao diretório de acervo, empréstimos e categorias de livros. Recomenda-se senhas fortes e de uso confidencial.
            </p>
            <p className="leading-relaxed text-slate-600 italic">
              <strong>Atenção:</strong> O administrador mestre padrão <span className="font-semibold text-brand-dark">admin@tecavirtual.com</span> é protegido e não pode ser removido.
            </p>
          </div>
        </div>

        {/* Right Column: Active Admins List */}
        <div className="lg:col-span-7 bg-[#fdfdfc] rounded-2xl border border-brand-border shadow-editorial overflow-hidden flex flex-col" id="admins-list-right">
          <div className="p-6 border-b border-brand-border/40 bg-[#fbfbf9]/60">
            <div className="text-[10px] font-bold text-brand-secondary uppercase tracking-[0.2em] mb-1">
              Controle Geral de Acesso
            </div>
            <h3 className="text-2xl font-normal text-brand-dark font-serif italic flex items-center gap-2">
              Contas Administrativas Ativas <span className="font-sans font-semibold text-xs tracking-normal text-brand-light not-italic ml-1">({admins.length})</span>
            </h3>
          </div>

          <div className="divide-y divide-brand-border/40" id="admins-directory-list">
            {admins.map((adm) => {
              const isMaster = adm.email.toLowerCase() === 'admin@tecavirtual.com';
              return (
                <div
                  key={adm.email}
                  id={`admin-item-${adm.email}`}
                  className="p-5 flex items-center justify-between hover:bg-slate-50/40 transition gap-4"
                >
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-full bg-[#1e4ca5]/10 text-[#1e4ca5] flex items-center justify-center font-bold text-sm">
                      {adm.name.charAt(0).toUpperCase()}
                    </div>
                    <div>
                      <div className="font-sans font-bold text-brand-dark text-sm flex items-center gap-2">
                        <span>{adm.name}</span>
                        {isMaster && (
                          <span className="text-[8px] bg-brand-primary/5 text-brand-primary border border-brand-primary/20 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wider">
                            Master
                          </span>
                        )}
                      </div>
                      <div className="text-[11px] text-slate-500 font-mono mt-0.5">
                        {adm.email}
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    {isMaster ? (
                      <span className="text-[10px] italic text-slate-400 font-bold bg-slate-100 px-2.5 py-1 rounded">
                        Protegido
                      </span>
                    ) : (
                      <button
                        onClick={() => onRemoveAdmin(adm.email)}
                        className="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-full transition cursor-pointer"
                        title={`Remover administrador ${adm.name}`}
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </motion.div>
  );
}
