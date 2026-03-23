// frontend/src/app/account/integrations/page.tsx
'use client';

import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { ComplianceType, IntegrationStatus } from '@prisma/client'; // Импорт энтумов из Prisma
import { 
  ShieldCheck, 
  Settings2, 
  ExternalLink, 
  AlertCircle, 
  Plus, 
  Loader2, 
  CheckCircle2, 
  X,
  Activity,
  ChevronDown
} from 'lucide-react';

/**
 * Главная страница управления Регуляторными Интеграциями (Честный ЗНАК, Меркурий и др.).
 * Стек: Next.js 15 (App Router), Tailwind (Glassmorphism), Lucide Icons.
 */
export default function ComplianceIntegrationsPage() {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [selectedType, setSelectedType] = useState<ComplianceType>(ComplianceType.HONEST_SIGN);
  const [formData, setFormData] = useState({ inn: '', token: '' });
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);

  const queryClient = useQueryClient();

  // Запрос списка уже подключенных интеграций
  const { data: integrations, isLoading } = useQuery({
    queryKey: ['compliance-integrations'],
    queryFn: async () => {
      const { data } = await axios.get('/api/compliance');
      return data;
    },
  });

  // Мутация для теста подключения (Backend Proxy)
  const testMutation = useMutation({
    mutationFn: async (payload: any) => {
      const { data } = await axios.post('/api/compliance/test-connection', payload);
      return data;
    },
    onSuccess: (data) => setTestResult(data),
    onError: (err: any) => setTestResult({ success: false, message: err.response?.data?.message || 'Ошибка сети' }),
  });

  // Мутация для сохранения
  const saveMutation = useMutation({
    mutationFn: async (payload: any) => {
      const { data } = await axios.post('/api/compliance/connect', payload);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['compliance-integrations'] });
      setIsModalOpen(false);
      setFormData({ inn: '', token: '' });
      setTestResult(null);
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    saveMutation.mutate({ type: selectedType, ...formData });
  };

  return (
    <div className="min-h-screen bg-[#050505] text-white p-8 font-sans">
      {/* Header Section */}
      <header className="mb-12">
        <div className="flex items-center gap-3 mb-2">
          <div className="p-2 bg-blue-500/10 rounded-lg border border-blue-500/20">
            <ShieldCheck className="w-6 h-6 text-blue-400" />
          </div>
          <h1 className="text-3xl font-bold bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
            Регуляторные Интеграции
          </h1>
        </div>
        <p className="text-gray-400 max-w-2xl leading-relaxed">
          Управляйте API-ключами государственных систем маркировки и ветеринарного контроля. 
          Эти системы обязательны для легальной продажи определенных групп товаров.
        </p>
      </header>

      {/* Main Grid: Status and FAQ */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {/* Integrations Table (Glassmorphism Effect) */}
        <div className="lg:col-span-2 space-y-6">
          <div className="relative overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl transition-all hover:bg-white/[0.07]">
            <table className="w-full text-left">
              <thead className="bg-white/5 text-gray-400 text-xs uppercase tracking-wider">
                <tr>
                  <th className="py-4 px-6 font-semibold">Система</th>
                  <th className="py-4 px-6 font-semibold">ИНН</th>
                  <th className="py-4 px-6 font-semibold">Статус</th>
                  <th className="py-4 px-6 font-semibold">Обновлено</th>
                  <th className="py-4 px-6 text-right font-semibold"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/10">
                {isLoading ? (
                  <tr>
                    <td colSpan={5} className="py-12 text-center text-gray-500">
                      <Loader2 className="w-6 h-6 animate-spin mx-auto mb-2" />
                      Загрузка данных...
                    </td>
                  </tr>
                ) : integrations?.length > 0 ? (
                  integrations.map((item: any) => (
                    <tr key={item.id} className="group hover:bg-white/[0.02] transition-colors">
                      <td className="py-4 px-6">
                        <div className="flex items-center gap-3">
                          <div className={`p-2 rounded-lg ${
                            item.type === 'HONEST_SIGN' ? 'bg-yellow-500/10' : 'bg-green-500/10'
                          }`}>
                            <Activity className={`w-4 h-4 ${
                              item.type === 'HONEST_SIGN' ? 'text-yellow-400' : 'text-green-400'
                            }`} />
                          </div>
                          <span className="font-medium text-gray-200">
                            {item.type === 'HONEST_SIGN' ? 'Честный ЗНАК' : item.type === 'MERCURY' ? 'Меркурий (ВетИС)' : 'МДЛП (Фарма)'}
                          </span>
                        </div>
                      </td>
                      <td className="py-4 px-6 text-gray-400 font-mono text-sm">{item.inn}</td>
                      <td className="py-4 px-6">
                        <span className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border ${
                          item.status === 'CONNECTED' 
                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                            : 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                        }`}>
                          <div className={`w-1.5 h-1.5 rounded-full ${item.status === 'CONNECTED' ? 'bg-emerald-400' : 'bg-rose-400'}`} />
                          {item.status === 'CONNECTED' ? 'Подключено' : 'Ошибка'}
                        </span>
                      </td>
                      <td className="py-4 px-6 text-gray-500 text-sm">
                        {new Date(item.lastCheckedAt).toLocaleDateString()}
                      </td>
                      <td className="py-4 px-6 text-right">
                        <button className="p-2 text-gray-500 hover:text-white transition-colors">
                          <Settings2 className="w-5 h-5" />
                        </button>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={5} className="py-12 text-center text-gray-500 italic">
                      Нет активных интеграций
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>

          {/* Action Button */}
          <button 
            onClick={() => setIsModalOpen(true)}
            className="w-full py-4 rounded-xl flex items-center justify-center gap-2 border-2 border-dashed border-white/10 text-gray-400 hover:border-blue-500/30 hover:text-blue-400 hover:bg-blue-500/5 transition-all group"
          >
            <Plus className="w-5 h-5 group-hover:scale-110 transition-transform" />
            Добавить новую систему контроля
          </button>
        </div>

        {/* Sidebar Info/FAQ (Right Column) */}
        <aside className="space-y-6">
          <div className="p-6 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl">
            <h3 className="text-lg font-semibold mb-4 flex items-center gap-2 text-blue-400">
              <AlertCircle className="w-5 h-5" />
              Важное примечание
            </h3>
            <div className="space-y-4 text-sm text-gray-400 leading-relaxed">
              <p>
                Ваши API-токены шифруются по стандарту <strong className="text-gray-200">AES-256-GCM</strong> и хранятся на защищенном бэкенд-узле.
              </p>
              <p>
                Браузер никогда не получает доступ к вашим секретным ключам напрямую — все запросы идут через прокси-сервер CatVRF.
              </p>
              <div className="pt-4 flex items-center gap-1 group cursor-pointer text-blue-400/80 hover:text-blue-400 transition-colors">
                <span className="text-xs uppercase font-bold tracking-widest">Безопасность</span>
                <ExternalLink className="w-3 h-3 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" />
              </div>
            </div>
          </div>

          <div className="p-6 rounded-2xl border border-white/10 bg-white/5 backdrop-blur-xl">
            <h3 className="text-lg font-semibold mb-6 flex items-center gap-2">
              Частые вопросы
            </h3>
            <div className="space-y-4">
              {[
                { q: 'Что будет, если не подключить Честный ЗНАК?', a: 'Продукты, требующие маркировки (духи, текстиль и др.), будут заблокированы к продаже на витрине.' },
                { q: 'Где взять токен?', a: 'В личном кабинете системы ГИС МТ (РФ) или в системе Цербер (для Меркурия).' },
              ].map((faq, i) => (
                <div key={i} className="group cursor-help">
                  <div className="flex justify-between items-center text-sm font-medium text-gray-300 mb-1 group-hover:text-blue-400 transition-colors">
                    <span>{faq.q}</span>
                    <ChevronDown className="w-4 h-4 text-gray-600 transition-transform group-hover:rotate-180" />
                  </div>
                  <p className="text-xs text-gray-500 leading-normal hidden group-hover:block transition-all duration-300 animate-in fade-in slide-in-from-top-1">
                    {faq.a}
                  </p>
                </div>
              ))}
            </div>
          </div>
        </aside>
      </div>

      {/* Connection Modal (Glassmorphism Modal) */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/60 backdrop-blur-md" onClick={() => setIsModalOpen(false)} />
          <div className="relative w-full max-w-lg bg-[#0a0a0a] border border-white/10 rounded-3xl p-8 shadow-2xl animate-in zoom-in-95 duration-200">
            <button 
              onClick={() => setIsModalOpen(false)}
              className="absolute top-6 right-6 p-2 text-gray-500 hover:text-white"
            >
              <X className="w-6 h-6" />
            </button>

            <h2 className="text-2xl font-bold mb-2">Настройка API-доступа</h2>
            <p className="text-gray-500 mb-8 text-sm">Введите данные владельца организации для интеграции.</p>

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Type Selection */}
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase text-gray-500 tracking-wider">Тип системы</label>
                <div className="grid grid-cols-3 gap-3">
                  {[
                    { id: ComplianceType.HONEST_SIGN, label: 'ГИС МТ' },
                    { id: ComplianceType.MERCURY, label: 'Меркурий' },
                    { id: ComplianceType.MDLP, label: 'МДЛП' }
                  ].map(type => (
                    <button
                      key={type.id}
                      type="button"
                      onClick={() => setSelectedType(type.id)}
                      className={`py-3 rounded-xl border text-sm font-medium transition-all ${
                        selectedType === type.id 
                          ? 'border-blue-500/50 bg-blue-500/10 text-blue-400' 
                          : 'border-white/5 bg-white/5 text-gray-500 hover:text-gray-300'
                      }`}
                    >
                      {type.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* INN Input */}
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase text-gray-500 tracking-wider">ИНН Организации</label>
                <input 
                  type="text"
                  maxLength={12}
                  className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-4 text-white placeholder:text-gray-700 focus:outline-none focus:border-blue-500/50 transition-colors font-mono"
                  placeholder="123456789012"
                  value={formData.inn}
                  onChange={(e) => setFormData({...formData, inn: e.target.value})}
                  required
                />
              </div>

              {/* API Token Input */}
              <div className="space-y-2">
                <label className="text-xs font-bold uppercase text-gray-500 tracking-wider">Api Token / Auth Key</label>
                <div className="relative">
                  <input 
                    type="password"
                    className="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-4 text-white placeholder:text-gray-700 focus:outline-none focus:border-blue-500/50 transition-colors"
                    placeholder="••••••••••••••••••••••••••••"
                    value={formData.token}
                    onChange={(e) => setFormData({...formData, token: e.target.value})}
                    required
                  />
                </div>
              </div>

              {/* Connection Test Result */}
              {testMutation.isPending && (
                <div className="p-4 bg-blue-500/5 border border-blue-500/10 rounded-xl flex items-center gap-3">
                  <Loader2 className="w-4 h-4 text-blue-400 animate-spin" />
                  <span className="text-sm text-blue-400">Проверка доступа...</span>
                </div>
              )}

              {testResult && (
                <div className={`p-4 rounded-xl flex items-center gap-3 border ${
                  testResult.success 
                    ? 'bg-emerald-500/5 border-emerald-500/20 text-emerald-400' 
                    : 'bg-rose-500/5 border-rose-500/20 text-rose-400'
                }`}>
                  {testResult.success ? <CheckCircle2 className="w-5 h-5" /> : <AlertCircle className="w-5 h-5" />}
                  <span className="text-sm font-medium">{testResult.message}</span>
                </div>
              )}

              {/* Footer Actions */}
              <div className="flex gap-4 pt-4">
                <button
                  type="button"
                  disabled={testMutation.isPending}
                  onClick={() => testMutation.mutate({ type: selectedType, ...formData })}
                  className="flex-1 py-4 border border-white/10 rounded-xl text-gray-400 hover:bg-white/5 hover:text-white transition-all disabled:opacity-50"
                >
                  Тест соединения
                </button>
                <button
                  type="submit"
                  disabled={!testResult?.success || saveMutation.isPending}
                  className="flex-1 py-4 bg-blue-600 hover:bg-blue-500 disabled:bg-gray-800 disabled:text-gray-500 rounded-xl font-bold transition-all shadow-lg shadow-blue-900/20"
                >
                  {saveMutation.isPending ? <Loader2 className="w-5 h-5 animate-spin mx-auto" /> : 'Сохранить'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
