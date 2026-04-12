<template>
<div class="space-y-4">
    <!-- ═══ HEADER ═══ -->
    <div class="flex justify-between items-center flex-wrap gap-3">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">📜 История взаимодействий</h2>
        <div class="flex items-center gap-2">
            <div class="flex rounded-lg overflow-hidden border" style="border-color:var(--t-border)">
                <button v-for="v in viewModes" :key="v.key"
                        class="px-3 py-1.5 text-sm font-medium transition-colors"
                        :style="activeView === v.key
                            ? 'background:var(--t-primary);color:#fff'
                            : 'background:var(--t-surface);color:var(--t-text-2)'"
                        @click="activeView = v.key">{{ v.icon }} {{ v.label }}</button>
            </div>
            <VButton size="sm" variant="outline" @click="exportInteractions">📤 Экспорт</VButton>
            <VButton size="sm" variant="outline" @click="showFilters = !showFilters">
                🔍 Фильтры {{ activeFiltersCount > 0 ? `(${activeFiltersCount})` : '' }}
            </VButton>
        </div>
    </div>

    <!-- ═══ SUMMARY STATS ═══ -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <VStatCard title="Всего событий" :value="String(allEvents.length)" icon="📊" />
        <VStatCard title="Сегодня" :value="String(todayEventsCount)" icon="📅">
            <template #trend><span class="text-green-400 text-xs">+{{ todayEventsCount }}</span></template>
        </VStatCard>
        <VStatCard title="Сообщений" :value="String(messagesCount)" icon="💬" />
        <VStatCard title="Визитов" :value="String(visitsCount)" icon="✅" />
        <VStatCard title="Отмен" :value="String(cancellationsCount)" icon="❌">
            <template #trend>
                <span :class="cancellationRate > 15 ? 'text-red-400' : 'text-green-400'" class="text-xs">
                    {{ cancellationRate }}%
                </span>
            </template>
        </VStatCard>
    </div>

    <!-- ═══ FILTERS PANEL ═══ -->
    <Transition name="slide">
        <VCard v-if="showFilters" title="🔍 Фильтры">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <!-- Client search -->
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Клиент</label>
                    <VInput v-model="filters.client" placeholder="Имя, телефон..." />
                </div>
                <!-- Employee -->
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Сотрудник</label>
                    <select v-model="filters.employee" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Все</option>
                        <option v-for="emp in employeesList" :key="emp" :value="emp">{{ emp }}</option>
                    </select>
                </div>
                <!-- Event type -->
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Тип события</label>
                    <select v-model="filters.eventType" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Все</option>
                        <option v-for="et in eventTypes" :key="et.key" :value="et.key">{{ et.icon }} {{ et.label }}</option>
                    </select>
                </div>
                <!-- Channel -->
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Канал</label>
                    <select v-model="filters.channel" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="">Все</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="telegram">Telegram</option>
                        <option value="sms">SMS</option>
                        <option value="email">Email</option>
                        <option value="push">Push</option>
                        <option value="phone">Телефон</option>
                        <option value="in_person">Лично</option>
                    </select>
                </div>
                <!-- Period -->
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Период</label>
                    <select v-model="filters.period" class="w-full px-3 py-2 rounded-lg text-sm border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option value="today">Сегодня</option>
                        <option value="7d">7 дней</option>
                        <option value="30d">30 дней</option>
                        <option value="90d">90 дней</option>
                        <option value="all">Всё время</option>
                    </select>
                </div>
                <!-- Reset -->
                <div class="flex items-end">
                    <VButton size="sm" variant="outline" @click="resetFilters" class="w-full">🗑️ Сбросить</VButton>
                </div>
            </div>

            <!-- Quick filter chips -->
            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t" style="border-color:var(--t-border)">
                <button v-for="chip in quickFilterChips" :key="chip.key"
                        class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                        :style="activeQuickFilter === chip.key
                            ? 'background:var(--t-primary);color:#fff'
                            : 'background:var(--t-bg);color:var(--t-text-2)'"
                        @click="applyQuickFilter(chip.key)">
                    {{ chip.icon }} {{ chip.label }} ({{ chip.count }})
                </button>
            </div>
        </VCard>
    </Transition>

    <!-- ═══ EVENT TYPE TABS ═══ -->
    <div class="flex items-center gap-1 overflow-x-auto pb-1">
        <button v-for="et in eventTypeTabs" :key="et.key"
                class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors"
                :style="filters.eventType === et.key
                    ? 'background:var(--t-primary);color:#fff'
                    : 'background:var(--t-surface);color:var(--t-text-2)'"
                @click="filters.eventType = filters.eventType === et.key ? '' : et.key">
            {{ et.icon }} {{ et.label }}
            <span class="ml-1 opacity-70">{{ et.count }}</span>
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- VIEW 1: TIMELINE                                               -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeView === 'timeline'" class="space-y-4">
        <div v-for="group in groupedByDay" :key="group.date" class="space-y-2">
            <!-- Day header -->
            <div class="flex items-center gap-3 sticky top-0 z-10 py-2 px-3 rounded-lg"
                 style="background:var(--t-bg)">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold"
                     style="background:var(--t-primary-dim);color:var(--t-primary)">{{ group.dayNum }}</div>
                <div>
                    <div class="text-sm font-semibold" style="color:var(--t-text)">{{ group.label }}</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ group.events.length }} событий</div>
                </div>
                <div class="flex-1"></div>
                <div class="text-xs" style="color:var(--t-text-3)">
                    {{ group.summary }}
                </div>
            </div>

            <!-- Events in day -->
            <div class="ml-5 border-l-2 pl-4 space-y-2" style="border-color:var(--t-border)">
                <div v-for="ev in group.events" :key="ev.id"
                     class="relative p-3 rounded-lg border transition-all cursor-pointer hover:shadow-md"
                     style="background:var(--t-surface);border-color:var(--t-border)"
                     @click="openEventDetail(ev)">
                    <!-- Timeline dot -->
                    <div class="absolute -left-[25px] top-4 w-3 h-3 rounded-full border-2"
                         :style="`background:${eventTypeColors[ev.type]};border-color:var(--t-surface)`"></div>

                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0"
                             :style="`background:${eventTypeColors[ev.type]}20`">
                            {{ eventTypeIcons[ev.type] }}
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium" style="color:var(--t-text)">{{ ev.title }}</span>
                                <VBadge :color="eventBadgeColor(ev.type)" size="sm">{{ eventTypeLabels[ev.type] }}</VBadge>
                                <span v-if="ev.channel" class="text-[10px] px-1.5 py-0.5 rounded"
                                      style="background:var(--t-bg);color:var(--t-text-3)">
                                    {{ channelIcons[ev.channel] }} {{ ev.channel }}
                                </span>
                            </div>
                            <div class="text-xs mt-1" style="color:var(--t-text-2)">{{ ev.description }}</div>
                            <div class="flex items-center gap-3 mt-1.5">
                                <span class="text-[10px]" style="color:var(--t-text-3)">
                                    👤 {{ ev.clientName }}
                                </span>
                                <span v-if="ev.employeeName" class="text-[10px]" style="color:var(--t-text-3)">
                                    🧑‍💼 {{ ev.employeeName }}
                                </span>
                                <span class="text-[10px]" style="color:var(--t-text-3)">
                                    🕒 {{ ev.time }}
                                </span>
                                <span v-if="ev.amount" class="text-[10px] font-bold"
                                      :style="`color:${ev.amount > 0 ? '#22c55e' : '#ef4444'}`">
                                    {{ ev.amount > 0 ? '+' : '' }}{{ fmtMoney(ev.amount) }}
                                </span>
                            </div>
                        </div>

                        <!-- Quick actions -->
                        <div class="flex items-center gap-1 shrink-0">
                            <button class="w-7 h-7 rounded flex items-center justify-center text-xs hover:opacity-80 transition"
                                    style="background:var(--t-bg);color:var(--t-text-2)"
                                    @click.stop="openClientFromEvent(ev)" title="Открыть клиента">👤</button>
                            <button v-if="ev.type === 'message_in'"
                                    class="w-7 h-7 rounded flex items-center justify-center text-xs hover:opacity-80 transition"
                                    style="background:var(--t-bg);color:var(--t-text-2)"
                                    @click.stop="replyToEvent(ev)" title="Ответить">↩️</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="filteredEvents.length === 0" class="text-center py-12">
            <div class="text-4xl mb-3">📭</div>
            <div class="text-sm" style="color:var(--t-text-2)">Нет событий по выбранным фильтрам</div>
            <VButton size="sm" variant="outline" class="mt-3" @click="resetFilters">Сбросить фильтры</VButton>
        </div>

        <!-- Load more -->
        <div v-if="hasMoreEvents" class="text-center">
            <VButton size="sm" variant="outline" @click="loadMore">
                📥 Загрузить ещё ({{ remainingCount }})
            </VButton>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- VIEW 2: TABLE                                                  -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeView === 'table'">
        <VCard>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" style="border-color:var(--t-border)">
                            <th class="text-left py-2 px-3 font-medium" style="color:var(--t-text-2)">Дата/Время</th>
                            <th class="text-left py-2 px-3 font-medium" style="color:var(--t-text-2)">Тип</th>
                            <th class="text-left py-2 px-3 font-medium" style="color:var(--t-text-2)">Клиент</th>
                            <th class="text-left py-2 px-3 font-medium" style="color:var(--t-text-2)">Описание</th>
                            <th class="text-left py-2 px-3 font-medium" style="color:var(--t-text-2)">Сотрудник</th>
                            <th class="text-left py-2 px-3 font-medium" style="color:var(--t-text-2)">Канал</th>
                            <th class="text-right py-2 px-3 font-medium" style="color:var(--t-text-2)">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ev in paginatedEvents" :key="ev.id"
                            class="border-b cursor-pointer transition hover:brightness-110"
                            style="border-color:var(--t-border)"
                            @click="openEventDetail(ev)">
                            <td class="py-2 px-3 whitespace-nowrap" style="color:var(--t-text-3)">
                                {{ ev.dateFormatted }}<br>
                                <span class="text-[10px]">{{ ev.time }}</span>
                            </td>
                            <td class="py-2 px-3">
                                <VBadge :color="eventBadgeColor(ev.type)" size="sm">
                                    {{ eventTypeIcons[ev.type] }} {{ eventTypeLabels[ev.type] }}
                                </VBadge>
                            </td>
                            <td class="py-2 px-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0"
                                         style="background:var(--t-primary-dim);color:var(--t-primary)">
                                        {{ ev.clientName.charAt(0) }}
                                    </div>
                                    <span class="text-sm" style="color:var(--t-text)">{{ ev.clientName }}</span>
                                </div>
                            </td>
                            <td class="py-2 px-3 max-w-xs truncate" style="color:var(--t-text-2)">
                                {{ ev.description }}
                            </td>
                            <td class="py-2 px-3 text-xs" style="color:var(--t-text-3)">
                                {{ ev.employeeName || '—' }}
                            </td>
                            <td class="py-2 px-3 text-xs" style="color:var(--t-text-3)">
                                <span v-if="ev.channel">{{ channelIcons[ev.channel] }} {{ ev.channel }}</span>
                                <span v-else>—</span>
                            </td>
                            <td class="py-2 px-3 text-right text-sm font-medium"
                                :style="ev.amount ? `color:${ev.amount > 0 ? '#22c55e' : '#ef4444'}` : 'color:var(--t-text-3)'">
                                {{ ev.amount ? (ev.amount > 0 ? '+' : '') + fmtMoney(ev.amount) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between mt-4 pt-3 border-t" style="border-color:var(--t-border)">
                <span class="text-xs" style="color:var(--t-text-3)">
                    {{ paginationStart }}–{{ paginationEnd }} из {{ filteredEvents.length }}
                </span>
                <div class="flex gap-1">
                    <VButton size="sm" variant="outline" :disabled="currentPage <= 1" @click="currentPage--">←</VButton>
                    <VButton size="sm" variant="outline" :disabled="currentPage >= totalPages" @click="currentPage++">→</VButton>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- VIEW 3: STATISTICS                                             -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeView === 'stats'" class="space-y-4">
        <!-- Event distribution by type -->
        <VCard title="📊 Распределение по типам событий">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-for="stat in eventTypeStats" :key="stat.type"
                     class="p-3 rounded-lg border text-center cursor-pointer transition hover:shadow"
                     style="background:var(--t-bg);border-color:var(--t-border)"
                     @click="filters.eventType = stat.type">
                    <div class="text-xl mb-1">{{ eventTypeIcons[stat.type] }}</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ stat.count }}</div>
                    <div class="text-xs" style="color:var(--t-text-2)">{{ eventTypeLabels[stat.type] }}</div>
                    <div class="w-full h-1.5 rounded-full mt-2 overflow-hidden" style="background:var(--t-border)">
                        <div class="h-full rounded-full transition-all"
                             :style="`width:${stat.pct}%;background:${eventTypeColors[stat.type]}`"></div>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Activity by hour -->
        <div class="grid md:grid-cols-2 gap-4">
            <VCard title="🕒 Активность по часам">
                <div class="flex items-end gap-1 h-32">
                    <div v-for="h in hourlyActivity" :key="h.hour" class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[9px] font-bold" style="color:var(--t-text-3)">{{ h.count }}</span>
                        <div class="w-full rounded-t transition-all"
                             :style="`height:${h.pct}%;background:var(--t-primary);opacity:${0.3 + h.pct / 140}`"></div>
                        <span class="text-[9px]" style="color:var(--t-text-3)">{{ h.label }}</span>
                    </div>
                </div>
            </VCard>

            <VCard title="📅 Активность по дням недели">
                <div class="space-y-2">
                    <div v-for="d in weekdayActivity" :key="d.day"
                         class="flex items-center gap-3">
                        <span class="text-xs w-8 font-medium" style="color:var(--t-text-2)">{{ d.label }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-border)">
                            <div class="h-full rounded-full transition-all"
                                 :style="`width:${d.pct}%;background:var(--t-primary)`"></div>
                        </div>
                        <span class="text-xs font-bold w-8 text-right" style="color:var(--t-text)">{{ d.count }}</span>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Top clients by interactions -->
        <VCard title="🏆 Топ-10 по количеству взаимодействий">
            <div class="space-y-2 max-h-80 overflow-y-auto">
                <div v-for="(tc, idx) in topClientsByInteractions" :key="tc.clientId"
                     class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:brightness-110 transition"
                     style="background:var(--t-bg)"
                     @click="$emit('open-client', tc.clientId)">
                    <span class="text-sm font-bold w-5" style="color:var(--t-text-3)">{{ idx + 1 }}</span>
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                         style="background:var(--t-primary-dim);color:var(--t-primary)">{{ tc.name.charAt(0) }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color:var(--t-text)">{{ tc.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">
                            {{ tc.visits }} визитов · {{ tc.messages }} сообщений · {{ tc.bonuses }} бонусных операций
                        </div>
                    </div>
                    <span class="text-sm font-bold" style="color:var(--t-primary)">{{ tc.total }}</span>
                </div>
            </div>
        </VCard>

        <!-- Channel distribution -->
        <VCard title="📡 Распределение по каналам коммуникации">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-for="ch in channelStats" :key="ch.channel"
                     class="p-3 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-xl mb-1">{{ channelIcons[ch.channel] }}</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ ch.count }}</div>
                    <div class="text-xs" style="color:var(--t-text-2)">{{ ch.channel }}</div>
                    <div class="text-[10px] mt-1" :class="ch.responseRate > 70 ? 'text-green-400' : 'text-yellow-400'">
                        Ответ: {{ ch.responseRate }}%
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Employee performance -->
        <VCard title="👩‍💼 Активность сотрудников">
            <div class="space-y-2">
                <div v-for="emp in employeeActivity" :key="emp.name"
                     class="p-3 rounded-lg border flex items-center gap-3"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                         style="background:var(--t-primary-dim);color:var(--t-primary)">{{ emp.name.charAt(0) }}</div>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ emp.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ emp.role }}</div>
                    </div>
                    <div class="flex gap-4 text-center">
                        <div>
                            <div class="text-sm font-bold" style="color:var(--t-primary)">{{ emp.interactions }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Взаимод.</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold" style="color:var(--t-text)">{{ emp.messages }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Сообщ.</div>
                        </div>
                        <div>
                            <div class="text-sm font-bold" :class="emp.avgResponseMin < 15 ? 'text-green-400' : 'text-yellow-400'">
                                {{ emp.avgResponseMin }}м
                            </div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Ср. ответ</div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- EVENT DETAIL MODAL                                             -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <VModal :show="showEventDetail" @close="showEventDetail = false"
            :title="activeEvent ? eventTypeIcons[activeEvent.type] + ' ' + eventTypeLabels[activeEvent.type] : ''" size="lg">
        <div v-if="activeEvent" class="space-y-4">
            <!-- Event header -->
            <div class="flex items-center gap-3 p-3 rounded-lg" style="background:var(--t-bg)">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg"
                     :style="`background:${eventTypeColors[activeEvent.type]}20`">
                    {{ eventTypeIcons[activeEvent.type] }}
                </div>
                <div class="flex-1">
                    <div class="text-sm font-semibold" style="color:var(--t-text)">{{ activeEvent.title }}</div>
                    <div class="text-xs" style="color:var(--t-text-3)">
                        {{ activeEvent.dateFormatted }} · {{ activeEvent.time }}
                    </div>
                </div>
                <VBadge :color="eventBadgeColor(activeEvent.type)" size="sm">
                    {{ eventTypeLabels[activeEvent.type] }}
                </VBadge>
            </div>

            <!-- Event details grid -->
            <div class="grid md:grid-cols-2 gap-3">
                <div class="p-3 rounded-lg border" style="background:var(--t-surface);border-color:var(--t-border)">
                    <div class="text-xs" style="color:var(--t-text-3)">Клиент</div>
                    <div class="text-sm font-medium mt-1" style="color:var(--t-text)">{{ activeEvent.clientName }}</div>
                    <div v-if="activeEvent.clientPhone" class="text-xs" style="color:var(--t-text-2)">
                        {{ activeEvent.clientPhone }}
                    </div>
                </div>
                <div class="p-3 rounded-lg border" style="background:var(--t-surface);border-color:var(--t-border)">
                    <div class="text-xs" style="color:var(--t-text-3)">Сотрудник</div>
                    <div class="text-sm font-medium mt-1" style="color:var(--t-text)">
                        {{ activeEvent.employeeName || 'Система' }}
                    </div>
                    <div v-if="activeEvent.employeeRole" class="text-xs" style="color:var(--t-text-2)">
                        {{ activeEvent.employeeRole }}
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="p-3 rounded-lg border" style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="text-xs mb-1" style="color:var(--t-text-3)">Описание</div>
                <div class="text-sm" style="color:var(--t-text)">{{ activeEvent.description }}</div>
            </div>

            <!-- Amount if any -->
            <div v-if="activeEvent.amount" class="p-3 rounded-lg border flex items-center justify-between"
                 style="background:var(--t-surface);border-color:var(--t-border)">
                <span class="text-xs" style="color:var(--t-text-3)">Сумма</span>
                <span class="text-lg font-bold"
                      :style="`color:${activeEvent.amount > 0 ? '#22c55e' : '#ef4444'}`">
                    {{ activeEvent.amount > 0 ? '+' : '' }}{{ fmtMoney(activeEvent.amount) }}
                </span>
            </div>

            <!-- Related events -->
            <div v-if="relatedEvents.length" class="space-y-2">
                <div class="text-xs font-semibold" style="color:var(--t-text-2)">Связанные события</div>
                <div v-for="rel in relatedEvents" :key="rel.id"
                     class="p-2 rounded-lg border flex items-center gap-2 cursor-pointer hover:brightness-110 transition"
                     style="background:var(--t-bg);border-color:var(--t-border)"
                     @click="openEventDetail(rel)">
                    <span class="text-sm">{{ eventTypeIcons[rel.type] }}</span>
                    <span class="text-xs flex-1" style="color:var(--t-text)">{{ rel.title }}</span>
                    <span class="text-[10px]" style="color:var(--t-text-3)">{{ rel.dateFormatted }} {{ rel.time }}</span>
                </div>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showEventDetail = false">Закрыть</VButton>
            <VButton @click="openClientFromEvent(activeEvent)">👤 Карточка клиента</VButton>
        </template>
    </VModal>
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

/* ═══════════════════════════════════════════════════════════════════ */
/*  PROPS & EMITS                                                      */
/* ═══════════════════════════════════════════════════════════════════ */
const props = defineProps({
    clients: { type: Array, default: () => [] },
    masters: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});
const emit = defineEmits(['open-client', 'reply-message']);

/* ═══════════════════════════════════════════════════════════════════ */
/*  VIEW MODE                                                          */
/* ═══════════════════════════════════════════════════════════════════ */
const viewModes = [
    { key: 'timeline', icon: '📜', label: 'Лента' },
    { key: 'table',    icon: '📊', label: 'Таблица' },
    { key: 'stats',    icon: '📈', label: 'Статистика' },
];
const activeView = ref('timeline');

/* ═══════════════════════════════════════════════════════════════════ */
/*  CONSTANTS                                                          */
/* ═══════════════════════════════════════════════════════════════════ */
const eventTypes = [
    { key: 'booking_created', icon: '📋', label: 'Запись создана' },
    { key: 'visit_completed', icon: '✅', label: 'Визит завершён' },
    { key: 'cancellation',    icon: '❌', label: 'Отмена' },
    { key: 'message_out',     icon: '📤', label: 'Сообщение исх.' },
    { key: 'message_in',      icon: '📥', label: 'Сообщение вх.' },
    { key: 'bonus_award',     icon: '🎁', label: 'Бонус начислен' },
    { key: 'bonus_deduct',    icon: '➖', label: 'Бонус списан' },
    { key: 'review',          icon: '⭐', label: 'Отзыв' },
    { key: 'profile_change',  icon: '✏️', label: 'Изменение профиля' },
    { key: 'campaign_sent',   icon: '📧', label: 'Рассылка' },
    { key: 'segment_change',  icon: '🏷️', label: 'Смена сегмента' },
    { key: 'no_show',         icon: '🚫', label: 'Неявка' },
];
const eventTypeIcons = Object.fromEntries(eventTypes.map(e => [e.key, e.icon]));
const eventTypeLabels = Object.fromEntries(eventTypes.map(e => [e.key, e.label]));
const eventTypeColors = {
    booking_created: '#3b82f6', visit_completed: '#22c55e', cancellation: '#ef4444',
    message_out: '#8b5cf6', message_in: '#06b6d4', bonus_award: '#f59e0b',
    bonus_deduct: '#f97316', review: '#eab308', profile_change: '#6b7280',
    campaign_sent: '#ec4899', segment_change: '#14b8a6', no_show: '#9ca3af',
};
const channelIcons = {
    whatsapp: '📱', telegram: '✈️', sms: '💬', email: '📧',
    push: '🔔', phone: '📞', in_person: '🤝', system: '⚙️',
};

function eventBadgeColor(type) {
    const map = {
        booking_created: 'blue', visit_completed: 'green', cancellation: 'red',
        message_out: 'purple', message_in: 'blue', bonus_award: 'yellow',
        bonus_deduct: 'orange', review: 'yellow', profile_change: 'gray',
        campaign_sent: 'purple', segment_change: 'green', no_show: 'gray',
    };
    return map[type] || 'gray';
}
function fmtMoney(v) {
    if (v == null) return '0 ₽';
    return Math.abs(Number(v)).toLocaleString('ru-RU') + ' ₽';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  FILTERS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
const showFilters = ref(false);
const filters = reactive({
    client: '',
    employee: '',
    eventType: '',
    channel: '',
    period: '30d',
});
const activeQuickFilter = ref('');

const employeesList = computed(() => {
    const names = new Set(allEvents.value.map(e => e.employeeName).filter(Boolean));
    return [...names].sort();
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.client) count++;
    if (filters.employee) count++;
    if (filters.eventType) count++;
    if (filters.channel) count++;
    if (filters.period !== '30d') count++;
    return count;
});

function resetFilters() {
    Object.assign(filters, { client: '', employee: '', eventType: '', channel: '', period: '30d' });
    activeQuickFilter.value = '';
}

const quickFilterChips = computed(() => [
    { key: 'today',     icon: '📅', label: 'Сегодня',   count: allEvents.value.filter(e => isToday(e.date)).length },
    { key: 'messages',  icon: '💬', label: 'Сообщения', count: allEvents.value.filter(e => e.type.startsWith('message')).length },
    { key: 'bookings',  icon: '📋', label: 'Записи',    count: allEvents.value.filter(e => e.type === 'booking_created').length },
    { key: 'bonuses',   icon: '🎁', label: 'Бонусы',    count: allEvents.value.filter(e => e.type.startsWith('bonus')).length },
    { key: 'problems',  icon: '⚠️', label: 'Проблемы',  count: allEvents.value.filter(e => ['cancellation', 'no_show'].includes(e.type)).length },
]);

function applyQuickFilter(key) {
    activeQuickFilter.value = activeQuickFilter.value === key ? '' : key;
    if (key === 'today') filters.period = 'today';
    else if (key === 'messages') filters.eventType = 'message_out';
    else if (key === 'bookings') filters.eventType = 'booking_created';
    else if (key === 'bonuses') filters.eventType = 'bonus_award';
    else if (key === 'problems') filters.eventType = 'cancellation';
    else resetFilters();
}

function isToday(dateStr) {
    const today = new Date();
    const d = new Date(dateStr);
    return d.getDate() === today.getDate() && d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear();
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  ALL EVENTS DATA                                                    */
/* ═══════════════════════════════════════════════════════════════════ */
const allEvents = ref([
    { id: 1,  type: 'visit_completed', date: '2026-04-09', time: '13:30', clientId: 9,  clientName: 'Виктория Соловьёва', clientPhone: '+7 900 999-00-11', employeeName: 'Светлана Романова', employeeRole: 'Мастер-косметолог', channel: 'in_person', title: 'Визит завершён — Косметология', description: 'Чистка лица + уход. Начислено 1120 бонусов (10%).', amount: 11200, dateFormatted: '09.04.2026' },
    { id: 2,  type: 'message_out',     date: '2026-04-09', time: '09:00', clientId: 9,  clientName: 'Виктория Соловьёва', clientPhone: '+7 900 999-00-11', employeeName: 'Система',           employeeRole: null,                channel: 'whatsapp', title: 'Напоминание о записи',       description: 'Виктория, ваша запись на косметологию сегодня в 12:00.',  amount: null, dateFormatted: '09.04.2026' },
    { id: 3,  type: 'bonus_award',     date: '2026-04-09', time: '13:35', clientId: 9,  clientName: 'Виктория Соловьёва', clientPhone: '+7 900 999-00-11', employeeName: 'Система',           employeeRole: null,                channel: 'system',   title: 'Начислены бонусы',           description: 'Кэшбэк 10% за визит. Баланс: 5 600 → 6 720 ₽.',        amount: 1120, dateFormatted: '09.04.2026' },
    { id: 4,  type: 'booking_created', date: '2026-04-08', time: '20:15', clientId: 1,  clientName: 'Мария Королёва',     clientPhone: '+7 900 111-22-33', employeeName: null,                employeeRole: null,                channel: 'in_person', title: 'Новая запись онлайн',        description: 'Окрашивание балаяж + стрижка кончиков + уход Olaplex. 12.04 в 14:30.', amount: null, dateFormatted: '08.04.2026' },
    { id: 5,  type: 'visit_completed', date: '2026-04-08', time: '13:00', clientId: 1,  clientName: 'Мария Королёва',     clientPhone: '+7 900 111-22-33', employeeName: 'Анна Соколова',     employeeRole: 'Стилист-колорист',  channel: 'in_person', title: 'Визит завершён — Окрашивание', description: 'Балаяж + стрижка. Итого 6 500 ₽, бонусами 500 ₽.',      amount: 6500, dateFormatted: '08.04.2026' },
    { id: 6,  type: 'bonus_award',     date: '2026-04-08', time: '13:05', clientId: 1,  clientName: 'Мария Королёва',     clientPhone: '+7 900 111-22-33', employeeName: 'Система',           employeeRole: null,                channel: 'system',   title: 'Начислены бонусы',           description: 'Кэшбэк 10% за визит. Баланс: 3 470 → 4 120 ₽.',        amount: 650,  dateFormatted: '08.04.2026' },
    { id: 7,  type: 'message_out',     date: '2026-04-08', time: '13:00', clientId: 1,  clientName: 'Мария Королёва',     clientPhone: '+7 900 111-22-33', employeeName: 'Система',           employeeRole: null,                channel: 'whatsapp', title: 'Благодарность за визит',     description: 'Спасибо за визит! Начислили 650 бонусов 🎁',            amount: null, dateFormatted: '08.04.2026' },
    { id: 8,  type: 'message_in',      date: '2026-04-08', time: '13:05', clientId: 1,  clientName: 'Мария Королёва',     clientPhone: '+7 900 111-22-33', employeeName: null,                employeeRole: null,                channel: 'whatsapp', title: 'Ответ клиента',              description: 'Спасибо, до встречи!',                                  amount: null, dateFormatted: '08.04.2026' },
    { id: 9,  type: 'review',          date: '2026-04-08', time: '14:20', clientId: 1,  clientName: 'Мария Королёва',     clientPhone: '+7 900 111-22-33', employeeName: null,                employeeRole: null,                channel: 'in_person', title: 'Оставлен отзыв ⭐⭐⭐⭐⭐', description: 'Идеальный цвет! Анна — мастер от бога.',                 amount: null, dateFormatted: '08.04.2026' },
    { id: 10, type: 'message_out',     date: '2026-04-07', time: '18:00', clientId: 1,  clientName: 'Мария Королёва',     clientPhone: '+7 900 111-22-33', employeeName: 'Система',           employeeRole: null,                channel: 'sms',      title: 'Напоминание о записи',       description: 'Мария, напоминаем о записи на завтра в 11:00.',         amount: null, dateFormatted: '07.04.2026' },
    { id: 11, type: 'message_out',     date: '2026-04-07', time: '10:00', clientId: 2,  clientName: 'Елена Петрова',      clientPhone: '+7 900 222-33-44', employeeName: 'Администратор',     employeeRole: 'Администратор',     channel: 'whatsapp', title: 'Подтверждение записи',       description: 'Елена, ваша запись на 14:00 подтверждена.',              amount: null, dateFormatted: '07.04.2026' },
    { id: 12, type: 'message_in',      date: '2026-04-07', time: '10:30', clientId: 2,  clientName: 'Елена Петрова',      clientPhone: '+7 900 222-33-44', employeeName: null,                employeeRole: null,                channel: 'whatsapp', title: 'Запрос на перенос',          description: 'Можно ли перенести на 15:00?',                          amount: null, dateFormatted: '07.04.2026' },
    { id: 13, type: 'visit_completed', date: '2026-04-07', time: '16:30', clientId: 2,  clientName: 'Елена Петрова',      clientPhone: '+7 900 222-33-44', employeeName: 'Ольга Демидова',    employeeRole: 'Мастер маникюра',   channel: 'in_person', title: 'Визит завершён — Маникюр',   description: 'Маникюр гель-лак. Итого 2 800 ₽.',                      amount: 2800, dateFormatted: '07.04.2026' },
    { id: 14, type: 'booking_created', date: '2026-04-06', time: '15:30', clientId: 5,  clientName: 'Наталья Белова',     clientPhone: '+7 900 555-66-77', employeeName: null,                employeeRole: null,                channel: 'in_person', title: 'Первая запись',              description: 'Коррекция бровей. 10.04 в 12:00.',                      amount: null, dateFormatted: '06.04.2026' },
    { id: 15, type: 'bonus_award',     date: '2026-04-06', time: '15:35', clientId: 5,  clientName: 'Наталья Белова',     clientPhone: '+7 900 555-66-77', employeeName: 'Система',           employeeRole: null,                channel: 'system',   title: 'Приветственный бонус',       description: 'Начислено 200 ₽ приветственных бонусов.',                amount: 200,  dateFormatted: '06.04.2026' },
    { id: 16, type: 'profile_change',  date: '2026-04-06', time: '15:32', clientId: 5,  clientName: 'Наталья Белова',     clientPhone: '+7 900 555-66-77', employeeName: 'Администратор',     employeeRole: 'Администратор',     channel: 'system',   title: 'Создан профиль клиента',    description: 'Новый клиент из рекламы ВК.',                           amount: null, dateFormatted: '06.04.2026' },
    { id: 17, type: 'cancellation',    date: '2026-04-05', time: '11:00', clientId: 7,  clientName: 'Татьяна Новикова',   clientPhone: '+7 900 777-88-99', employeeName: null,                employeeRole: null,                channel: 'phone',    title: 'Отмена записи',              description: 'Отменена запись на педикюр 06.04. Причина: болезнь.',    amount: null, dateFormatted: '05.04.2026' },
    { id: 18, type: 'visit_completed', date: '2026-04-05', time: '15:30', clientId: 4,  clientName: 'Ирина Морозова',     clientPhone: '+7 900 444-55-66', employeeName: 'Светлана Романова', employeeRole: 'Мастер-косметолог', channel: 'in_person', title: 'Визит завершён — Массаж',    description: 'Массаж расслабляющий 60 мин. Итого 3 500 ₽.',           amount: 3500, dateFormatted: '05.04.2026' },
    { id: 19, type: 'campaign_sent',   date: '2026-04-05', time: '10:00', clientId: null, clientName: 'Все — Лояльные',  clientPhone: null,                employeeName: 'Маркетолог',        employeeRole: 'Маркетолог',        channel: 'sms',      title: 'Рассылка: Весенняя акция',  description: 'Отправлена SMS-рассылка «Весенняя акция -20%» — 45 клиентов.', amount: null, dateFormatted: '05.04.2026' },
    { id: 20, type: 'segment_change',  date: '2026-04-04', time: '03:00', clientId: 6,  clientName: 'Анастасия Кузнецова', clientPhone: '+7 900 666-77-88', employeeName: 'Система',          employeeRole: null,                channel: 'system',   title: 'Смена сегмента',             description: 'Лояльная → VIP (сумма > 50 000 ₽, визитов > 10).',       amount: null, dateFormatted: '04.04.2026' },
    { id: 21, type: 'visit_completed', date: '2026-04-04', time: '12:00', clientId: 6,  clientName: 'Анастасия Кузнецова', clientPhone: '+7 900 666-77-88', employeeName: 'Анна Соколова',    employeeRole: 'Стилист-колорист',  channel: 'in_person', title: 'Визит завершён — Стрижка',   description: 'Стрижка + укладка. Итого 3 200 ₽.',                     amount: 3200, dateFormatted: '04.04.2026' },
    { id: 22, type: 'bonus_deduct',    date: '2026-04-03', time: '14:00', clientId: 11, clientName: 'Алина Фёдорова',     clientPhone: '+7 900 200-30-40', employeeName: 'Система',           employeeRole: null,                channel: 'system',   title: 'Списание бонусов',           description: 'Оплата бонусами 340 ₽ за коррекцию бровей.',             amount: -340, dateFormatted: '03.04.2026' },
    { id: 23, type: 'visit_completed', date: '2026-04-03', time: '13:30', clientId: 11, clientName: 'Алина Фёдорова',     clientPhone: '+7 900 200-30-40', employeeName: 'Кристина Лебедева', employeeRole: 'Бровист',           channel: 'in_person', title: 'Визит завершён — Брови',     description: 'Коррекция + окрашивание бровей. Итого 2 800 ₽ (340 бонусами).', amount: 2800, dateFormatted: '03.04.2026' },
    { id: 24, type: 'message_in',      date: '2026-04-03', time: '11:00', clientId: 11, clientName: 'Алина Фёдорова',     clientPhone: '+7 900 200-30-40', employeeName: null,                employeeRole: null,                channel: 'telegram', title: 'Вопрос об уходе',            description: 'Подскажите, какой уход за бровями вы рекомендуете?',     amount: null, dateFormatted: '03.04.2026' },
    { id: 25, type: 'message_out',     date: '2026-04-03', time: '11:15', clientId: 11, clientName: 'Алина Фёдорова',     clientPhone: '+7 900 200-30-40', employeeName: 'Кристина Лебедева', employeeRole: 'Бровист',           channel: 'telegram', title: 'Ответ мастера',              description: 'Рекомендую масло для бровей Brow Xenna, наносить на ночь.', amount: null, dateFormatted: '03.04.2026' },
    { id: 26, type: 'no_show',         date: '2026-04-02', time: '14:00', clientId: 10, clientName: 'Регина Карпова',     clientPhone: '+7 900 100-20-30', employeeName: 'Ольга Демидова',    employeeRole: 'Мастер маникюра',   channel: 'in_person', title: 'Неявка клиента',             description: 'Клиент не пришёл на маникюр 14:00. Попытка дозвона — нет ответа.', amount: null, dateFormatted: '02.04.2026' },
    { id: 27, type: 'message_out',     date: '2026-04-02', time: '14:15', clientId: 10, clientName: 'Регина Карпова',     clientPhone: '+7 900 100-20-30', employeeName: 'Администратор',     employeeRole: 'Администратор',     channel: 'sms',      title: 'SMS о неявке',               description: 'Регина, мы ждали вас сегодня в 14:00. Перезапишемся?',   amount: null, dateFormatted: '02.04.2026' },
    { id: 28, type: 'visit_completed', date: '2026-04-01', time: '16:00', clientId: 3,  clientName: 'Дарья Волкова',      clientPhone: '+7 900 333-44-55', employeeName: 'Анна Соколова',     employeeRole: 'Стилист-колорист',  channel: 'in_person', title: 'Визит завершён — Окрашивание', description: 'Первое окрашивание тон-в-тон. Итого 4 500 ₽.',          amount: 4500, dateFormatted: '01.04.2026' },
    { id: 29, type: 'booking_created', date: '2026-04-01', time: '09:00', clientId: 3,  clientName: 'Дарья Волкова',      clientPhone: '+7 900 333-44-55', employeeName: null,                employeeRole: null,                channel: 'in_person', title: 'Запись онлайн',              description: 'Окрашивание тон-в-тон к Анне Соколовой, 01.04 в 14:00.', amount: null, dateFormatted: '01.04.2026' },
    { id: 30, type: 'profile_change',  date: '2026-03-30', time: '12:00', clientId: 12, clientName: 'Полина Зайцева',     clientPhone: '+7 900 300-40-50', employeeName: 'Администратор',     employeeRole: 'Администратор',     channel: 'system',   title: 'Обновлён профиль',           description: 'Добавлена аллергия: «Аллергия на краску».',              amount: null, dateFormatted: '30.03.2026' },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  EVENT TYPE TABS                                                    */
/* ═══════════════════════════════════════════════════════════════════ */
const eventTypeTabs = computed(() => {
    const counts = {};
    for (const e of allEvents.value) counts[e.type] = (counts[e.type] || 0) + 1;
    return [
        { key: '',                icon: '📊', label: 'Все',       count: allEvents.value.length },
        { key: 'visit_completed', icon: '✅', label: 'Визиты',    count: counts['visit_completed'] || 0 },
        { key: 'booking_created', icon: '📋', label: 'Записи',    count: counts['booking_created'] || 0 },
        { key: 'message_out',     icon: '💬', label: 'Сообщения', count: (counts['message_out'] || 0) + (counts['message_in'] || 0) },
        { key: 'bonus_award',     icon: '🎁', label: 'Бонусы',    count: (counts['bonus_award'] || 0) + (counts['bonus_deduct'] || 0) },
        { key: 'cancellation',    icon: '❌', label: 'Отмены',    count: (counts['cancellation'] || 0) + (counts['no_show'] || 0) },
        { key: 'review',          icon: '⭐', label: 'Отзывы',    count: counts['review'] || 0 },
        { key: 'profile_change',  icon: '✏️', label: 'Профиль',   count: (counts['profile_change'] || 0) + (counts['segment_change'] || 0) },
    ];
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  FILTERED & COMPUTED                                                */
/* ═══════════════════════════════════════════════════════════════════ */
const filteredEvents = computed(() => {
    let list = [...allEvents.value];

    if (filters.client) {
        const q = filters.client.toLowerCase();
        list = list.filter(e => e.clientName.toLowerCase().includes(q) || (e.clientPhone || '').includes(q));
    }
    if (filters.employee) list = list.filter(e => e.employeeName === filters.employee);
    if (filters.eventType) {
        if (filters.eventType === 'message_out') {
            list = list.filter(e => e.type === 'message_out' || e.type === 'message_in');
        } else if (filters.eventType === 'bonus_award') {
            list = list.filter(e => e.type === 'bonus_award' || e.type === 'bonus_deduct');
        } else if (filters.eventType === 'cancellation') {
            list = list.filter(e => e.type === 'cancellation' || e.type === 'no_show');
        } else if (filters.eventType === 'profile_change') {
            list = list.filter(e => e.type === 'profile_change' || e.type === 'segment_change');
        } else {
            list = list.filter(e => e.type === filters.eventType);
        }
    }
    if (filters.channel) list = list.filter(e => e.channel === filters.channel);
    if (filters.period !== 'all') {
        const now = new Date();
        const daysMap = { today: 0, '7d': 7, '30d': 30, '90d': 90 };
        const days = daysMap[filters.period] ?? 30;
        if (filters.period === 'today') {
            list = list.filter(e => isToday(e.date));
        } else {
            const cutoff = new Date(now.getTime() - days * 86400000);
            list = list.filter(e => new Date(e.date) >= cutoff);
        }
    }

    list.sort((a, b) => {
        const dc = b.date.localeCompare(a.date);
        return dc !== 0 ? dc : b.time.localeCompare(a.time);
    });
    return list;
});

/* Summary stats */
const todayEventsCount = computed(() => allEvents.value.filter(e => isToday(e.date)).length);
const messagesCount = computed(() => allEvents.value.filter(e => e.type.startsWith('message')).length);
const visitsCount = computed(() => allEvents.value.filter(e => e.type === 'visit_completed').length);
const cancellationsCount = computed(() => allEvents.value.filter(e => e.type === 'cancellation' || e.type === 'no_show').length);
const cancellationRate = computed(() => {
    const bookings = allEvents.value.filter(e => e.type === 'booking_created').length;
    return bookings > 0 ? Math.round(cancellationsCount.value / bookings * 100) : 0;
});

/* Timeline grouping by day */
const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
const monthNames = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

const visibleCount = ref(50);
const displayedEvents = computed(() => filteredEvents.value.slice(0, visibleCount.value));
const hasMoreEvents = computed(() => filteredEvents.value.length > visibleCount.value);
const remainingCount = computed(() => filteredEvents.value.length - visibleCount.value);
function loadMore() { visibleCount.value += 30; }

const groupedByDay = computed(() => {
    const groups = {};
    for (const e of displayedEvents.value) {
        if (!groups[e.date]) {
            const d = new Date(e.date);
            const dayNum = String(d.getDate()).padStart(2, '0');
            const label = `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}, ${dayNames[d.getDay()]}`;
            const eventsInDay = displayedEvents.value.filter(x => x.date === e.date);
            const visitsInDay = eventsInDay.filter(x => x.type === 'visit_completed').length;
            const msgsInDay = eventsInDay.filter(x => x.type.startsWith('message')).length;
            const summary = [
                visitsInDay > 0 ? `${visitsInDay} визит.` : null,
                msgsInDay > 0 ? `${msgsInDay} сообщ.` : null,
            ].filter(Boolean).join(' · ') || '';
            groups[e.date] = { date: e.date, dayNum, label, summary, events: [] };
        }
        groups[e.date].events.push(e);
    }
    return Object.values(groups).sort((a, b) => b.date.localeCompare(a.date));
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  TABLE PAGINATION                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const perPage = 15;
const currentPage = ref(1);
const totalPages = computed(() => Math.max(1, Math.ceil(filteredEvents.value.length / perPage)));
const paginationStart = computed(() => (currentPage.value - 1) * perPage + 1);
const paginationEnd = computed(() => Math.min(currentPage.value * perPage, filteredEvents.value.length));
const paginatedEvents = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredEvents.value.slice(start, start + perPage);
});
watch(() => filters.client + filters.employee + filters.eventType + filters.channel + filters.period, () => { currentPage.value = 1; });

/* ═══════════════════════════════════════════════════════════════════ */
/*  STATISTICS                                                         */
/* ═══════════════════════════════════════════════════════════════════ */
const eventTypeStats = computed(() => {
    const counts = {};
    for (const e of allEvents.value) counts[e.type] = (counts[e.type] || 0) + 1;
    const maxCount = Math.max(...Object.values(counts), 1);
    return eventTypes.map(et => ({
        type: et.key,
        count: counts[et.key] || 0,
        pct: Math.round(((counts[et.key] || 0) / maxCount) * 100),
    })).filter(s => s.count > 0);
});

const hourlyActivity = computed(() => {
    const hours = Array.from({ length: 12 }, (_, i) => ({ hour: i + 8, label: `${i + 8}`, count: 0, pct: 0 }));
    for (const e of allEvents.value) {
        const h = parseInt(e.time.split(':')[0], 10);
        const slot = hours.find(x => x.hour === h);
        if (slot) slot.count++;
    }
    const maxCount = Math.max(...hours.map(h => h.count), 1);
    for (const h of hours) h.pct = Math.round(h.count / maxCount * 100);
    return hours;
});

const weekdayActivity = computed(() => {
    const days = [
        { day: 1, label: 'Пн', count: 0, pct: 0 },
        { day: 2, label: 'Вт', count: 0, pct: 0 },
        { day: 3, label: 'Ср', count: 0, pct: 0 },
        { day: 4, label: 'Чт', count: 0, pct: 0 },
        { day: 5, label: 'Пт', count: 0, pct: 0 },
        { day: 6, label: 'Сб', count: 0, pct: 0 },
        { day: 0, label: 'Вс', count: 0, pct: 0 },
    ];
    for (const e of allEvents.value) {
        const d = new Date(e.date).getDay();
        const slot = days.find(x => x.day === d);
        if (slot) slot.count++;
    }
    const maxCount = Math.max(...days.map(d => d.count), 1);
    for (const d of days) d.pct = Math.round(d.count / maxCount * 100);
    return days;
});

const topClientsByInteractions = computed(() => {
    const map = {};
    for (const e of allEvents.value) {
        if (!e.clientId) continue;
        if (!map[e.clientId]) map[e.clientId] = { clientId: e.clientId, name: e.clientName, total: 0, visits: 0, messages: 0, bonuses: 0 };
        map[e.clientId].total++;
        if (e.type === 'visit_completed') map[e.clientId].visits++;
        if (e.type.startsWith('message')) map[e.clientId].messages++;
        if (e.type.startsWith('bonus')) map[e.clientId].bonuses++;
    }
    return Object.values(map).sort((a, b) => b.total - a.total).slice(0, 10);
});

const channelStats = computed(() => {
    const map = {};
    for (const e of allEvents.value) {
        if (!e.channel || e.channel === 'system') continue;
        if (!map[e.channel]) map[e.channel] = { channel: e.channel, count: 0, responseRate: 0 };
        map[e.channel].count++;
    }
    const responseRates = { whatsapp: 85, telegram: 78, sms: 42, email: 31, push: 55, phone: 92, in_person: 100 };
    return Object.values(map).map(ch => ({ ...ch, responseRate: responseRates[ch.channel] || 50 }))
        .sort((a, b) => b.count - a.count);
});

const employeeActivity = computed(() => [
    { name: 'Анна Соколова',     role: 'Стилист-колорист',  interactions: 14, messages: 3,  avgResponseMin: 8 },
    { name: 'Ольга Демидова',    role: 'Мастер маникюра',   interactions: 8,  messages: 2,  avgResponseMin: 12 },
    { name: 'Светлана Романова', role: 'Мастер-косметолог', interactions: 6,  messages: 1,  avgResponseMin: 5 },
    { name: 'Кристина Лебедева', role: 'Бровист',           interactions: 5,  messages: 4,  avgResponseMin: 15 },
    { name: 'Администратор',     role: 'Администратор',     interactions: 12, messages: 8,  avgResponseMin: 3 },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  EVENT DETAIL                                                       */
/* ═══════════════════════════════════════════════════════════════════ */
const showEventDetail = ref(false);
const activeEvent = ref(null);

const relatedEvents = computed(() => {
    if (!activeEvent.value) return [];
    return allEvents.value
        .filter(e => e.id !== activeEvent.value.id && e.clientId === activeEvent.value.clientId && e.date === activeEvent.value.date)
        .slice(0, 5);
});

function openEventDetail(ev) {
    activeEvent.value = ev;
    showEventDetail.value = true;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  ACTIONS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
function openClientFromEvent(ev) {
    if (ev?.clientId) {
        emit('open-client', ev.clientId);
        showEventDetail.value = false;
    }
}
function replyToEvent(ev) {
    emit('reply-message', { clientId: ev.clientId, clientName: ev.clientName, channel: ev.channel });
}
function exportInteractions() {
    const events = filteredEvents.value;
    const header = '\uFEFFДата;Тип;Канал;Клиент;Текст\n';
    const rows = events.map(e => `${e.date || ''};${e.type || ''};${e.channel || ''};${e.clientName || ''};${e.text || ''}`).join('\n');
    const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `interactions_${Date.now()}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
