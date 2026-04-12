<template>
<div class="space-y-4">
    <!-- ═══ SUB-TABS ═══ -->
    <div class="flex items-center gap-1 overflow-x-auto pb-1 border-b" style="border-color:var(--t-border)">
        <button v-for="t in crmTabs" :key="t.key"
                class="px-3 py-2 rounded-t-lg text-sm font-medium whitespace-nowrap transition-colors"
                :style="activeCrmTab === t.key
                    ? 'background:var(--t-surface);color:var(--t-primary);border-bottom:2px solid var(--t-primary)'
                    : 'color:var(--t-text-2)'"
                @click="activeCrmTab = t.key">
            {{ t.icon }} {{ t.label }}
            <span v-if="t.badge" class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full"
                  style="background:var(--t-primary-dim);color:var(--t-primary)">{{ t.badge }}</span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 1. DASHBOARD                                                   -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'clients'" class="space-y-5">
        <!-- Quick search -->
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">📊 CRM Dashboard</h2>
            <VInput v-model="quickSearch" placeholder="🔍 Быстрый поиск клиента..." class="w-72"
                    @keyup.enter="goToClientBySearch" />
        </div>

        <!-- Main metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Всего клиентов" :value="String(allClients.length)" icon="👥" />
            <VStatCard title="Новые (30д)" :value="String(newClientsCount)" icon="🆕">
                <template #trend><span class="text-green-400 text-xs">+{{ newClientsCount }}</span></template>
            </VStatCard>
            <VStatCard title="Активные" :value="String(activeClientsCount)" icon="💚" />
            <VStatCard title="Потерянные" :value="String(lostClientsCount)" icon="😔">
                <template #trend><span class="text-red-400 text-xs">{{ lostClientsCount }}</span></template>
            </VStatCard>
        </div>

        <!-- Financial metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Средний LTV" :value="fmtMoney(avgLTV)" icon="💰" />
            <VStatCard title="Средний чек" :value="fmtMoney(avgCheck)" icon="🧾" />
            <VStatCard title="Частота визитов" :value="avgFrequency + ' дн.'" icon="📅" />
            <VStatCard title="Retention Rate" :value="retentionRate + '%'" icon="🔁" />
        </div>

        <!-- Top lists + Attention widget -->
        <div class="grid md:grid-cols-3 gap-4">
            <!-- Top-10 loyal -->
            <VCard title="⭐ Топ-10 по лояльности">
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <div v-for="(c, idx) in topLoyal" :key="c.id"
                         class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:brightness-110 transition"
                         style="background:var(--t-bg)" @click="openProfile(c)">
                        <span class="text-sm font-bold w-5" style="color:var(--t-text-3)">{{ idx + 1 }}</span>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                             style="background:var(--t-primary-dim);color:var(--t-primary)">{{ c.name.charAt(0) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ c.name }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ c.visits }} визитов</div>
                        </div>
                        <VBadge :color="segmentColors[c.segment]" size="sm">{{ c.segment }}</VBadge>
                    </div>
                </div>
            </VCard>

            <!-- Top-10 spenders -->
            <VCard title="💎 Топ-10 по выручке">
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <div v-for="(c, idx) in topSpenders" :key="c.id"
                         class="flex items-center gap-2 p-2 rounded-lg cursor-pointer hover:brightness-110 transition"
                         style="background:var(--t-bg)" @click="openProfile(c)">
                        <span class="text-sm font-bold w-5" style="color:var(--t-text-3)">{{ idx + 1 }}</span>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                             style="background:var(--t-primary-dim);color:var(--t-primary)">{{ c.name.charAt(0) }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ c.name }}</div>
                        </div>
                        <span class="text-sm font-bold" style="color:var(--t-primary)">{{ fmtMoney(c.totalSpent) }}</span>
                    </div>
                </div>
            </VCard>

            <!-- Attention widget -->
            <VCard title="🔔 Требуют внимания">
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <div v-if="birthdaysSoon.length" class="space-y-1">
                        <div class="text-xs font-semibold" style="color:var(--t-text-2)">🎂 Дни рождения (7 дней)</div>
                        <div v-for="c in birthdaysSoon" :key="'bd'+c.id"
                             class="text-sm p-2 rounded-lg cursor-pointer hover:brightness-110"
                             style="background:var(--t-bg);color:var(--t-text)" @click="openProfile(c)">
                            {{ c.name }} — {{ c.birthday }}
                        </div>
                    </div>
                    <div v-if="dormantClients.length" class="space-y-1">
                        <div class="text-xs font-semibold" style="color:var(--t-text-2)">😴 Давно не были (30+ дней)</div>
                        <div v-for="c in dormantClients" :key="'dr'+c.id"
                             class="text-sm p-2 rounded-lg cursor-pointer hover:brightness-110"
                             style="background:var(--t-bg);color:var(--t-text)" @click="openProfile(c)">
                            {{ c.name }} — {{ c.lastVisit }}
                        </div>
                    </div>
                    <div v-if="highLtvAtRisk.length" class="space-y-1">
                        <div class="text-xs font-semibold" style="color:var(--t-text-2)">⚠️ Высокий LTV + риск ухода</div>
                        <div v-for="c in highLtvAtRisk" :key="'risk'+c.id"
                             class="text-sm p-2 rounded-lg cursor-pointer hover:brightness-110"
                             style="background:var(--t-bg);color:var(--t-text)" @click="openProfile(c)">
                            {{ c.name }} — {{ fmtMoney(c.totalSpent) }}
                        </div>
                    </div>
                    <div v-if="!birthdaysSoon.length && !dormantClients.length && !highLtvAtRisk.length"
                         class="text-sm py-4 text-center" style="color:var(--t-text-3)">
                        ✅ Всё под контролем
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Segment distribution mini-chart -->
        <VCard title="📈 Распределение по сегментам">
            <div class="flex items-end gap-2 h-32">
                <div v-for="seg in segmentDistribution" :key="seg.name" class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-xs font-bold" style="color:var(--t-text)">{{ seg.count }}</span>
                    <div class="w-full rounded-t-lg transition-all"
                         :style="`height:${seg.pct}%;background:var(--t-${segmentThemeColors[seg.name] || 'primary'})`"></div>
                    <span class="text-[10px] text-center" style="color:var(--t-text-3)">{{ seg.name }}</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 2. CLIENTS LIST                                                -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'clients'" class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">👥 Все клиенты</h2>
            <div class="flex items-center gap-2">
                <VButton size="sm" @click="showAddClient = true">➕ Добавить</VButton>
                <VButton size="sm" variant="outline" @click="showMassActions = !showMassActions">⚡ Массовые</VButton>
            </div>
        </div>

        <!-- Filters bar -->
        <div class="flex flex-wrap gap-2 items-center">
            <VInput v-model="clientFilter.search" placeholder="🔍 Имя, телефон..." class="w-56" />
            <select v-model="clientFilter.segment" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все сегменты</option>
                <option v-for="s in segmentsList" :key="s" :value="s">{{ s }}</option>
            </select>
            <select v-model="clientFilter.sortBy" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="name">По имени</option>
                <option value="lastVisit">По посл. визиту</option>
                <option value="totalSpent">По сумме</option>
                <option value="visits">По визитам</option>
                <option value="ltv">По LTV</option>
            </select>
            <select v-model="clientFilter.master" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все мастера</option>
                <option v-for="m in masters" :key="m.id" :value="m.name">{{ m.name }}</option>
            </select>
            <VButton size="sm" variant="outline" @click="resetFilters">🔄 Сбросить</VButton>
        </div>

        <!-- Saved views -->
        <div class="flex gap-2 overflow-x-auto pb-1">
            <button v-for="sv in savedViews" :key="sv.key"
                    class="px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap transition-colors border"
                    :style="activeView === sv.key
                        ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)'
                        : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'"
                    @click="applyView(sv)">
                {{ sv.label }}
            </button>
        </div>

        <!-- Mass actions panel -->
        <div v-if="showMassActions && selectedClients.length > 0"
             class="p-3 rounded-xl border flex items-center gap-3 flex-wrap"
             style="background:var(--t-primary-dim);border-color:var(--t-primary)">
            <span class="text-sm font-medium" style="color:var(--t-primary)">
                Выбрано: {{ selectedClients.length }}
            </span>
            <VButton size="sm" @click="massAction('sms')">📱 SMS</VButton>
            <VButton size="sm" @click="massAction('push')">🔔 Push</VButton>
            <VButton size="sm" variant="outline" @click="massAction('tag')">🏷️ Тег</VButton>
            <VButton size="sm" variant="outline" @click="massAction('segment')">📂 Сегмент</VButton>
            <VButton size="sm" variant="outline" @click="massAction('export')">📤 Экспорт</VButton>
        </div>

        <!-- Clients table -->
        <div class="overflow-x-auto rounded-xl border" style="border-color:var(--t-border)">
            <table class="w-full text-sm">
                <thead>
                    <tr style="background:var(--t-surface)">
                        <th v-if="showMassActions" class="p-3 text-left">
                            <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" />
                        </th>
                        <th class="p-3 text-left" style="color:var(--t-text-2)">Клиент</th>
                        <th class="p-3 text-left" style="color:var(--t-text-2)">Телефон</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Визитов</th>
                        <th class="p-3 text-right" style="color:var(--t-text-2)">Потрачено</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Посл. визит</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Сегмент</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Бонусы</th>
                        <th class="p-3 text-center" style="color:var(--t-text-2)">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in paginatedClients" :key="c.id"
                        class="border-t cursor-pointer hover:brightness-105 transition"
                        style="border-color:var(--t-border)"
                        @click="openProfile(c)">
                        <td v-if="showMassActions" class="p-3" @click.stop>
                            <input type="checkbox" :checked="selectedClients.includes(c.id)"
                                   @change="toggleClient(c.id)" />
                        </td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                     style="background:var(--t-primary-dim);color:var(--t-primary)">{{ c.name.charAt(0) }}</div>
                                <div>
                                    <div class="font-medium" style="color:var(--t-text)">{{ c.name }}</div>
                                    <div class="text-[10px]" style="color:var(--t-text-3)">
                                        {{ c.tags?.join(', ') || c.preferences }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3" style="color:var(--t-text)">{{ c.phone }}</td>
                        <td class="p-3 text-center font-bold" style="color:var(--t-primary)">{{ c.visits }}</td>
                        <td class="p-3 text-right font-bold" style="color:var(--t-text)">{{ fmtMoney(c.totalSpent) }}</td>
                        <td class="p-3 text-center text-xs" style="color:var(--t-text-2)">{{ c.lastVisit }}</td>
                        <td class="p-3 text-center"><VBadge :color="segmentColors[c.segment]" size="sm">{{ c.segment }}</VBadge></td>
                        <td class="p-3 text-center text-xs font-bold" style="color:var(--t-accent)">{{ c.bonusBalance || 0 }} ₽</td>
                        <td class="p-3 text-center" @click.stop>
                            <div class="flex justify-center gap-1">
                                <button class="p-1 rounded hover:opacity-80" title="Записать" @click="bookClient(c)">📅</button>
                                <button class="p-1 rounded hover:opacity-80" title="Написать" @click="messageClient(c)">💬</button>
                                <button class="p-1 rounded hover:opacity-80" title="Профиль" @click="openProfile(c)">👤</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between">
            <span class="text-xs" style="color:var(--t-text-3)">
                Показано {{ paginationStart }}–{{ paginationEnd }} из {{ filteredClientsList.length }}
            </span>
            <div class="flex gap-1">
                <VButton size="sm" variant="outline" :disabled="currentPage <= 1" @click="currentPage--">←</VButton>
                <button v-for="p in totalPages" :key="p"
                        class="w-8 h-8 rounded-lg text-xs font-bold transition"
                        :style="p === currentPage
                            ? 'background:var(--t-primary);color:#fff'
                            : 'color:var(--t-text-2)'"
                        @click="currentPage = p">{{ p }}</button>
                <VButton size="sm" variant="outline" :disabled="currentPage >= totalPages" @click="currentPage++">→</VButton>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 3. CLIENT PROFILE                                              -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'profile' && activeClient" class="space-y-4">
        <div class="flex items-center gap-3 mb-2">
            <VButton size="sm" variant="outline" @click="activeCrmTab = 'clients'">← Назад</VButton>
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">
                👤 {{ activeClient.name }}
            </h2>
            <VBadge :color="segmentColors[activeClient.segment]" size="sm">{{ activeClient.segment }}</VBadge>
            <VBadge v-if="activeClient.loyaltyLevel" :color="loyaltyLevelColors[activeClient.loyaltyLevel]" size="sm">
                {{ activeClient.loyaltyLevel }}
            </VBadge>
        </div>

        <!-- Profile header card -->
        <div class="p-4 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
            <div class="flex items-start gap-4 flex-wrap">
                <div class="w-20 h-20 rounded-full flex items-center justify-center text-2xl font-bold"
                     style="background:var(--t-primary-dim);color:var(--t-primary)">
                    {{ activeClient.name.charAt(0) }}
                </div>
                <div class="flex-1 min-w-[200px]">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                        <div>
                            <span class="block text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Телефон</span>
                            <span style="color:var(--t-text)">{{ activeClient.phone }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Email</span>
                            <span style="color:var(--t-text)">{{ activeClient.email || '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">День рождения</span>
                            <span style="color:var(--t-text)">{{ activeClient.birthday }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Источник</span>
                            <span style="color:var(--t-text)">{{ activeClient.source || 'Самостоятельно' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Создан</span>
                            <span style="color:var(--t-text)">{{ activeClient.createdAt || '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Аллергии</span>
                            <span :style="activeClient.allergies !== 'Нет' ? 'color:#ef4444' : 'color:var(--t-text)'">
                                {{ activeClient.allergies }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-1">
                    <VButton size="sm" @click="bookClient(activeClient)">📅 Записать</VButton>
                    <VButton size="sm" variant="outline" @click="messageClient(activeClient)">💬 Написать</VButton>
                    <VButton size="sm" variant="outline" @click="showEditClient = true">✏️ Изменить</VButton>
                </div>
            </div>
        </div>

        <!-- Profile metrics -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <VStatCard title="Визитов" :value="String(activeClient.visits)" icon="📊" />
            <VStatCard title="Потрачено" :value="fmtMoney(activeClient.totalSpent)" icon="💰" />
            <VStatCard title="Средний чек" :value="fmtMoney(activeClient.visits > 0 ? Math.round(activeClient.totalSpent / activeClient.visits) : 0)" icon="🧾" />
            <VStatCard title="Бонусы" :value="(activeClient.bonusBalance || 0) + ' ₽'" icon="🎁" />
            <VStatCard title="LTV (прогноз)" :value="fmtMoney(activeClient.ltvPredicted || activeClient.totalSpent)" icon="📈" />
        </div>

        <!-- Profile tabs -->
        <div class="flex gap-2 overflow-x-auto pb-1">
            <button v-for="pt in profileTabs" :key="pt.key"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors"
                    :style="activeProfileTab === pt.key
                        ? 'background:var(--t-primary);color:#fff'
                        : 'background:var(--t-surface);color:var(--t-text-2)'"
                    @click="activeProfileTab = pt.key">
                {{ pt.icon }} {{ pt.label }}
            </button>
        </div>

        <!-- ═══ VISIT HISTORY — FULL TIMELINE ═══ -->
        <div v-if="activeProfileTab === 'history'" class="space-y-4">

            <!-- ── Stats header ── -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Визитов</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ visitStats.total }}</div>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Общая сумма</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ fmtMoney(visitStats.totalSpent) }}</div>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Средний чек</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ fmtMoney(visitStats.avgCheck) }}</div>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Частый мастер</div>
                    <div class="text-sm font-bold truncate" style="color:var(--t-primary)">{{ visitStats.topMaster }}</div>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background:var(--t-surface);border-color:var(--t-border)">
                    <div class="text-[10px] uppercase tracking-wide" style="color:var(--t-text-3)">Частая услуга</div>
                    <div class="text-sm font-bold truncate" style="color:var(--t-accent)">{{ visitStats.topService }}</div>
                </div>
            </div>

            <!-- ── Heatmap (visit frequency) ── -->
            <div class="p-3 rounded-xl border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold" style="color:var(--t-text-2)">📅 Частота посещений (12 мес.)</span>
                    <span class="text-[10px]" style="color:var(--t-text-3)">Больше = ярче</span>
                </div>
                <div class="flex gap-1 items-end h-10">
                    <div v-for="(m, idx) in visitHeatmap" :key="idx"
                         class="flex-1 rounded-sm transition-all cursor-help"
                         :style="`height:${Math.max(m.pct, 8)}%;background:var(--t-primary);opacity:${0.15 + m.pct * 0.85 / 100}`"
                         :title="m.label + ': ' + m.count + ' визит(ов)'"></div>
                </div>
                <div class="flex gap-1 mt-1">
                    <span v-for="(m, idx) in visitHeatmap" :key="'lbl'+idx"
                          class="flex-1 text-center text-[8px]" style="color:var(--t-text-3)">{{ m.short }}</span>
                </div>
            </div>

            <!-- ── AI Prediction ── -->
            <div v-if="activeClient" class="p-3 rounded-xl border flex items-center gap-3"
                 style="background:linear-gradient(135deg, var(--t-primary-dim), var(--t-surface));border-color:var(--t-primary)">
                <span class="text-2xl">🔮</span>
                <div class="flex-1">
                    <div class="text-sm font-semibold" style="color:var(--t-text)">Прогноз следующего визита</div>
                    <div class="text-xs" style="color:var(--t-text-2)">
                        Клиент обычно приходит раз в <strong style="color:var(--t-primary)">{{ nextVisitPrediction.avgInterval }} дней</strong>.
                        Последний визит — <strong>{{ nextVisitPrediction.lastDaysAgo }} дн. назад</strong>.
                        Ожидаемый визит: <strong style="color:var(--t-primary)">{{ nextVisitPrediction.predictedDate }}</strong>
                        (точность {{ nextVisitPrediction.confidence }}%)
                    </div>
                </div>
                <VButton size="sm" @click="bookClient(activeClient)">📅 Записать</VButton>
            </div>

            <!-- ── Filters bar ── -->
            <div class="p-3 rounded-xl border space-y-2" style="background:var(--t-surface);border-color:var(--t-border)">
                <!-- Period quick buttons -->
                <div class="flex flex-wrap gap-1.5 items-center">
                    <span class="text-xs font-semibold mr-1" style="color:var(--t-text-2)">Период:</span>
                    <button v-for="p in historyPeriods" :key="p.key"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors"
                            :style="historyFilter.period === p.key
                                ? 'background:var(--t-primary);color:#fff'
                                : 'background:var(--t-bg);color:var(--t-text-2)'"
                            @click="historyFilter.period = p.key">{{ p.label }}</button>
                </div>
                <!-- Selects row -->
                <div class="flex flex-wrap gap-2 items-center">
                    <VInput v-model="historyFilter.search" placeholder="🔍 Услуга, комментарий..." class="w-52" />
                    <select v-model="historyFilter.master" class="px-2 py-1.5 rounded-lg text-xs border"
                            style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Все мастера</option>
                        <option v-for="m in visitMastersList" :key="m" :value="m">{{ m }}</option>
                    </select>
                    <select v-model="historyFilter.status" class="px-2 py-1.5 rounded-lg text-xs border"
                            style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Все статусы</option>
                        <option value="completed">✅ Выполнено</option>
                        <option value="confirmed">🔵 Подтверждено</option>
                        <option value="pending">🟡 Ожидает</option>
                        <option value="cancelled">❌ Отменено</option>
                        <option value="no_show">⬜ Неявка</option>
                    </select>
                    <select v-model="historyFilter.salon" class="px-2 py-1.5 rounded-lg text-xs border"
                            style="background:var(--t-bg);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Все филиалы</option>
                        <option v-for="s in props.salons" :key="s.id" :value="s.name">{{ s.name }}</option>
                    </select>
                    <div class="ml-auto flex gap-1">
                        <VButton size="sm" variant="outline" @click="resetHistoryFilters">🔄</VButton>
                        <VButton size="sm" variant="outline" @click="exportHistory">📤 Экспорт</VButton>
                    </div>
                </div>
            </div>

            <!-- ── Result count ── -->
            <div class="flex items-center justify-between">
                <span class="text-xs" style="color:var(--t-text-3)">
                    Найдено: {{ filteredVisitHistory.length }} визитов · {{ fmtMoney(filteredVisitTotalSpent) }}
                </span>
            </div>

            <!-- ── Timeline grouped by month ── -->
            <div class="space-y-5">
                <div v-for="group in groupedVisitsByMonth" :key="group.month">
                    <!-- Month header -->
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-px flex-1" style="background:var(--t-border)"></div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold"
                              style="background:var(--t-surface);color:var(--t-text-2);border:1px solid var(--t-border)">
                            {{ group.month }} · {{ group.visits.length }} визит(ов) · {{ fmtMoney(group.total) }}
                        </span>
                        <div class="h-px flex-1" style="background:var(--t-border)"></div>
                    </div>

                    <!-- Visit cards -->
                    <div class="relative pl-6 md:pl-8 space-y-3">
                        <!-- Timeline line -->
                        <div class="absolute left-2 md:left-3 top-0 bottom-0 w-0.5 rounded-full" style="background:var(--t-border)"></div>

                        <div v-for="v in group.visits" :key="v.id" class="relative">
                            <!-- Timeline dot -->
                            <div class="absolute -left-4 md:-left-5 top-4 w-3 h-3 rounded-full border-2"
                                 :style="`border-color:var(--t-primary);background:${visitStatusDotColors[v.status] || 'var(--t-primary)'}`"></div>

                            <!-- Visit card -->
                            <div class="rounded-xl border overflow-hidden transition-shadow hover:shadow-lg cursor-pointer"
                                 style="background:var(--t-surface);border-color:var(--t-border)"
                                 @click="openVisitDetail(v)">

                                <!-- Card header -->
                                <div class="flex items-center gap-3 p-3 border-b" style="border-color:var(--t-border)">
                                    <!-- Date block -->
                                    <div class="text-center min-w-[52px]">
                                        <div class="text-lg font-bold leading-none" style="color:var(--t-primary)">{{ v.dayNum }}</div>
                                        <div class="text-[10px] uppercase" style="color:var(--t-text-3)">{{ v.dayOfWeek }}</div>
                                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ v.time }}</div>
                                    </div>

                                    <!-- Divider -->
                                    <div class="w-px h-10 rounded" style="background:var(--t-border)"></div>

                                    <!-- Master info -->
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                             :style="`background:${v.masterColor || 'var(--t-primary-dim)'};color:var(--t-primary)`">
                                            {{ v.masterName.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ v.masterName }}</div>
                                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ v.salon }}</div>
                                        </div>
                                    </div>

                                    <!-- Status & total -->
                                    <div class="text-right shrink-0">
                                        <div class="text-sm font-bold" style="color:var(--t-text)">{{ fmtMoney(v.total) }}</div>
                                        <VBadge :color="visitStatusColors[v.status]" size="sm">{{ visitStatusLabels[v.status] }}</VBadge>
                                    </div>
                                </div>

                                <!-- Services list -->
                                <div class="p-3 space-y-1.5">
                                    <div v-for="(svc, si) in v.services" :key="si"
                                         class="flex items-center gap-2 text-sm">
                                        <span class="text-base">{{ svc.icon || '💆' }}</span>
                                        <span class="flex-1 truncate" style="color:var(--t-text)">{{ svc.name }}</span>
                                        <span class="text-[10px] shrink-0" style="color:var(--t-text-3)">{{ svc.duration }}</span>
                                        <span class="font-semibold shrink-0" style="color:var(--t-text)">{{ fmtMoney(svc.price) }}</span>
                                    </div>

                                    <!-- Payment breakdown -->
                                    <div v-if="v.prepaid || v.bonusPaid" class="flex flex-wrap gap-3 mt-2 pt-2 border-t text-[10px]" style="border-color:var(--t-border)">
                                        <span v-if="v.prepaid" style="color:var(--t-text-3)">💳 Предоплата: {{ fmtMoney(v.prepaid) }}</span>
                                        <span v-if="v.bonusPaid" style="color:var(--t-accent)">🎁 Бонусами: {{ fmtMoney(v.bonusPaid) }}</span>
                                        <span v-if="v.cashPaid" style="color:var(--t-text-3)">💵 Доплата: {{ fmtMoney(v.cashPaid) }}</span>
                                    </div>
                                </div>

                                <!-- Review (if exists) -->
                                <div v-if="v.review" class="px-3 pb-2">
                                    <div class="p-2 rounded-lg text-xs" style="background:var(--t-bg)">
                                        <div class="flex items-center gap-1 mb-0.5">
                                            <span v-for="star in 5" :key="star" class="text-sm">
                                                {{ star <= v.review.rating ? '⭐' : '☆' }}
                                            </span>
                                            <span class="ml-1" style="color:var(--t-text-3)">{{ v.review.date }}</span>
                                        </div>
                                        <p style="color:var(--t-text)">{{ v.review.text }}</p>
                                    </div>
                                </div>

                                <!-- Before/After photos -->
                                <div v-if="v.photos?.length" class="px-3 pb-2">
                                    <div class="flex gap-2 overflow-x-auto">
                                        <div v-for="(ph, pi) in v.photos" :key="pi"
                                             class="w-20 h-20 rounded-lg border flex items-center justify-center text-xs shrink-0"
                                             style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text-3)">
                                            📷 {{ ph.label }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Master comment (staff-only) -->
                                <div v-if="v.masterComment" class="px-3 pb-2">
                                    <div class="p-2 rounded-lg text-[11px] italic border-l-2"
                                         style="background:var(--t-bg);color:var(--t-text-2);border-color:var(--t-accent)">
                                        👩‍💼 <strong>{{ v.masterName }}:</strong> {{ v.masterComment }}
                                    </div>
                                </div>

                                <!-- Source + quick actions -->
                                <div class="flex items-center justify-between px-3 pb-3">
                                    <span class="text-[10px]" style="color:var(--t-text-3)">📲 {{ v.source }}</span>
                                    <div class="flex gap-1" @click.stop>
                                        <button class="p-1.5 rounded-lg hover:opacity-80 transition text-xs"
                                                style="background:var(--t-bg);color:var(--t-text-2)" title="Повторить запись"
                                                @click="repeatBooking(v)">🔁</button>
                                        <button class="p-1.5 rounded-lg hover:opacity-80 transition text-xs"
                                                style="background:var(--t-bg);color:var(--t-text-2)" title="Добавить комментарий"
                                                @click="addVisitComment(v)">💬</button>
                                        <button class="p-1.5 rounded-lg hover:opacity-80 transition text-xs"
                                                style="background:var(--t-bg);color:var(--t-text-2)" title="Запросить отзыв"
                                                @click="requestReview(v)">⭐</button>
                                        <button class="p-1.5 rounded-lg hover:opacity-80 transition text-xs"
                                                style="background:var(--t-bg);color:var(--t-text-2)" title="Печать чека"
                                                @click="printReceipt(v)">🖨️</button>
                                        <button class="p-1.5 rounded-lg hover:opacity-80 transition text-xs"
                                                style="background:var(--t-bg);color:var(--t-text-2)" title="Подробнее"
                                                @click="openVisitDetail(v)">📋</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!filteredVisitHistory.length" class="text-center py-12">
                    <div class="text-4xl mb-3">📭</div>
                    <div class="text-sm font-medium" style="color:var(--t-text-2)">Нет визитов по выбранным фильтрам</div>
                    <VButton size="sm" variant="outline" class="mt-3" @click="resetHistoryFilters">Сбросить фильтры</VButton>
                </div>
            </div>
        </div>

        <!-- ═══ Visit Detail Modal ═══ -->
        <VModal :show="showVisitDetail" @close="showVisitDetail = false" :title="'📋 Визит — ' + (activeVisit?.fullDate || '')" size="lg">
            <div v-if="activeVisit" class="space-y-4">
                <!-- Header -->
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold"
                         style="background:var(--t-primary-dim);color:var(--t-primary)">{{ activeVisit.masterName.charAt(0) }}</div>
                    <div>
                        <div class="font-semibold" style="color:var(--t-text)">{{ activeVisit.masterName }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ activeVisit.salon }} · {{ activeVisit.fullDate }} {{ activeVisit.time }}</div>
                    </div>
                    <div class="ml-auto text-right">
                        <div class="text-lg font-bold" style="color:var(--t-primary)">{{ fmtMoney(activeVisit.total) }}</div>
                        <VBadge :color="visitStatusColors[activeVisit.status]" size="sm">{{ visitStatusLabels[activeVisit.status] }}</VBadge>
                    </div>
                </div>

                <!-- Services detail -->
                <div class="rounded-xl border overflow-hidden" style="border-color:var(--t-border)">
                    <div class="p-2 text-xs font-semibold" style="background:var(--t-bg);color:var(--t-text-2)">Услуги</div>
                    <div v-for="(svc, si) in activeVisit.services" :key="si"
                         class="flex items-center gap-3 px-3 py-2 border-t text-sm"
                         style="border-color:var(--t-border)">
                        <span class="text-lg">{{ svc.icon || '💆' }}</span>
                        <div class="flex-1">
                            <div style="color:var(--t-text)">{{ svc.name }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ svc.duration }} · {{ svc.masterName || activeVisit.masterName }}</div>
                        </div>
                        <span class="font-bold" style="color:var(--t-text)">{{ fmtMoney(svc.price) }}</span>
                    </div>
                </div>

                <!-- Payment breakdown -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-center text-xs">
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div style="color:var(--t-text-3)">Итого</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(activeVisit.total) }}</div>
                    </div>
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div style="color:var(--t-text-3)">Предоплата</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(activeVisit.prepaid || 0) }}</div>
                    </div>
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div style="color:var(--t-text-3)">Бонусами</div>
                        <div class="font-bold" style="color:var(--t-accent)">{{ fmtMoney(activeVisit.bonusPaid || 0) }}</div>
                    </div>
                    <div class="p-2 rounded-lg" style="background:var(--t-bg)">
                        <div style="color:var(--t-text-3)">Доплата</div>
                        <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(activeVisit.cashPaid || 0) }}</div>
                    </div>
                </div>

                <!-- Review -->
                <div v-if="activeVisit.review" class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center gap-1 mb-1">
                        <span v-for="star in 5" :key="star" class="text-sm">{{ star <= activeVisit.review.rating ? '⭐' : '☆' }}</span>
                        <span class="ml-2 text-xs" style="color:var(--t-text-3)">{{ activeVisit.review.date }}</span>
                    </div>
                    <p class="text-sm" style="color:var(--t-text)">{{ activeVisit.review.text }}</p>
                </div>

                <!-- Master comment -->
                <div v-if="activeVisit.masterComment" class="p-3 rounded-xl border-l-2" style="background:var(--t-bg);border-color:var(--t-accent)">
                    <div class="text-[10px] uppercase mb-1" style="color:var(--t-text-3)">Комментарий мастера</div>
                    <p class="text-sm italic" style="color:var(--t-text-2)">{{ activeVisit.masterComment }}</p>
                </div>

                <!-- Source -->
                <div class="text-xs" style="color:var(--t-text-3)">📲 Источник записи: {{ activeVisit.source }}</div>
            </div>
            <template #footer>
                <VButton variant="outline" @click="repeatBooking(activeVisit)">🔁 Повторить</VButton>
                <VButton variant="outline" @click="requestReview(activeVisit)">⭐ Запросить отзыв</VButton>
                <VButton variant="outline" @click="printReceipt(activeVisit)">🖨️ Чек</VButton>
                <VButton @click="showVisitDetail = false">Закрыть</VButton>
            </template>
        </VModal>

        <!-- Preferences -->
        <VCard v-if="activeProfileTab === 'preferences'" title="❤️ Предпочтения">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div>
                        <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Любимые услуги</span>
                        <div class="flex flex-wrap gap-1">
                            <VBadge v-for="s in (activeClient.favoriteServices || activeClient.preferences?.split(', ') || [])"
                                    :key="s" color="blue" size="sm">{{ s }}</VBadge>
                        </div>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Любимый мастер</span>
                        <span class="text-sm" style="color:var(--t-text)">{{ activeClient.favoriteMaster || 'Нет предпочтений' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Удобное время</span>
                        <span class="text-sm" style="color:var(--t-text)">{{ activeClient.preferredTime || 'Любое' }}</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Аллергии и противопоказания</span>
                        <span class="text-sm" :style="activeClient.allergies !== 'Нет' ? 'color:#ef4444;font-weight:600' : 'color:var(--t-text)'">
                            {{ activeClient.allergies }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Предпочтения по продуктам</span>
                        <span class="text-sm" style="color:var(--t-text)">{{ activeClient.productPreferences || 'Не указаны' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Комментарий</span>
                        <span class="text-sm" style="color:var(--t-text)">{{ activeClient.notes || '—' }}</span>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Bonuses tab -->
        <VCard v-if="activeProfileTab === 'bonuses'" title="🎁 Бонусный баланс">
            <div class="grid grid-cols-3 gap-3 mb-4">
                <VStatCard title="Текущий баланс" :value="(activeClient.bonusBalance || 0) + ' ₽'" />
                <VStatCard title="Всего начислено" :value="(activeClient.bonusTotal || 0) + ' ₽'" />
                <VStatCard title="Всего списано" :value="(activeClient.bonusSpent || 0) + ' ₽'" />
            </div>
            <div class="flex gap-2 mb-4">
                <VButton size="sm" @click="showAwardBonus = true">➕ Начислить</VButton>
                <VButton size="sm" variant="outline" @click="showDeductBonus = true">➖ Списать</VButton>
            </div>
            <div class="space-y-2 max-h-60 overflow-y-auto">
                <div v-for="tx in clientBonusHistory" :key="tx.id"
                     class="flex items-center gap-3 p-2 rounded-lg border text-sm"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span :class="tx.amount > 0 ? 'text-green-400' : 'text-red-400'" class="font-bold w-20 text-right">
                        {{ tx.amount > 0 ? '+' : '' }}{{ tx.amount }} ₽
                    </span>
                    <span class="flex-1" style="color:var(--t-text)">{{ tx.reason }}</span>
                    <span class="text-xs" style="color:var(--t-text-3)">{{ tx.date }}</span>
                </div>
                <div v-if="!clientBonusHistory.length" class="text-center py-4 text-sm" style="color:var(--t-text-3)">
                    Нет операций с бонусами
                </div>
            </div>
        </VCard>

        <!-- Tags & Segments -->
        <VCard v-if="activeProfileTab === 'tags'" title="🏷️ Теги и сегменты">
            <div class="space-y-4">
                <div>
                    <span class="block text-xs font-semibold mb-2" style="color:var(--t-text-2)">Сегменты</span>
                    <div class="flex flex-wrap gap-2">
                        <VBadge :color="segmentColors[activeClient.segment]">{{ activeClient.segment }}</VBadge>
                    </div>
                </div>
                <div>
                    <span class="block text-xs font-semibold mb-2" style="color:var(--t-text-2)">Теги</span>
                    <div class="flex flex-wrap gap-1">
                        <VBadge v-for="tag in (activeClient.tags || [])" :key="tag" color="gray" size="sm">
                            {{ tag }} <button class="ml-1 text-xs opacity-60 hover:opacity-100" @click.stop="removeTag(tag)">×</button>
                        </VBadge>
                        <button class="px-2 py-0.5 rounded text-xs border transition hover:opacity-80"
                                style="border-color:var(--t-border);color:var(--t-text-2)"
                                @click="showAddTag = true">+ Тег</button>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Notes -->
        <VCard v-if="activeProfileTab === 'notes'" title="📝 Заметки сотрудников">
            <div class="space-y-3 mb-4 max-h-60 overflow-y-auto">
                <div v-for="n in clientNotes" :key="n.id"
                     class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex justify-between mb-1">
                        <span class="text-xs font-semibold" style="color:var(--t-primary)">{{ n.author }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-3)">{{ n.date }}</span>
                    </div>
                    <p class="text-sm" style="color:var(--t-text)">{{ n.text }}</p>
                </div>
                <div v-if="!clientNotes.length" class="text-center py-4 text-sm" style="color:var(--t-text-3)">
                    Заметок нет
                </div>
            </div>
            <div class="flex gap-2">
                <VInput v-model="newNote" placeholder="Добавить заметку..." class="flex-1" />
                <VButton size="sm" @click="addNote">💾</VButton>
            </div>
        </VCard>

        <!-- Medical card -->
        <VCard v-if="activeProfileTab === 'medical'" title="🏥 Медкарта">
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Аллергии</span>
                    <span :style="activeClient.allergies !== 'Нет' ? 'color:#ef4444;font-weight:600' : 'color:var(--t-text)'">
                        {{ activeClient.allergies }}
                    </span>
                </div>
                <div>
                    <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Тип кожи</span>
                    <span style="color:var(--t-text)">{{ activeClient.skinType || 'Не определён' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Тип волос</span>
                    <span style="color:var(--t-text)">{{ activeClient.hairType || 'Не определён' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">Противопоказания</span>
                    <span style="color:var(--t-text)">{{ activeClient.contraindications || 'Нет' }}</span>
                </div>
                <div class="col-span-2">
                    <span class="block text-xs font-semibold mb-1" style="color:var(--t-text-2)">История процедур (косметология)</span>
                    <div v-for="proc in (activeClient.medicalProcedures || [])" :key="proc.date"
                         class="p-2 rounded border mb-1 text-xs" style="border-color:var(--t-border);color:var(--t-text)">
                        {{ proc.date }} — {{ proc.name }} ({{ proc.master }})
                    </div>
                    <span v-if="!activeClient.medicalProcedures?.length" style="color:var(--t-text-3)">Нет данных</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 4. SEGMENTATION & MARKETING                                    -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'segments'" class="space-y-4">
        <BeautySegmentation
            :clients="allClients"
            :masters="props.masters"
            :salons="props.salons"
            :services="props.services"
            @open-client="openProfile"
            @send-message="handleSegmentMessage"
            @award-bonus="handleSegmentBonus"
            @create-promo="handleSegmentPromo"
            @export="handleSegmentExport"
            @add-tag="handleSegmentTag" />

        <!-- Campaigns -->
        <VCard title="📣 Кампании">
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs" style="color:var(--t-text-3)">Активных кампаний: {{ activeCampaigns.length }}</span>
                <VButton size="sm" @click="showCreateCampaign = true">➕ Создать кампанию</VButton>
            </div>
            <div class="space-y-2">
                <div v-for="camp in campaigns" :key="camp.id"
                     class="p-3 rounded-lg border flex items-center gap-3 flex-wrap"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex-1 min-w-[180px]">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ camp.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">
                            {{ camp.channel }} · {{ camp.segment }} · {{ camp.date }}
                        </div>
                    </div>
                    <div class="text-right text-xs">
                        <div style="color:var(--t-text-2)">Отправлено: {{ camp.sent }}</div>
                        <div style="color:var(--t-primary)">Открыто: {{ camp.opened }} ({{ camp.sent > 0 ? Math.round(camp.opened/camp.sent*100) : 0 }}%)</div>
                    </div>
                    <VBadge :color="camp.status === 'active' ? 'green' : camp.status === 'completed' ? 'blue' : 'gray'" size="sm">
                        {{ camp.status === 'active' ? 'Активна' : camp.status === 'completed' ? 'Завершена' : 'Черновик' }}
                    </VBadge>
                </div>
                <div v-if="!campaigns.length" class="text-center py-4 text-sm" style="color:var(--t-text-3)">
                    Нет кампаний
                </div>
            </div>
        </VCard>

        <!-- Auto-triggers -->
        <VCard title="⚡ Авто-триггеры">
            <div class="space-y-2">
                <div v-for="tr in autoTriggers" :key="tr.id"
                     class="p-3 rounded-lg border flex items-center gap-3"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-xl">{{ tr.icon }}</span>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ tr.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ tr.description }}</div>
                    </div>
                    <div class="text-xs text-right" style="color:var(--t-text-2)">Сработал: {{ tr.fires }}×</div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" :checked="tr.enabled" @change="toggleTrigger(tr)" class="sr-only peer" />
                        <div class="w-9 h-5 rounded-full peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:w-4 after:h-4 after:rounded-full after:transition-all"
                             :style="tr.enabled
                                 ? 'background:var(--t-primary);'
                                 : 'background:var(--t-border);'"
                             style="after:background:white"></div>
                    </label>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 4B. MARKETING AUTOMATION                                        -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'automation'" class="space-y-4">
        <BeautyAutomation
            :clients="allClients"
            :masters="props.masters"
            :services="props.services" />
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 5. INTERACTION HISTORY                                         -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'history'" class="space-y-4">
        <BeautyCRMHistory
            :clients="allClients"
            :masters="props.masters"
            @open-client="openProfileById" />
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 6. CLIENT ANALYTICS                                            -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'analytics'" class="space-y-4">
        <BeautyCRMAnalytics
            :clients="allClients"
            @open-client="openProfileById"
            @filter-segment="applySegmentFilter" />
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- 7. CRM SETTINGS                                                -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeCrmTab === 'settings'" class="space-y-4">
        <BeautyCRMSettings
            :clients="allClients"
            :masters="props.masters"
            @save-settings="handleSaveSettings" />
    </div>
    <!-- ══════════════════════════════════════════════════════════════ -->
    <!--                         MODALS                                 -->
    <!-- ══════════════════════════════════════════════════════════════ -->

    <!-- Add Client Modal -->
    <VModal :show="showAddClient" @close="showAddClient = false" title="➕ Новый клиент" size="lg">
        <div class="grid md:grid-cols-2 gap-4">
            <VInput v-model="newClient.name" label="ФИО" placeholder="Имя Фамилия" />
            <VInput v-model="newClient.phone" label="Телефон" placeholder="+7 900 000-00-00" />
            <VInput v-model="newClient.email" label="Email" placeholder="email@example.com" />
            <VInput v-model="newClient.birthday" label="День рождения" placeholder="ДД.ММ" />
            <VInput v-model="newClient.source" label="Источник" placeholder="Instagram, Рекомендация..." />
            <VInput v-model="newClient.allergies" label="Аллергии" placeholder="Нет" />
            <div class="col-span-2">
                <VInput v-model="newClient.notes" label="Комментарий" placeholder="Заметка..." />
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showAddClient = false">Отмена</VButton>
            <VButton @click="createClient">💾 Создать</VButton>
        </template>
    </VModal>

    <!-- Award Bonus Modal -->
    <VModal :show="showAwardBonus" @close="showAwardBonus = false" title="🎁 Начислить бонусы">
        <div class="space-y-3">
            <VInput v-model.number="bonusAmount" label="Сумма" type="number" placeholder="500" />
            <VInput v-model="bonusReason" label="Причина" placeholder="Подарок ко дню рождения" />
        </div>
        <template #footer>
            <VButton variant="outline" @click="showAwardBonus = false">Отмена</VButton>
            <VButton @click="awardBonus">✅ Начислить</VButton>
        </template>
    </VModal>

    <!-- Deduct Bonus Modal -->
    <VModal :show="showDeductBonus" @close="showDeductBonus = false" title="➖ Списать бонусы">
        <div class="space-y-3">
            <VInput v-model.number="deductAmount" label="Сумма" type="number" placeholder="200" />
            <VInput v-model="deductReason" label="Причина" placeholder="Оплата услуги" />
        </div>
        <template #footer>
            <VButton variant="outline" @click="showDeductBonus = false">Отмена</VButton>
            <VButton @click="deductBonus">✅ Списать</VButton>
        </template>
    </VModal>

    <!-- Add Tag Modal -->
    <VModal :show="showAddTag" @close="showAddTag = false" title="🏷️ Добавить тег">
        <VInput v-model="newTag" placeholder="Название тега" />
        <template #footer>
            <VButton variant="outline" @click="showAddTag = false">Отмена</VButton>
            <VButton @click="addTag">✅ Добавить</VButton>
        </template>
    </VModal>

    <!-- Edit Client Modal -->
    <VModal :show="showEditClient" @close="showEditClient = false" title="✏️ Редактировать клиента" size="lg">
        <div v-if="activeClient" class="grid md:grid-cols-2 gap-4">
            <VInput :modelValue="activeClient.name" @update:modelValue="v => activeClient.name = v" label="ФИО" />
            <VInput :modelValue="activeClient.phone" @update:modelValue="v => activeClient.phone = v" label="Телефон" />
            <VInput :modelValue="activeClient.email" @update:modelValue="v => activeClient.email = v" label="Email" />
            <VInput :modelValue="activeClient.birthday" @update:modelValue="v => activeClient.birthday = v" label="День рождения" />
            <VInput :modelValue="activeClient.allergies" @update:modelValue="v => activeClient.allergies = v" label="Аллергии" />
            <VInput :modelValue="activeClient.preferences" @update:modelValue="v => activeClient.preferences = v" label="Предпочтения" />
        </div>
        <template #footer>
            <VButton variant="outline" @click="showEditClient = false">Отмена</VButton>
            <VButton @click="saveClient">💾 Сохранить</VButton>
        </template>
    </VModal>

    <!-- Create Segment Modal -->
    <VModal :show="showCreateSegment" @close="showCreateSegment = false" title="📂 Новый сегмент">
        <div class="space-y-3">
            <VInput v-model="newSegmentName" label="Название" placeholder="Корпоративные клиенты" />
            <VInput v-model="newSegmentRule" label="Условие (описание)" placeholder="Сумма покупок > 100 000 ₽" />
        </div>
        <template #footer>
            <VButton variant="outline" @click="showCreateSegment = false">Отмена</VButton>
            <VButton @click="createSegment">✅ Создать</VButton>
        </template>
    </VModal>

    <!-- Create Campaign Modal -->
    <VModal :show="showCreateCampaign" @close="showCreateCampaign = false" title="📣 Новая кампания" size="lg">
        <div class="grid md:grid-cols-2 gap-4">
            <VInput v-model="newCampaign.name" label="Название" placeholder="Акция ко Дню красоты" />
            <select v-model="newCampaign.channel" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="sms">SMS</option>
                <option value="push">Push</option>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
            </select>
            <select v-model="newCampaign.segment" class="px-3 py-2 rounded-lg text-sm border"
                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                <option value="">Все клиенты</option>
                <option v-for="s in segmentsList" :key="s" :value="s">{{ s }}</option>
            </select>
            <VInput v-model="newCampaign.date" label="Дата отправки" type="date" />
            <div class="col-span-2">
                <label class="block text-xs mb-1" style="color:var(--t-text-2)">Текст</label>
                <textarea v-model="newCampaign.text" rows="3" class="w-full px-3 py-2 rounded-lg text-sm border resize-none"
                          style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"
                          placeholder="Текст рассылки..."></textarea>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showCreateCampaign = false">Отмена</VButton>
            <VButton @click="createCampaign">🚀 Запустить</VButton>
        </template>
    </VModal>

    <!-- Confirm Delete Modal -->
    <VModal :show="confirmDeleteModal" @close="confirmDeleteModal = false" title="⚠️ Подтверждение удаления">
        <div class="text-center py-4">
            <div class="text-4xl mb-3">🗑️</div>
            <div class="text-sm" style="color:var(--t-text)">
                Вы действительно хотите удалить <b>{{ confirmDeleteCount }}</b> клиентов?
            </div>
            <div class="text-xs mt-2" style="color:var(--t-text-3)">Это действие необратимо</div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="confirmDeleteModal = false">Отмена</VButton>
            <VButton style="background:#ef4444" @click="confirmDeleteClients">🗑️ Удалить</VButton>
        </template>
    </VModal>

    <!-- Comment Modal -->
    <VModal :show="commentModal.show" @close="commentModal.show = false" title="📝 Внутренний комментарий">
        <div class="space-y-3">
            <label class="block text-xs mb-1" style="color:var(--t-text-2)">Комментарий к визиту</label>
            <textarea v-model="commentModal.value" rows="4" placeholder="Введите комментарий..."
                      class="w-full px-3 py-2 rounded-lg text-sm border"
                      style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"></textarea>
        </div>
        <template #footer>
            <VButton variant="outline" @click="commentModal.show = false">Отмена</VButton>
            <VButton @click="confirmAddComment">💾 Сохранить</VButton>
        </template>
    </VModal>

    <!-- Toast уведомление -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showToast" class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium"
                 :style="{ background: 'var(--t-primary)', color: '#fff' }">
                {{ toastMessage }}
            </div>
        </Transition>
    </Teleport>

</div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import VButton from '../../UI/VButton.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VCard from '../../UI/VCard.vue';
import VInput from '../../UI/VInput.vue';
import VModal from '../../UI/VModal.vue';
import VBadge from '../../UI/VBadge.vue';
import BeautySegmentation from './BeautySegmentation.vue';
import BeautyAutomation from './BeautyAutomation.vue';
import BeautyCRMHistory from './BeautyCRMHistory.vue';
import BeautyCRMAnalytics from './BeautyCRMAnalytics.vue';
import BeautyCRMSettings from './BeautyCRMSettings.vue';

/* ═══════════════════════════════════════════════════════════════════ */
/*  PROPS                                                              */
/* ═══════════════════════════════════════════════════════════════════ */
const props = defineProps({
    masters:  { type: Array, default: () => [] },
    salons:   { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    bookings: { type: Array, default: () => [] },
});

const emit = defineEmits(['open-client', 'book-master', 'settings-saved']);

/* ═══════════════════════════════════════════════════════════════════ */
/*  CRM TABS                                                           */
/* ═══════════════════════════════════════════════════════════════════ */
const crmTabs = [
    { key: 'clients',    icon: '👥', label: 'Клиенты',       badge: null },
    { key: 'segments',   icon: '📂', label: 'Сегментация',   badge: null },
    { key: 'automation', icon: '🤖', label: 'Автоматизация', badge: null },
    { key: 'history',    icon: '📜', label: 'История',       badge: null },
    { key: 'analytics',  icon: '📊', label: 'Аналитика',     badge: null },
    { key: 'settings',   icon: '⚙️', label: 'Настройки',    badge: null },
];
const activeCrmTab = ref('clients');

/* ═══════════════════════════════════════════════════════════════════ */
/*  ALL CLIENTS DATA                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const allClients = ref([
    { id: 1,  name: 'Мария Королёва',     phone: '+7 900 111-22-33', email: 'maria@mail.ru',  visits: 24, lastVisit: '08.04.2026', totalSpent: 82400, segment: 'VIP',       allergies: 'Нет',                   birthday: '15.06', preferences: 'Окрашивание, стрижка',    tags: ['постоянная', 'окрашивание'], source: 'Рекомендация', createdAt: '12.01.2024', bonusBalance: 4120, bonusTotal: 8240, bonusSpent: 4120, loyaltyLevel: 'Platinum', favoriteMaster: 'Анна Соколова', preferredTime: 'Утро 10:00–12:00', skinType: 'Комбинированная', hairType: 'Тонкие, окрашенные', ltvPredicted: 124000, churnRisk: 5 },
    { id: 2,  name: 'Елена Петрова',      phone: '+7 900 222-33-44', email: 'elena@yandex.ru', visits: 12, lastVisit: '07.04.2026', totalSpent: 41200, segment: 'Лояльная',  allergies: 'Аллергия на латекс',    birthday: '22.09', preferences: 'Маникюр',                tags: ['маникюр'],                   source: 'Instagram',    createdAt: '05.03.2024', bonusBalance: 2060, bonusTotal: 4120, bonusSpent: 2060, loyaltyLevel: 'Gold',     favoriteMaster: 'Ольга Демидова', preferredTime: 'Вечер 17:00–19:00', skinType: 'Сухая',              hairType: 'Нормальные',          ltvPredicted: 68000,  churnRisk: 12 },
    { id: 3,  name: 'Дарья Волкова',      phone: '+7 900 333-44-55', email: '',                visits: 3,  lastVisit: '01.04.2026', totalSpent: 18500, segment: 'Новичок',   allergies: 'Нет',                   birthday: '03.12', preferences: 'Окрашивание',             tags: ['новая'],                     source: 'Яндекс',      createdAt: '20.03.2026', bonusBalance: 925,  bonusTotal: 925,  bonusSpent: 0,    loyaltyLevel: 'Bronze',   favoriteMaster: null,             preferredTime: 'Любое',             skinType: null,                 hairType: 'Густые, тёмные',      ltvPredicted: 42000,  churnRisk: 35 },
    { id: 4,  name: 'Ирина Морозова',     phone: '+7 900 444-55-66', email: 'irina@gmail.com', visits: 8,  lastVisit: '05.04.2026', totalSpent: 28600, segment: 'Лояльная',  allergies: 'Нет',                   birthday: '10.01', preferences: 'Массаж, косметология',   tags: ['spa', 'массаж'],             source: 'Самостоятельно', createdAt: '14.07.2024', bonusBalance: 1430, bonusTotal: 2860, bonusSpent: 1430, loyaltyLevel: 'Silver',   favoriteMaster: 'Светлана Романова', preferredTime: 'День 13:00–15:00', skinType: 'Нормальная',          hairType: null,                  ltvPredicted: 52000,  churnRisk: 18 },
    { id: 5,  name: 'Наталья Белова',     phone: '+7 900 555-66-77', email: '',                visits: 1,  lastVisit: '06.04.2026', totalSpent: 1200,  segment: 'Новичок',   allergies: 'Нет',                   birthday: '28.04', preferences: 'Брови',                   tags: [],                            source: 'Реклама ВК',   createdAt: '06.04.2026', bonusBalance: 60,   bonusTotal: 60,   bonusSpent: 0,    loyaltyLevel: 'Bronze',   favoriteMaster: null,             preferredTime: 'Любое',             skinType: null,                 hairType: null,                  ltvPredicted: 15000,  churnRisk: 55 },
    { id: 6,  name: 'Анастасия Кузнецова',phone: '+7 900 666-77-88', email: 'nastya@mail.ru',  visits: 18, lastVisit: '04.04.2026', totalSpent: 64800, segment: 'VIP',       allergies: 'Нет',                   birthday: '07.08', preferences: 'Стрижка, укладка',       tags: ['постоянная', 'укладка'],     source: 'Рекомендация', createdAt: '02.11.2023', bonusBalance: 3240, bonusTotal: 6480, bonusSpent: 3240, loyaltyLevel: 'Gold',     favoriteMaster: 'Анна Соколова',  preferredTime: 'Утро 09:00–11:00',  skinType: 'Жирная',             hairType: 'Средние, вьющиеся',   ltvPredicted: 98000,  churnRisk: 8 },
    { id: 7,  name: 'Татьяна Новикова',   phone: '+7 900 777-88-99', email: '',                visits: 5,  lastVisit: '25.03.2026', totalSpent: 22000, segment: 'Лояльная',  allergies: 'Чувствительная кожа',   birthday: '18.05', preferences: 'Педикюр, маникюр',       tags: ['маникюр', 'педикюр'],        source: 'Instagram',    createdAt: '09.09.2025', bonusBalance: 1100, bonusTotal: 2200, bonusSpent: 1100, loyaltyLevel: 'Silver',   favoriteMaster: 'Ольга Демидова', preferredTime: 'Вечер 18:00–20:00',  skinType: 'Чувствительная',      hairType: null,                  ltvPredicted: 38000,  churnRisk: 28 },
    { id: 8,  name: 'Оксана Егорова',     phone: '+7 900 888-99-00', email: 'oksana@bk.ru',    visits: 0,  lastVisit: '',           totalSpent: 0,     segment: 'Новичок',   allergies: 'Нет',                   birthday: '11.11', preferences: '',                        tags: ['записана'],                  source: 'Сайт',        createdAt: '09.04.2026', bonusBalance: 0,    bonusTotal: 0,    bonusSpent: 0,    loyaltyLevel: 'Bronze',   favoriteMaster: null,             preferredTime: 'Любое',             skinType: null,                 hairType: null,                  ltvPredicted: 8000,   churnRisk: 70 },
    { id: 9,  name: 'Виктория Соловьёва', phone: '+7 900 999-00-11', email: 'vika@ya.ru',      visits: 31, lastVisit: '09.04.2026', totalSpent: 112000,segment: 'VIP',       allergies: 'Нет',                   birthday: '20.02', preferences: 'Окрашивание, косметология', tags: ['топ-клиент', 'косметология'], source: 'Рекомендация', createdAt: '15.06.2023', bonusBalance: 5600, bonusTotal: 11200, bonusSpent: 5600, loyaltyLevel: 'Platinum', favoriteMaster: 'Светлана Романова', preferredTime: 'День 12:00–14:00', skinType: 'Комбинированная',      hairType: 'Длинные, окрашенные', ltvPredicted: 168000, churnRisk: 3 },
    { id: 10, name: 'Регина Карпова',     phone: '+7 900 100-20-30', email: '',                visits: 2,  lastVisit: '02.03.2026', totalSpent: 8400,  segment: 'Потерянная', allergies: 'Нет',                  birthday: '30.07', preferences: 'Маникюр',                tags: [],                            source: 'Авито',       createdAt: '15.01.2026', bonusBalance: 420,  bonusTotal: 840,  bonusSpent: 420,  loyaltyLevel: 'Bronze',   favoriteMaster: null,             preferredTime: 'Любое',             skinType: null,                 hairType: null,                  ltvPredicted: 12000,  churnRisk: 82 },
    { id: 11, name: 'Алина Фёдорова',     phone: '+7 900 200-30-40', email: 'alina@mail.ru',   visits: 14, lastVisit: '03.04.2026', totalSpent: 48200, segment: 'Лояльная',  allergies: 'Нет',                   birthday: '14.04', preferences: 'Брови, ресницы',         tags: ['ресницы'],                   source: 'Instagram',    createdAt: '01.02.2025', bonusBalance: 2410, bonusTotal: 4820, bonusSpent: 2410, loyaltyLevel: 'Gold',     favoriteMaster: 'Кристина Лебедева', preferredTime: 'Утро 10:00–12:00', skinType: 'Нормальная',          hairType: 'Нормальные',          ltvPredicted: 72000,  churnRisk: 10 },
    { id: 12, name: 'Полина Зайцева',     phone: '+7 900 300-40-50', email: '',                visits: 6,  lastVisit: '01.02.2026', totalSpent: 19800, segment: 'Потерянная', allergies: 'Аллергия на краску',   birthday: '22.06', preferences: 'Стрижка',                tags: ['осторожно-аллергия'],        source: 'Яндекс',      createdAt: '10.08.2025', bonusBalance: 990,  bonusTotal: 1980, bonusSpent: 990,  loyaltyLevel: 'Silver',   favoriteMaster: 'Игорь Волков',   preferredTime: 'День 14:00–16:00',  skinType: 'Чувствительная',      hairType: 'Тонкие, натуральные', ltvPredicted: 28000,  churnRisk: 75 },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  CONSTANTS & HELPERS                                                */
/* ═══════════════════════════════════════════════════════════════════ */
const segmentColors = { 'VIP': 'purple', 'Лояльная': 'green', 'Новичок': 'blue', 'Потерянная': 'red' };
const segmentThemeColors = { 'VIP': 'accent', 'Лояльная': 'primary', 'Новичок': 'primary-dim', 'Потерянная': 'border' };
const loyaltyLevelColors = { 'Bronze': 'gray', 'Silver': 'blue', 'Gold': 'yellow', 'Platinum': 'purple' };
const segmentsList = ['VIP', 'Лояльная', 'Новичок', 'Потерянная'];
const perPage = 8;

function fmtMoney(v) {
    if (v == null) return '0 ₽';
    return Number(v).toLocaleString('ru-RU') + ' ₽';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  DASHBOARD COMPUTEDS                                                */
/* ═══════════════════════════════════════════════════════════════════ */
const newClientsCount = computed(() => allClients.value.filter(c => c.segment === 'Новичок').length);
const activeClientsCount = computed(() => allClients.value.filter(c => c.visits >= 2 && c.segment !== 'Потерянная').length);
const lostClientsCount = computed(() => allClients.value.filter(c => c.segment === 'Потерянная').length);
const avgLTV = computed(() => {
    const total = allClients.value.reduce((s, c) => s + (c.ltvPredicted || c.totalSpent), 0);
    return allClients.value.length > 0 ? Math.round(total / allClients.value.length) : 0;
});
const avgCheck = computed(() => {
    const withVisits = allClients.value.filter(c => c.visits > 0);
    if (!withVisits.length) return 0;
    const total = withVisits.reduce((s, c) => s + Math.round(c.totalSpent / c.visits), 0);
    return Math.round(total / withVisits.length);
});
const avgFrequency = computed(() => {
    const withVisits = allClients.value.filter(c => c.visits > 1);
    if (!withVisits.length) return 0;
    return 21;
});
const retentionRate = computed(() => {
    const returning = allClients.value.filter(c => c.visits >= 2).length;
    return allClients.value.length > 0 ? Math.round(returning / allClients.value.length * 100) : 0;
});

const topLoyal = computed(() => [...allClients.value].sort((a, b) => b.visits - a.visits).slice(0, 10));
const topSpenders = computed(() => [...allClients.value].sort((a, b) => b.totalSpent - a.totalSpent).slice(0, 10));

const birthdaysSoon = computed(() => {
    const now = new Date();
    const dayNow = now.getDate();
    const monthNow = now.getMonth() + 1;
    return allClients.value.filter(c => {
        if (!c.birthday) return false;
        const parts = c.birthday.split('.');
        const d = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        if (m !== monthNow) return false;
        return d >= dayNow && d <= dayNow + 7;
    });
});
const dormantClients = computed(() => allClients.value.filter(c => c.churnRisk > 60));
const highLtvAtRisk = computed(() => allClients.value.filter(c => c.totalSpent > 30000 && c.churnRisk > 30));

const segmentDistribution = computed(() => {
    return segmentsList.map(s => {
        const count = allClients.value.filter(c => c.segment === s).length;
        const pct = allClients.value.length > 0 ? Math.round(count / allClients.value.length * 100) : 0;
        return { name: s, count, pct: Math.max(pct, 5) };
    });
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  CLIENTS LIST                                                       */
/* ═══════════════════════════════════════════════════════════════════ */
const clientFilter = reactive({ search: '', segment: '', sortBy: 'lastVisit', master: '' });
const currentPage = ref(1);
const showMassActions = ref(false);
const selectedClients = ref([]);
const showAddClient = ref(false);
const activeView = ref('all');
const showToast = ref(false);
const toastMessage = ref('');
const confirmDeleteModal = ref(false);
const confirmDeleteCount = ref(0);
const commentModal = reactive({ show: false, value: '', target: null });

const savedViews = [
    { key: 'all',       label: '👥 Все',            filter: {} },
    { key: 'vip',       label: '⭐ VIP',            filter: { segment: 'VIP' } },
    { key: 'loyal',     label: '💚 Лояльные',       filter: { segment: 'Лояльная' } },
    { key: 'new',       label: '🆕 Новички',        filter: { segment: 'Новичок' } },
    { key: 'lost',      label: '😔 Потерянные',     filter: { segment: 'Потерянная' } },
    { key: 'birthdays', label: '🎂 Именинники',     filter: { special: 'birthdays' } },
    { key: 'risk',      label: '⚠️ Риск оттока',    filter: { special: 'risk' } },
];

const filteredClientsList = computed(() => {
    let list = [...allClients.value];
    const q = clientFilter.search.toLowerCase();
    if (q) list = list.filter(c => c.name.toLowerCase().includes(q) || c.phone.includes(q));
    if (clientFilter.segment) list = list.filter(c => c.segment === clientFilter.segment);
    if (clientFilter.master) list = list.filter(c => c.favoriteMaster === clientFilter.master);

    const sortMap = {
        name: (a, b) => a.name.localeCompare(b.name),
        lastVisit: (a, b) => (b.lastVisit || '').localeCompare(a.lastVisit || ''),
        totalSpent: (a, b) => b.totalSpent - a.totalSpent,
        visits: (a, b) => b.visits - a.visits,
        ltv: (a, b) => (b.ltvPredicted || 0) - (a.ltvPredicted || 0),
    };
    list.sort(sortMap[clientFilter.sortBy] || sortMap.lastVisit);
    return list;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredClientsList.value.length / perPage)));
const paginationStart = computed(() => (currentPage.value - 1) * perPage + 1);
const paginationEnd = computed(() => Math.min(currentPage.value * perPage, filteredClientsList.value.length));
const paginatedClients = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredClientsList.value.slice(start, start + perPage);
});
const allSelected = computed(() => paginatedClients.value.length > 0 && paginatedClients.value.every(c => selectedClients.value.includes(c.id)));

watch(() => clientFilter.search + clientFilter.segment + clientFilter.master + clientFilter.sortBy, () => { currentPage.value = 1; });

function resetFilters() {
    Object.assign(clientFilter, { search: '', segment: '', sortBy: 'lastVisit', master: '' });
    activeView.value = 'all';
}
function applyView(sv) {
    activeView.value = sv.key;
    clientFilter.segment = sv.filter.segment || '';
    clientFilter.search = '';
    clientFilter.master = '';
    if (sv.filter.special === 'birthdays') {
        clientFilter.segment = '';
    }
    if (sv.filter.special === 'risk') {
        clientFilter.segment = '';
        clientFilter.sortBy = 'ltv';
    }
}
function toggleSelectAll() {
    if (allSelected.value) {
        selectedClients.value = selectedClients.value.filter(id => !paginatedClients.value.find(c => c.id === id));
    } else {
        const ids = paginatedClients.value.map(c => c.id);
        selectedClients.value = [...new Set([...selectedClients.value, ...ids])];
    }
}
function toggleClient(id) {
    const idx = selectedClients.value.indexOf(id);
    if (idx >= 0) selectedClients.value.splice(idx, 1);
    else selectedClients.value.push(id);
}
function massAction(type) {
    const clients = allClients.value.filter(c => selectedClients.value.includes(c.id));
    if (!clients.length) return;
    if (type === 'sms') {
        handleSegmentMessage({ type: 'sms', clients, segment: 'selected' });
    } else if (type === 'bonus') {
        handleSegmentBonus({ amount: 200, clients, reason: 'Массовое начисление' });
    } else if (type === 'delete') {
        confirmDeleteCount.value = clients.length;
        confirmDeleteModal.value = true;
        return;
    } else if (type === 'tag') {
        handleSegmentTag({ type: 'add', tag: 'Массовая акция', clients });
    } else if (type === 'export') {
        handleSegmentExport({ clients, segment: 'selected' });
    }
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  SEGMENTATION EVENT HANDLERS                                        */
/* ═══════════════════════════════════════════════════════════════════ */
function handleSegmentMessage(payload) {
    const count = payload.clients?.length || 0;
    const channel = payload.type === 'sms' ? 'SMS' : payload.type === 'whatsapp' ? 'WhatsApp' : payload.type === 'push' ? 'Push' : 'Email';
    toastMessage.value = `✅ ${channel}-рассылка: ${count} клиентов в очереди`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function handleSegmentBonus(payload) {
    const amount = payload.amount || 0;
    (payload.clients || []).forEach(c => {
        const client = allClients.value.find(cl => cl.id === c.id);
        if (client) {
            client.bonusBalance = (client.bonusBalance || 0) + amount;
            client.bonusTotal = (client.bonusTotal || 0) + amount;
        }
    });
    toastMessage.value = `🎁 Начислено ${amount} ₽ бонусов ${payload.clients?.length || 0} клиентам`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function handleSegmentPromo(payload) {
    const count = payload.clients?.length || 0;
    toastMessage.value = `🎟️ Промокод «${payload.code || 'PROMO'}» отправлен ${count} клиентам (скидка ${payload.value || 0}%)`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function handleSegmentExport(payload) {
    const clients = payload.clients || [];
    const header = 'Имя;Телефон;Email;Сегмент;Визитов;Потрачено;Последний визит\n';
    const rows = clients.map(c => `${c.name};${c.phone};${c.email || ''};${c.segment};${c.visits};${c.totalSpent};${c.lastVisit}`).join('\n');
    const bom = '\uFEFF';
    const blob = new Blob([bom + header + rows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `clients_segment_${payload.segment || 'all'}_${Date.now()}.csv`;
    a.click(); URL.revokeObjectURL(url);
    toastMessage.value = `📥 Экспортировано ${clients.length} клиентов`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function handleSegmentTag(payload) {
    const tag = payload.tag || 'Без тега';
    (payload.clients || []).forEach(c => {
        const client = allClients.value.find(cl => cl.id === c.id);
        if (client) {
            if (payload.type === 'add' && !client.tags?.includes(tag)) {
                client.tags = [...(client.tags || []), tag];
            } else if (payload.type === 'remove') {
                client.tags = (client.tags || []).filter(t => t !== tag);
            }
        }
    });
    toastMessage.value = `🏷️ Тег «${tag}» ${payload.type === 'add' ? 'добавлен' : 'удалён'} у ${payload.clients?.length || 0} клиентов`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  CLIENT PROFILE                                                     */
/* ═══════════════════════════════════════════════════════════════════ */
const activeClient = ref(null);
const activeProfileTab = ref('history');
const showEditClient = ref(false);
const showAwardBonus = ref(false);
const showDeductBonus = ref(false);
const showAddTag = ref(false);
const newTag = ref('');
const newNote = ref('');
const bonusAmount = ref(0);
const bonusReason = ref('');
const deductAmount = ref(0);
const deductReason = ref('');

const profileTabs = [
    { key: 'history',     icon: '📋', label: 'История' },
    { key: 'preferences', icon: '❤️', label: 'Предпочтения' },
    { key: 'bonuses',     icon: '🎁', label: 'Бонусы' },
    { key: 'tags',        icon: '🏷️', label: 'Теги' },
    { key: 'notes',       icon: '📝', label: 'Заметки' },
    { key: 'medical',     icon: '🏥', label: 'Медкарта' },
];

function openProfile(client) {
    activeClient.value = client;
    activeProfileTab.value = 'history';
    activeCrmTab.value = 'profile';
}

/* ─── Visit history — enriched data ─── */
const showVisitDetail = ref(false);
const activeVisit = ref(null);
const historyFilter = reactive({ period: 'all', search: '', master: '', status: '', salon: '' });
const historyPeriods = [
    { key: '7d',   label: '7 дней' },
    { key: '30d',  label: '30 дней' },
    { key: '90d',  label: '90 дней' },
    { key: '6m',   label: '6 мес.' },
    { key: '1y',   label: '1 год' },
    { key: 'all',  label: 'Всё время' },
];

const visitStatusColors = { completed: 'green', confirmed: 'blue', pending: 'yellow', cancelled: 'red', no_show: 'gray', in_progress: 'purple' };
const visitStatusLabels = { completed: 'Выполнено', confirmed: 'Подтверждено', pending: 'Ожидает', cancelled: 'Отменено', no_show: 'Неявка', in_progress: 'В процессе' };
const visitStatusDotColors = { completed: '#22c55e', confirmed: '#3b82f6', pending: '#f59e0b', cancelled: '#ef4444', no_show: '#6b7280', in_progress: '#a855f7' };

const allVisitHistory = computed(() => {
    if (!activeClient.value) return [];
    return [
        {
            id: 1, date: '2026-04-08', time: '11:00', dayNum: '08', dayOfWeek: 'Ср', fullDate: '08.04.2026',
            masterName: 'Анна Соколова', masterColor: 'var(--t-primary-dim)', salon: 'BeautyLab Центр',
            services: [
                { name: 'Окрашивание балаяж', icon: '🎨', duration: '2 ч 30 мин', price: 4500 },
                { name: 'Стрижка женская',    icon: '✂️', duration: '45 мин',     price: 2000 },
            ],
            total: 6500, prepaid: 2000, bonusPaid: 500, cashPaid: 4000,
            status: 'completed', source: 'Онлайн-запись',
            review: { rating: 5, text: 'Идеальный цвет! Анна — мастер от бога.', date: '09.04.2026' },
            photos: [{ label: 'До' }, { label: 'После' }],
            masterComment: 'Использовали краситель Wella 7/0 + 9/1. Клиент хочет попробовать балаяж в следующий раз.',
        },
        {
            id: 2, date: '2026-03-25', time: '10:00', dayNum: '25', dayOfWeek: 'Ср', fullDate: '25.03.2026',
            masterName: 'Анна Соколова', masterColor: 'var(--t-primary-dim)', salon: 'BeautyLab Центр',
            services: [
                { name: 'Укладка праздничная', icon: '💇', duration: '1 ч', price: 2000 },
            ],
            total: 2000, prepaid: 0, bonusPaid: 0, cashPaid: 2000,
            status: 'completed', source: 'Администратор',
            review: null, photos: [], masterComment: null,
        },
        {
            id: 3, date: '2026-03-15', time: '15:00', dayNum: '15', dayOfWeek: 'Сб', fullDate: '15.03.2026',
            masterName: 'Ольга Демидова', masterColor: '#dbeafe', salon: 'BeautyLab Центр',
            services: [
                { name: 'Маникюр гель-лак', icon: '💅', duration: '1 ч 15 мин', price: 2800 },
                { name: 'Педикюр классический', icon: '🦶', duration: '1 ч',       price: 2800 },
            ],
            total: 5600, prepaid: 0, bonusPaid: 560, cashPaid: 5040,
            status: 'completed', source: 'Онлайн-запись',
            review: { rating: 4, text: 'Очень аккуратно! Немного пришлось подождать.', date: '16.03.2026' },
            photos: [], masterComment: 'Аллергия на латекс — только нитриловые перчатки!',
        },
        {
            id: 4, date: '2026-03-01', time: '12:00', dayNum: '01', dayOfWeek: 'Сб', fullDate: '01.03.2026',
            masterName: 'Анна Соколова', masterColor: 'var(--t-primary-dim)', salon: 'BeautyLab Центр',
            services: [
                { name: 'Окрашивание корней', icon: '🎨', duration: '1 ч 30 мин', price: 3500 },
                { name: 'Тонирование',        icon: '✨', duration: '30 мин',     price: 1000 },
            ],
            total: 4500, prepaid: 0, bonusPaid: 0, cashPaid: 4500,
            status: 'completed', source: 'Онлайн-запись',
            review: null, photos: [{ label: 'До' }, { label: 'После' }],
            masterComment: 'Следующий визит планировать через 4–5 недель.',
        },
        {
            id: 5, date: '2026-02-14', time: '13:00', dayNum: '14', dayOfWeek: 'Пт', fullDate: '14.02.2026',
            masterName: 'Светлана Романова', masterColor: '#fef3c7', salon: 'BeautyLab SPA',
            services: [
                { name: 'Косметология — чистка лица', icon: '🧖', duration: '1 ч 15 мин', price: 3800 },
            ],
            total: 3800, prepaid: 1000, bonusPaid: 0, cashPaid: 2800,
            status: 'completed', source: 'Рекомендация',
            review: { rating: 5, text: 'Кожа как у младенца! Спасибо!', date: '15.02.2026' },
            photos: [{ label: 'До' }, { label: 'После' }],
            masterComment: 'Тип кожи: комбинированная. Рекомендован курс из 4-х процедур.',
        },
        {
            id: 6, date: '2026-01-20', time: '14:00', dayNum: '20', dayOfWeek: 'Пн', fullDate: '20.01.2026',
            masterName: 'Анна Соколова', masterColor: 'var(--t-primary-dim)', salon: 'BeautyLab Центр',
            services: [
                { name: 'Окрашивание полное', icon: '🎨', duration: '3 ч', price: 5500 },
                { name: 'Уход Olaplex',       icon: '💎', duration: '30 мин', price: 1500 },
            ],
            total: 7000, prepaid: 3000, bonusPaid: 0, cashPaid: 4000,
            status: 'completed', source: 'Онлайн-запись',
            review: null, photos: [{ label: 'После' }],
            masterComment: null,
        },
        {
            id: 7, date: '2025-12-22', time: '11:00', dayNum: '22', dayOfWeek: 'Пн', fullDate: '22.12.2025',
            masterName: 'Кристина Лебедева', masterColor: '#fce7f3', salon: 'BeautyLab Центр',
            services: [
                { name: 'Ламинирование бровей', icon: '✨', duration: '40 мин', price: 2200 },
                { name: 'Окрашивание бровей',   icon: '✏️', duration: '20 мин', price: 800 },
            ],
            total: 3000, prepaid: 0, bonusPaid: 300, cashPaid: 2700,
            status: 'completed', source: 'Instagram',
            review: { rating: 5, text: 'Брови мечты! Кристина лучшая!', date: '23.12.2025' },
            photos: [{ label: 'До' }, { label: 'После' }],
            masterComment: null,
        },
        {
            id: 8, date: '2025-11-15', time: '16:00', dayNum: '15', dayOfWeek: 'Сб', fullDate: '15.11.2025',
            masterName: 'Игорь Волков', masterColor: '#e0e7ff', salon: 'BeautyLab Центр',
            services: [
                { name: 'Мужская стрижка',   icon: '💈', duration: '40 мин', price: 1800 },
                { name: 'Уход за бородой',   icon: '🧔', duration: '20 мин', price: 1200 },
            ],
            total: 3000, prepaid: 0, bonusPaid: 0, cashPaid: 3000,
            status: 'cancelled', source: 'Онлайн-запись',
            review: null, photos: [],
            masterComment: 'Клиент отменил за 3 часа — без штрафа.',
        },
        {
            id: 9, date: '2025-10-05', time: '10:00', dayNum: '05', dayOfWeek: 'Вс', fullDate: '05.10.2025',
            masterName: 'Анна Соколова', masterColor: 'var(--t-primary-dim)', salon: 'BeautyLab Центр',
            services: [
                { name: 'Стрижка + укладка', icon: '✂️', duration: '1 ч 15 мин', price: 3200 },
            ],
            total: 3200, prepaid: 0, bonusPaid: 0, cashPaid: 3200,
            status: 'completed', source: 'Рекомендация',
            review: null, photos: [], masterComment: null,
        },
        {
            id: 10, date: '2026-04-12', time: '14:30', dayNum: '12', dayOfWeek: 'Сб', fullDate: '12.04.2026',
            masterName: 'Анна Соколова', masterColor: 'var(--t-primary-dim)', salon: 'BeautyLab Центр',
            services: [
                { name: 'Окрашивание балаяж', icon: '🎨', duration: '2 ч 30 мин', price: 4500 },
                { name: 'Стрижка кончиков',   icon: '✂️', duration: '20 мин',     price: 800 },
                { name: 'Уход Olaplex',       icon: '💎', duration: '30 мин',     price: 1500 },
            ],
            total: 6800, prepaid: 2000, bonusPaid: 0, cashPaid: 4800,
            status: 'confirmed', source: 'Онлайн-запись',
            review: null, photos: [], masterComment: null,
        },
    ];
});

/* ─── Filtered & grouped visit history ─── */
const visitMastersList = computed(() => {
    const masters = new Set(allVisitHistory.value.map(v => v.masterName));
    return [...masters].sort();
});

const filteredVisitHistory = computed(() => {
    let list = [...allVisitHistory.value];

    // Period filter
    if (historyFilter.period !== 'all') {
        const now = new Date();
        const daysMap = { '7d': 7, '30d': 30, '90d': 90, '6m': 180, '1y': 365 };
        const days = daysMap[historyFilter.period] || 9999;
        const cutoff = new Date(now.getTime() - days * 86400000);
        list = list.filter(v => new Date(v.date) >= cutoff);
    }

    // Search
    if (historyFilter.search) {
        const q = historyFilter.search.toLowerCase();
        list = list.filter(v =>
            v.services.some(s => s.name.toLowerCase().includes(q)) ||
            (v.masterComment || '').toLowerCase().includes(q) ||
            (v.review?.text || '').toLowerCase().includes(q)
        );
    }

    // Master
    if (historyFilter.master) list = list.filter(v => v.masterName === historyFilter.master);

    // Status
    if (historyFilter.status) list = list.filter(v => v.status === historyFilter.status);

    // Salon
    if (historyFilter.salon) list = list.filter(v => v.salon === historyFilter.salon);

    // Sort newest first
    list.sort((a, b) => b.date.localeCompare(a.date));
    return list;
});

const filteredVisitTotalSpent = computed(() => filteredVisitHistory.value.reduce((s, v) => s + v.total, 0));

const monthNames = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
const groupedVisitsByMonth = computed(() => {
    const groups = {};
    for (const v of filteredVisitHistory.value) {
        const d = new Date(v.date);
        const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        const label = `${monthNames[d.getMonth()]} ${d.getFullYear()}`;
        if (!groups[key]) groups[key] = { month: label, visits: [], total: 0 };
        groups[key].visits.push(v);
        groups[key].total += v.total;
    }
    return Object.entries(groups)
        .sort((a, b) => b[0].localeCompare(a[0]))
        .map(([, g]) => g);
});

/* ─── Visit stats (for header) ─── */
const visitStats = computed(() => {
    const visits = allVisitHistory.value.filter(v => v.status === 'completed');
    const total = visits.length;
    const totalSpent = visits.reduce((s, v) => s + v.total, 0);
    const avgCheck = total > 0 ? Math.round(totalSpent / total) : 0;

    const masterCounts = {};
    const serviceCounts = {};
    for (const v of visits) {
        masterCounts[v.masterName] = (masterCounts[v.masterName] || 0) + 1;
        for (const s of v.services) {
            serviceCounts[s.name] = (serviceCounts[s.name] || 0) + 1;
        }
    }
    const topMaster = Object.entries(masterCounts).sort((a, b) => b[1] - a[1])[0]?.[0] || '—';
    const topService = Object.entries(serviceCounts).sort((a, b) => b[1] - a[1])[0]?.[0] || '—';

    return { total, totalSpent, avgCheck, topMaster, topService };
});

/* ─── Visit frequency heatmap (last 12 months) ─── */
const visitHeatmap = computed(() => {
    const now = new Date();
    const shortMonths = ['Я','Ф','М','А','М','И','И','А','С','О','Н','Д'];
    const months = [];
    for (let i = 11; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        months.push({ key, label: `${monthNames[d.getMonth()]} ${d.getFullYear()}`, short: shortMonths[d.getMonth()], count: 0, pct: 0 });
    }
    for (const v of allVisitHistory.value) {
        const vKey = v.date.substring(0, 7);
        const m = months.find(x => x.key === vKey);
        if (m) m.count++;
    }
    const maxCount = Math.max(...months.map(m => m.count), 1);
    for (const m of months) m.pct = Math.round(m.count / maxCount * 100);
    return months;
});

/* ─── Next visit prediction ─── */
const nextVisitPrediction = computed(() => {
    const completed = allVisitHistory.value
        .filter(v => v.status === 'completed')
        .sort((a, b) => b.date.localeCompare(a.date));

    if (completed.length < 2) return { avgInterval: 0, lastDaysAgo: 0, predictedDate: '—', confidence: 0 };

    const intervals = [];
    for (let i = 0; i < completed.length - 1 && i < 5; i++) {
        const d1 = new Date(completed[i].date);
        const d2 = new Date(completed[i + 1].date);
        intervals.push(Math.round((d1 - d2) / 86400000));
    }
    const avgInterval = Math.round(intervals.reduce((s, v) => s + v, 0) / intervals.length);

    const lastDate = new Date(completed[0].date);
    const now = new Date();
    const lastDaysAgo = Math.round((now - lastDate) / 86400000);

    const predicted = new Date(lastDate.getTime() + avgInterval * 86400000);
    const predictedDate = predicted.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });

    const confidence = Math.min(95, 60 + Math.round(completed.length * 4));

    return { avgInterval, lastDaysAgo, predictedDate, confidence };
});

function resetHistoryFilters() {
    Object.assign(historyFilter, { period: 'all', search: '', master: '', status: '', salon: '' });
}
function exportHistory() {
    const visits = filteredVisitHistory.value;
    const header = '\uFEFFДата;Мастер;Услуги;Сумма;Статус\n';
    const rows = visits.map(v => `${v.date};${v.masterName};${v.services?.map(s => s.name).join(', ')};${v.total};Выполнен`).join('\n');
    const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `history_${activeClient.value?.name || 'client'}_${Date.now()}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
function openVisitDetail(v) {
    activeVisit.value = v;
    showVisitDetail.value = true;
}
function repeatBooking(v) {
    emit('book-master', {
        masterId: v?.masterId,
        masterName: v?.masterName,
        services: v?.services || [],
        clientId: activeClient.value?.id,
        clientName: activeClient.value?.name,
    });
    toastMessage.value = `📅 Запись к ${v?.masterName || 'мастеру'} создана`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function addVisitComment(v) {
    commentModal.value = '';
    commentModal.target = v;
    commentModal.show = true;
}
function confirmAddComment() {
    const v = commentModal.target;
    if (v && commentModal.value.trim()) {
        v.masterComment = (v.masterComment ? v.masterComment + '\n' : '') + commentModal.value.trim();
    }
    commentModal.show = false;
    commentModal.value = '';
    commentModal.target = null;
}
function requestReview(v) {
    toastMessage.value = `✉️ Запрос отзыва отправлен ${activeClient.value?.name || 'клиенту'} (визит ${v?.date || ''})`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function printReceipt(v) {
    const services = v?.services?.map(s => `<tr><td>${s.name}</td><td>${s.price} ₽</td></tr>`).join('') || '';
    const html = `<html><head><title>Чек #${v?.id || ''}</title><style>body{font-family:sans-serif;padding:20px}table{width:100%;border-collapse:collapse}td{padding:4px 8px;border-bottom:1px solid #eee}h3{margin-bottom:4px}</style></head><body><h3>Чек №${v?.id || ''}</h3><p>Дата: ${v?.date || ''}</p><p>Мастер: ${v?.masterName || ''}</p><table>${services}</table><p><b>Итого: ${v?.total || 0} ₽</b></p></body></html>`;
    const w = window.open('', '_blank', 'width=400,height=500');
    if (w) { w.document.write(html); w.document.close(); w.print(); }
}

const clientBonusHistory = computed(() => {
    if (!activeClient.value) return [];
    return [
        { id: 1, amount: 650,  reason: 'Кэшбэк за визит 08.04',     date: '08.04.2026' },
        { id: 2, amount: -500, reason: 'Оплата бонусами',            date: '25.03.2026' },
        { id: 3, amount: 560,  reason: 'Кэшбэк за визит 15.03',     date: '15.03.2026' },
        { id: 4, amount: 1000, reason: 'Подарок ко дню рождения',    date: '15.06.2025' },
        { id: 5, amount: 450,  reason: 'Кэшбэк за визит 01.03',     date: '01.03.2026' },
    ];
});

const clientNotes = ref([
    { id: 1, author: 'Анна Соколова',  date: '08.04.2026', text: 'Хочет попробовать балаяж в следующий раз. Обсудили варианты.' },
    { id: 2, author: 'Ольга Демидова', date: '15.03.2026', text: 'Аллергия на латекс — использовать только нитриловые перчатки.' },
]);

function addNote() {
    if (!newNote.value.trim()) return;
    clientNotes.value.unshift({
        id: Date.now(), author: 'Администратор', date: new Date().toLocaleDateString('ru-RU'), text: newNote.value,
    });
    newNote.value = '';
}
function addTag() {
    if (!newTag.value.trim() || !activeClient.value) return;
    if (!activeClient.value.tags) activeClient.value.tags = [];
    activeClient.value.tags.push(newTag.value.trim());
    newTag.value = '';
    showAddTag.value = false;
}
function removeTag(tag) {
    if (!activeClient.value?.tags) return;
    activeClient.value.tags = activeClient.value.tags.filter(t => t !== tag);
}
function awardBonus() {
    if (!activeClient.value || bonusAmount.value <= 0) return;
    activeClient.value.bonusBalance = (activeClient.value.bonusBalance || 0) + bonusAmount.value;
    activeClient.value.bonusTotal = (activeClient.value.bonusTotal || 0) + bonusAmount.value;
    bonusAmount.value = 0;
    bonusReason.value = '';
    showAwardBonus.value = false;
}
function deductBonus() {
    if (!activeClient.value || deductAmount.value <= 0) return;
    activeClient.value.bonusBalance = Math.max(0, (activeClient.value.bonusBalance || 0) - deductAmount.value);
    activeClient.value.bonusSpent = (activeClient.value.bonusSpent || 0) + deductAmount.value;
    deductAmount.value = 0;
    deductReason.value = '';
    showDeductBonus.value = false;
}
function saveClient() { showEditClient.value = false; }
function bookClient(c) {
    emit('book-master', { clientId: c.id, clientName: c.name, clientPhone: c.phone });
    toastMessage.value = `📅 Запись для ${c.name} создана`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function messageClient(c) {
    if (c.phone) {
        const waUrl = `https://wa.me/${c.phone.replace(/[^\d+]/g, '')}`;
        window.open(waUrl, '_blank');
    }
    toastMessage.value = `💬 Сообщение для ${c.name} — WhatsApp`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  NEW CLIENT                                                         */
/* ═══════════════════════════════════════════════════════════════════ */
const newClient = reactive({ name: '', phone: '', email: '', birthday: '', source: '', allergies: 'Нет', notes: '' });
function createClient() {
    if (!newClient.name || !newClient.phone) return;
    allClients.value.push({
        id: Date.now(), name: newClient.name, phone: newClient.phone, email: newClient.email,
        visits: 0, lastVisit: '', totalSpent: 0, segment: 'Новичок', allergies: newClient.allergies,
        birthday: newClient.birthday, preferences: '', tags: [], source: newClient.source,
        createdAt: new Date().toLocaleDateString('ru-RU'), bonusBalance: 0, bonusTotal: 0, bonusSpent: 0,
        loyaltyLevel: 'Bronze', favoriteMaster: null, preferredTime: 'Любое', skinType: null, hairType: null,
        ltvPredicted: 0, churnRisk: 50,
    });
    Object.assign(newClient, { name: '', phone: '', email: '', birthday: '', source: '', allergies: 'Нет', notes: '' });
    showAddClient.value = false;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  QUICK SEARCH (dashboard)                                           */
/* ═══════════════════════════════════════════════════════════════════ */
const quickSearch = ref('');
function goToClientBySearch() {
    const q = quickSearch.value.toLowerCase();
    if (!q) return;
    const found = allClients.value.find(c => c.name.toLowerCase().includes(q) || c.phone.includes(q));
    if (found) { openProfile(found); quickSearch.value = ''; }
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  SEGMENTS & MARKETING                                               */
/* ═══════════════════════════════════════════════════════════════════ */
const showCreateSegment = ref(false);
const newSegmentName = ref('');
const newSegmentRule = ref('');
const showCreateCampaign = ref(false);
const newCampaign = reactive({ name: '', channel: 'sms', segment: '', date: '', text: '' });

const autoSegments = computed(() => [
    { name: 'VIP',        icon: '👑', count: allClients.value.filter(c => c.segment === 'VIP').length,        rule: '> 50 000 ₽ и > 10 визитов' },
    { name: 'Лояльная',   icon: '💚', count: allClients.value.filter(c => c.segment === 'Лояльная').length,  rule: '> 3 визитов, активен' },
    { name: 'Новичок',    icon: '🆕', count: allClients.value.filter(c => c.segment === 'Новичок').length,   rule: '≤ 3 визитов' },
    { name: 'Потерянная',  icon: '😴', count: allClients.value.filter(c => c.segment === 'Потерянная').length, rule: 'Нет визитов > 30 дней' },
    { name: 'С аллергией', icon: '⚠️', count: allClients.value.filter(c => c.allergies && c.allergies !== 'Нет').length, rule: 'Есть противопоказания' },
]);

const manualSegments = ref([
    { name: 'Корпоративные', count: 0 },
    { name: 'Подарочные сертификаты', count: 3 },
]);

const campaigns = ref([
    { id: 1, name: 'Весенняя акция -20%',     channel: 'SMS',      segment: 'Лояльная',  date: '01.04.2026', sent: 45, opened: 32, status: 'completed' },
    { id: 2, name: 'Новинки косметики',        channel: 'Push',     segment: 'VIP',       date: '05.04.2026', sent: 12, opened: 10, status: 'completed' },
    { id: 3, name: 'Возвращайтесь!',           channel: 'WhatsApp', segment: 'Потерянная', date: '10.04.2026', sent: 0,  opened: 0,  status: 'draft' },
]);
const activeCampaigns = computed(() => campaigns.value.filter(c => c.status === 'active'));

const autoTriggers = ref([
    { id: 1, icon: '🎂', name: 'День рождения',        description: 'Поздравление за 3 дня + скидка 15%',    fires: 24, enabled: true },
    { id: 2, icon: '🏆', name: '3-й визит',             description: 'Благодарность + 500 бонусов',           fires: 38, enabled: true },
    { id: 3, icon: '😴', name: 'Не были 30 дней',       description: 'Напоминание + промокод -10%',           fires: 12, enabled: true },
    { id: 4, icon: '❌', name: 'Отмена записи',         description: 'Предложение перезаписаться',            fires: 8,  enabled: false },
    { id: 5, icon: '⭐', name: 'Достижение VIP',        description: 'Персональное поздравление',             fires: 5,  enabled: true },
]);

function applySegmentFilter(segment) {
    activeCrmTab.value = 'clients';
    clientFilter.segment = segmentsList.includes(segment) ? segment : '';
}
function toggleTrigger(tr) { tr.enabled = !tr.enabled; }
function createSegment() {
    if (!newSegmentName.value.trim()) return;
    manualSegments.value.push({ name: newSegmentName.value, count: 0 });
    newSegmentName.value = '';
    newSegmentRule.value = '';
    showCreateSegment.value = false;
}
function createCampaign() {
    if (!newCampaign.name) return;
    campaigns.value.push({
        id: Date.now(), name: newCampaign.name, channel: newCampaign.channel,
        segment: newCampaign.segment, date: newCampaign.date, sent: 0, opened: 0, status: 'draft',
    });
    Object.assign(newCampaign, { name: '', channel: 'sms', segment: '', date: '', text: '' });
    showCreateCampaign.value = false;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  LOYALTY PROGRAM                                                    */
/* ═══════════════════════════════════════════════════════════════════ */
const loyaltyLevels = computed(() => [
    { name: 'Bronze',   icon: '🥉', minSpent: 0,      cashback: 3,  gradFrom: '#92400e', gradTo: '#b45309', clientsCount: allClients.value.filter(c => c.loyaltyLevel === 'Bronze').length },
    { name: 'Silver',   icon: '🥈', minSpent: 15000,  cashback: 5,  gradFrom: '#6b7280', gradTo: '#9ca3af', clientsCount: allClients.value.filter(c => c.loyaltyLevel === 'Silver').length },
    { name: 'Gold',     icon: '🥇', minSpent: 40000,  cashback: 7,  gradFrom: '#b45309', gradTo: '#f59e0b', clientsCount: allClients.value.filter(c => c.loyaltyLevel === 'Gold').length },
    { name: 'Platinum', icon: '💎', minSpent: 80000,  cashback: 10, gradFrom: '#7c3aed', gradTo: '#a78bfa', clientsCount: allClients.value.filter(c => c.loyaltyLevel === 'Platinum').length },
]);

const bonusRules = ref([
    { id: 1, icon: '🧾', name: 'Кэшбэк за визит',   description: 'Автоматическое начисление % от суммы', value: '3–10%', active: true },
    { id: 2, icon: '👤', name: 'Реферальная программа', description: 'Бонус за приведённого друга',        value: '500 ₽', active: true },
    { id: 3, icon: '🎂', name: 'День рождения',       description: 'Подарочные бонусы',                   value: '1000 ₽', active: true },
    { id: 4, icon: '📅', name: 'Первый визит',        description: 'Приветственный бонус',                 value: '200 ₽', active: true },
    { id: 5, icon: '💰', name: 'Сумма от 5000 ₽',     description: 'Бонус за крупный чек',                value: '500 ₽', active: false },
]);

const recentBonusOps = computed(() => [
    { id: 1, clientName: 'Мария Королёва',     amount: 650,  reason: 'Кэшбэк 10%',         date: '08.04.2026' },
    { id: 2, clientName: 'Виктория Соловьёва', amount: 1120, reason: 'Кэшбэк 10%',         date: '09.04.2026' },
    { id: 3, clientName: 'Елена Петрова',      amount: -500, reason: 'Оплата бонусами',     date: '07.04.2026' },
    { id: 4, clientName: 'Алина Фёдорова',     amount: 340,  reason: 'Кэшбэк 7%',          date: '03.04.2026' },
    { id: 5, clientName: 'Наталья Белова',     amount: 200,  reason: 'Приветственный бонус', date: '06.04.2026' },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  COMMUNICATIONS                                                     */
/* ═══════════════════════════════════════════════════════════════════ */
const chatSearch = ref('');
const activeChatId = ref(null);
const chatMessage = ref('');
const messageTemplate = ref('');

const chats = ref([
    { clientId: 1,  name: 'Мария Королёва',     lastMessage: 'Спасибо, до встречи!',        time: '08.04', unread: 0 },
    { clientId: 2,  name: 'Елена Петрова',      lastMessage: 'Можно ли перенести на 15:00?', time: '07.04', unread: 1 },
    { clientId: 5,  name: 'Наталья Белова',     lastMessage: 'Здравствуйте, записалась',     time: '06.04', unread: 0 },
    { clientId: 9,  name: 'Виктория Соловьёва', lastMessage: 'Отлично, жду!',               time: '09.04', unread: 2 },
    { clientId: 11, name: 'Алина Фёдорова',     lastMessage: 'Подскажите по уходу',          time: '03.04', unread: 0 },
]);

const chatMessages = ref({
    1: [
        { id: 1, from: 'staff',  text: 'Мария, добрый день! Напоминаем о записи на 11:00 завтра.',          time: '07.04 18:00' },
        { id: 2, from: 'client', text: 'Спасибо, приду!',                                                   time: '07.04 18:15' },
        { id: 3, from: 'staff',  text: 'Спасибо за визит! Начислили 650 бонусов 🎁',                        time: '08.04 13:00' },
        { id: 4, from: 'client', text: 'Спасибо, до встречи!',                                              time: '08.04 13:05' },
    ],
    2: [
        { id: 1, from: 'staff',  text: 'Елена, здравствуйте! Ваша запись на 14:00 подтверждена.',            time: '07.04 10:00' },
        { id: 2, from: 'client', text: 'Можно ли перенести на 15:00?',                                       time: '07.04 10:30' },
    ],
    9: [
        { id: 1, from: 'staff',  text: 'Виктория, ваша запись на косметологию 10.04 в 12:00.',                time: '09.04 09:00' },
        { id: 2, from: 'client', text: 'Отлично, жду!',                                                      time: '09.04 09:15' },
    ],
});

const messageTemplates = ref([
    { key: 'reminder',   label: '⏰ Напоминание',    text: 'Здравствуйте, {name}! Напоминаем о записи {date} в {time}.' },
    { key: 'thanks',     label: '🙏 Благодарность',  text: 'Спасибо за визит, {name}! Начислили {bonus} бонусов.' },
    { key: 'promo',      label: '🎁 Акция',          text: '{name}, только для вас скидка {discount}% на {service}!' },
    { key: 'birthday',   label: '🎂 День рождения',  text: 'С днём рождения, {name}! Дарим {bonus} бонусов в подарок!' },
    { key: 'comeback',   label: '😊 Возвращайтесь',  text: '{name}, мы скучаем! Приходите — дарим скидку {discount}%.' },
]);

const filteredChats = computed(() => {
    const q = chatSearch.value.toLowerCase();
    return q ? chats.value.filter(ch => ch.name.toLowerCase().includes(q)) : chats.value;
});
const activeChatName = computed(() => {
    const chat = chats.value.find(ch => ch.clientId === activeChatId.value);
    return chat?.name || '';
});
const activeMessages = computed(() => chatMessages.value[activeChatId.value] || []);

function openChat(chat) { activeChatId.value = chat.clientId; }
function applyTemplate() {
    if (!messageTemplate.value) return;
    const tpl = messageTemplates.value.find(t => t.key === messageTemplate.value);
    if (tpl) chatMessage.value = tpl.text;
    messageTemplate.value = '';
}
function sendMessage() {
    if (!chatMessage.value.trim() || !activeChatId.value) return;
    if (!chatMessages.value[activeChatId.value]) chatMessages.value[activeChatId.value] = [];
    chatMessages.value[activeChatId.value].push({
        id: Date.now(), from: 'staff', text: chatMessage.value,
        time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }),
    });
    chatMessage.value = '';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  ANALYTICS                                                          */
/* ═══════════════════════════════════════════════════════════════════ */
const churnRate = computed(() => {
    const lost = allClients.value.filter(c => c.churnRisk > 60).length;
    return allClients.value.length > 0 ? Math.round(lost / allClients.value.length * 100) : 0;
});
const npsScore = ref('72');
const avgVisitsPerMonth = ref(3.4);

const churnRiskClients = computed(() => {
    return [...allClients.value]
        .filter(c => c.churnRisk > 25)
        .sort((a, b) => b.churnRisk - a.churnRisk)
        .slice(0, 10);
});

const visitPredictions = computed(() => [
    { clientId: 1,  name: 'Мария Королёва',     avgInterval: 14, predictedDate: '22.04.2026', confidence: 92 },
    { clientId: 6,  name: 'Анастасия Кузнецова',avgInterval: 18, predictedDate: '22.04.2026', confidence: 88 },
    { clientId: 9,  name: 'Виктория Соловьёва', avgInterval: 12, predictedDate: '21.04.2026', confidence: 95 },
    { clientId: 2,  name: 'Елена Петрова',      avgInterval: 21, predictedDate: '28.04.2026', confidence: 85 },
    { clientId: 11, name: 'Алина Фёдорова',     avgInterval: 17, predictedDate: '20.04.2026', confidence: 90 },
    { clientId: 4,  name: 'Ирина Морозова',     avgInterval: 25, predictedDate: '30.04.2026', confidence: 78 },
]);

const segmentDynamics = computed(() => [
    { segment: 'VIP',        current: allClients.value.filter(c => c.segment === 'VIP').length,        change: 1 },
    { segment: 'Лояльная',   current: allClients.value.filter(c => c.segment === 'Лояльная').length,  change: 2 },
    { segment: 'Новичок',    current: allClients.value.filter(c => c.segment === 'Новичок').length,   change: 3 },
    { segment: 'Потерянная', current: allClients.value.filter(c => c.segment === 'Потерянная').length, change: -1 },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  B2B TOOLS                                                          */
/* ═══════════════════════════════════════════════════════════════════ */
const importFileInput = ref(null);
const importResult = ref(null);
const showAddBlacklist = ref(false);
const blacklistEntry = reactive({ name: '', reason: '' });

const duplicates = ref([
    { id: 1, name1: 'Елена Петрова', name2: 'Е.Петрова', matchType: 'Телефон' },
]);

const blacklist = ref([
    { id: 1, name: 'Сергей Иванов', reason: 'Неадекватное поведение' },
]);

const accessRoles = ref([
    { name: 'Администратор', description: 'Полный доступ ко всем данным CRM',                    permissions: ['read', 'write', 'delete', 'export'] },
    { name: 'Мастер',        description: 'Просмотр своих клиентов и истории визитов',             permissions: ['read'] },
    { name: 'Менеджер',      description: 'Управление клиентами, кампаниями, без удаления',        permissions: ['read', 'write', 'export'] },
    { name: 'Маркетолог',    description: 'Кампании, сегменты, аналитика',                        permissions: ['read', 'write'] },
]);

function triggerImport() { importFileInput.value?.click(); }
function handleImport(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    importResult.value = { success: true, message: `Файл «${file.name}» загружен. Импортировано 0 клиентов (демо-режим).` };
}
function scanDuplicates() {
    const found = [];
    const clients = allClients.value;
    for (let i = 0; i < clients.length; i++) {
        for (let j = i + 1; j < clients.length; j++) {
            const a = clients[i], b = clients[j];
            if (a.phone === b.phone || (a.email && a.email === b.email) || a.name.toLowerCase() === b.name.toLowerCase()) {
                found.push({ id: Date.now() + j, name: `${a.name} ↔ ${b.name}`, phone: a.phone, reason: a.phone === b.phone ? 'Телефон' : a.email === b.email ? 'Email' : 'Имя', ids: [a.id, b.id] });
            }
        }
    }
    duplicates.value = found;
    toastMessage.value = found.length ? `🔍 Найдено ${found.length} дублей` : '✅ Дубли не найдены';
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
function mergeDuplicate(dup) { duplicates.value = duplicates.value.filter(d => d.id !== dup.id); }
function ignoreDuplicate(dup) { duplicates.value = duplicates.value.filter(d => d.id !== dup.id); }
function removeFromBlacklist(bl) { blacklist.value = blacklist.value.filter(b => b.id !== bl.id); }
function addToBlacklist() {
    if (!blacklistEntry.name) return;
    blacklist.value.push({ id: Date.now(), name: blacklistEntry.name, reason: blacklistEntry.reason });
    Object.assign(blacklistEntry, { name: '', reason: '' });
    showAddBlacklist.value = false;
}
function exportData(format) {
    const clients = allClients.value;
    let content, mime, ext;
    if (format === 'json') {
        content = JSON.stringify(clients, null, 2);
        mime = 'application/json';
        ext = 'json';
    } else {
        const header = '\uFEFFИмя;Телефон;Email;Сегмент;Визитов;Потрачено;Последний визит\n';
        const rows = clients.map(c => `${c.name};${c.phone};${c.email || ''};${c.segment};${c.visits};${c.totalSpent};${c.lastVisit}`).join('\n');
        content = header + rows;
        mime = 'text/csv;charset=utf-8;';
        ext = 'csv';
    }
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `crm_clients_${Date.now()}.${ext}`;
    a.click();
    URL.revokeObjectURL(url);
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  COMPONENT INTEGRATION HELPERS                                      */
/* ═══════════════════════════════════════════════════════════════════ */
function openProfileById(clientId) {
    const client = allClients.value.find(c => c.id === clientId);
    if (client) openProfile(client);
}
function handleSaveSettings(payload) {
    if (payload) {
        Object.keys(payload).forEach(key => {
            if (typeof payload[key] !== 'undefined') {
                // Apply settings from CRM Settings component
            }
        });
    }
    toastMessage.value = '✅ Настройки CRM сохранены';
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
    emit('settings-saved', payload);
}
function confirmDeleteClients() {
    allClients.value = allClients.value.filter(c => !selectedClients.value.includes(c.id));
    selectedClients.value = [];
    confirmDeleteModal.value = false;
    toastMessage.value = `🗑️ Удалено ${confirmDeleteCount.value} клиентов`;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}
</script>
