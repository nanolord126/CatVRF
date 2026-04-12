<template>
<div class="space-y-4">
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- HEADER + VIEW SWITCHER                                         -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <button v-if="activeView !== 'dashboard'" @click="goBack"
                    class="p-1.5 rounded-lg transition" style="color:var(--t-text-2)"
                    title="Назад">←</button>
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">
                {{ activeView === 'dashboard' ? '🤖 Автоматизация маркетинга'
                 : activeView === 'editor' ? '🛠️ Редактор автоматизации'
                 : activeView === 'templates' ? '📋 Шаблоны автоматизаций'
                 : activeView === 'stats' ? '📊 Статистика: ' + (activeAutomation?.name || '')
                 : '🤖 Автоматизация' }}
            </h2>
        </div>
        <div v-if="activeView === 'dashboard'" class="flex items-center gap-2">
            <VButton size="sm" variant="ghost" @click="activeView = 'templates'">📋 Шаблоны</VButton>
            <VButton size="sm" @click="startCreateAutomation">➕ Создать автоматизацию</VButton>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- 1. DASHBOARD                                                    -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'dashboard'">
        <!-- Global stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Отправлено" :value="String(globalStats.totalSent)" icon="📤">
                <template #trend><span class="text-green-400 text-xs">+{{ globalStats.sentThisMonth }} за мес.</span></template>
            </VStatCard>
            <VStatCard title="Open Rate" :value="globalStats.openRate + '%'" icon="📬">
                <template #trend><span :class="globalStats.openRate > 40 ? 'text-green-400' : 'text-yellow-400'" class="text-xs">{{ globalStats.openRate > 40 ? '✓ отлично' : '↗ средне' }}</span></template>
            </VStatCard>
            <VStatCard title="Конверсия в запись" :value="globalStats.conversionRate + '%'" icon="📅">
                <template #trend><span class="text-green-400 text-xs">+{{ globalStats.convDelta }}% vs пред. мес.</span></template>
            </VStatCard>
            <VStatCard title="Выручка от авто" :value="fmtMoney(globalStats.revenue)" icon="💰">
                <template #trend><span class="text-green-400 text-xs">ROI {{ globalStats.roi }}%</span></template>
            </VStatCard>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center gap-2 flex-wrap">
            <button v-for="f in filterTabs" :key="f.key"
                    class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                    :style="activeFilter === f.key
                        ? 'background:var(--t-primary);color:#fff'
                        : 'background:var(--t-surface);color:var(--t-text-2);border:1px solid var(--t-border)'"
                    @click="activeFilter = f.key">
                {{ f.label }}
                <span v-if="f.count != null" class="ml-1 opacity-70">({{ f.count }})</span>
            </button>
        </div>

        <!-- Automations list -->
        <VCard :title="'📋 Автоматизации (' + filteredAutomations.length + ')'">
            <div v-if="filteredAutomations.length" class="space-y-2">
                <div v-for="auto in filteredAutomations" :key="auto.id"
                     class="p-4 rounded-xl border flex items-start gap-4 transition hover:shadow cursor-pointer group"
                     style="background:var(--t-bg);border-color:var(--t-border)"
                     @click="openAutomationStats(auto)">
                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"
                         :style="`background:${categoryColors[auto.category] || 'var(--t-primary-dim)'}`">
                        {{ auto.icon }}
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-semibold truncate" style="color:var(--t-text)">{{ auto.name }}</span>
                            <VBadge :color="statusColors[auto.status]" size="sm">{{ statusLabels[auto.status] }}</VBadge>
                        </div>
                        <div class="text-[11px] mb-2" style="color:var(--t-text-3)">
                            {{ auto.description }}
                        </div>
                        <div class="flex items-center gap-4 text-[10px]" style="color:var(--t-text-2)">
                            <span>📤 {{ auto.stats.sent }}</span>
                            <span>📬 {{ auto.stats.opened }} ({{ auto.stats.sent > 0 ? Math.round(auto.stats.opened / auto.stats.sent * 100) : 0 }}%)</span>
                            <span>📅 {{ auto.stats.conversions }}</span>
                            <span>💰 {{ fmtMoney(auto.stats.revenue) }}</span>
                            <span v-if="auto.channels?.length" class="flex gap-0.5">
                                <span v-for="ch in auto.channels" :key="ch">{{ channelIcons[ch] || ch }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition">
                        <button class="p-1.5 rounded-lg hover:bg-black/5 text-sm" title="Редактировать"
                                @click.stop="editAutomation(auto)">✏️</button>
                        <button class="p-1.5 rounded-lg hover:bg-black/5 text-sm" title="Дублировать"
                                @click.stop="duplicateAutomation(auto)">📋</button>
                        <button class="p-1.5 rounded-lg hover:bg-black/5 text-sm"
                                :title="auto.status === 'active' ? 'Приостановить' : 'Запустить'"
                                @click.stop="toggleAutomation(auto)">
                            {{ auto.status === 'active' ? '⏸️' : '▶️' }}
                        </button>
                        <button class="p-1.5 rounded-lg hover:bg-black/5 text-sm text-red-400" title="Удалить"
                                @click.stop="confirmDeleteAutomation(auto)">🗑️</button>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8">
                <div class="text-3xl mb-2">🤖</div>
                <div class="text-sm" style="color:var(--t-text-3)">Нет автоматизаций в этой категории</div>
                <VButton size="sm" class="mt-3" @click="activeView = 'templates'">Выбрать из шаблонов</VButton>
            </div>
        </VCard>

        <!-- Quick stats chart -->
        <div class="grid md:grid-cols-2 gap-4">
            <!-- Channels breakdown -->
            <VCard title="📊 По каналам">
                <div class="space-y-2">
                    <div v-for="ch in channelBreakdown" :key="ch.channel"
                         class="flex items-center gap-3">
                        <span class="text-lg w-6 text-center">{{ channelIcons[ch.channel] }}</span>
                        <span class="text-xs w-20" style="color:var(--t-text)">{{ channelNames[ch.channel] }}</span>
                        <div class="flex-1 h-5 rounded-full overflow-hidden" style="background:var(--t-surface)">
                            <div class="h-full rounded-full transition-all" style="background:var(--t-primary)"
                                 :style="{ width: ch.pct + '%' }"></div>
                        </div>
                        <span class="text-xs w-12 text-right" style="color:var(--t-text-2)">{{ ch.sent }}</span>
                        <span class="text-[10px] w-10 text-right" style="color:var(--t-text-3)">{{ ch.pct }}%</span>
                    </div>
                </div>
            </VCard>

            <!-- Monthly performance -->
            <VCard title="📈 Динамика за 6 мес.">
                <div class="flex items-end gap-1 h-36">
                    <div v-for="m in monthlyPerformance" :key="m.month" class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-[9px]" style="color:var(--t-text-3)">{{ m.sent }}</span>
                        <div class="w-full rounded-t-md transition-all" style="background:var(--t-primary)"
                             :style="{ height: m.pct + '%', minHeight: '4px' }"></div>
                        <span class="text-[9px]" style="color:var(--t-text-3)">{{ m.month }}</span>
                    </div>
                </div>
            </VCard>
        </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- 2. TEMPLATES GALLERY                                            -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'templates'">
        <!-- Template categories -->
        <div class="flex items-center gap-2 flex-wrap">
            <button v-for="cat in templateCategories" :key="cat.key"
                    class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                    :style="activeTemplateCategory === cat.key
                        ? 'background:var(--t-primary);color:#fff'
                        : 'background:var(--t-surface);color:var(--t-text-2);border:1px solid var(--t-border)'"
                    @click="activeTemplateCategory = cat.key">
                {{ cat.icon }} {{ cat.label }}
            </button>
        </div>

        <!-- Templates grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="tpl in filteredTemplates" :key="tpl.id"
                 class="rounded-xl border p-4 transition hover:shadow cursor-pointer group"
                 style="background:var(--t-surface);border-color:var(--t-border)"
                 @click="useTemplate(tpl)">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl"
                         :style="`background:${categoryColors[tpl.category] || 'var(--t-primary-dim)'}`">
                        {{ tpl.icon }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate" style="color:var(--t-text)">{{ tpl.name }}</div>
                        <VBadge :color="categoryBadgeColors[tpl.category] || 'blue'" size="sm">{{ categoryLabels[tpl.category] }}</VBadge>
                    </div>
                </div>
                <p class="text-xs mb-3" style="color:var(--t-text-3)">{{ tpl.description }}</p>

                <!-- Trigger + Channel preview -->
                <div class="flex items-center gap-2 text-[10px] mb-2" style="color:var(--t-text-2)">
                    <span>⚡ {{ tpl.triggerLabel }}</span>
                    <span>·</span>
                    <span>{{ tpl.defaultChannels.map(c => channelIcons[c]).join(' ') }}</span>
                    <span v-if="tpl.delay">· ⏱️ {{ tpl.delay }}</span>
                </div>

                <!-- Expected results -->
                <div class="flex items-center gap-3 text-[10px] px-2 py-1.5 rounded-lg" style="background:var(--t-bg)">
                    <span style="color:var(--t-text-3)">Ожидаемый Open Rate:</span>
                    <span class="font-medium" style="color:var(--t-primary)">{{ tpl.expectedOpenRate }}%</span>
                    <span style="color:var(--t-text-3)">Конверсия:</span>
                    <span class="font-medium" style="color:var(--t-primary)">{{ tpl.expectedConversion }}%</span>
                </div>

                <VButton size="sm" class="w-full mt-3 opacity-0 group-hover:opacity-100 transition">
                    ➕ Использовать шаблон
                </VButton>
            </div>
        </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- 3. AUTOMATION EDITOR                                            -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'editor'">
        <!-- Editor tabs -->
        <div class="flex items-center gap-1 overflow-x-auto pb-1 border-b" style="border-color:var(--t-border)">
            <button v-for="et in editorTabs" :key="et.key"
                    class="px-3 py-1.5 rounded-t-lg text-xs font-medium whitespace-nowrap transition-colors"
                    :style="activeEditorTab === et.key
                        ? 'background:var(--t-surface);color:var(--t-primary);border-bottom:2px solid var(--t-primary)'
                        : 'color:var(--t-text-2)'"
                    @click="activeEditorTab = et.key">
                {{ et.icon }} {{ et.label }}
            </button>
        </div>

        <!-- 3A. Trigger & Conditions -->
        <div v-if="activeEditorTab === 'trigger'" class="space-y-4">
            <VCard title="⚡ Триггер">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Тип триггера</label>
                        <select v-model="editForm.triggerType"
                                class="w-full px-3 py-2 rounded-lg text-sm border"
                                style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                            <optgroup label="Триггерные кампании">
                                <option value="birthday">🎂 День рождения</option>
                                <option value="first_visit">🏆 После первого визита</option>
                                <option value="after_visit">🔄 После визита</option>
                                <option value="sleeping_30">😴 Спящий клиент (30 дн.)</option>
                                <option value="sleeping_60">😴 Спящий клиент (60 дн.)</option>
                                <option value="sleeping_90">😴 Спящий клиент (90 дн.)</option>
                                <option value="booking_confirm">✅ Подтверждение записи</option>
                                <option value="booking_remind_48h">⏰ Напоминание 48ч</option>
                                <option value="booking_remind_2h">⏰ Напоминание 2ч</option>
                                <option value="cancellation">❌ Отмена записи</option>
                                <option value="course_complete">🎓 Завершение курса</option>
                                <option value="bonus_expiring">💳 Бонусы сгорают</option>
                            </optgroup>
                            <optgroup label="Поведенческие">
                                <option value="viewed_no_book">👀 Просмотр без записи</option>
                                <option value="favorited">❤️ Добавил в избранное</option>
                                <option value="msg_not_opened">📭 Не открыл сообщение</option>
                            </optgroup>
                            <optgroup label="Сезонные / Праздничные">
                                <option value="seasonal">📅 Сезонная кампания</option>
                                <option value="holiday">🎉 Праздничная кампания</option>
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Задержка отправки</label>
                        <div class="flex gap-2">
                            <input v-model.number="editForm.delayValue" type="number" min="0" max="720"
                                   class="w-20 px-3 py-2 rounded-lg text-sm border"
                                   style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)" />
                            <select v-model="editForm.delayUnit"
                                    class="flex-1 px-3 py-2 rounded-lg text-sm border"
                                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                                <option value="minutes">минут</option>
                                <option value="hours">часов</option>
                                <option value="days">дней</option>
                            </select>
                        </div>
                    </div>
                </div>
            </VCard>

            <!-- Conditions -->
            <VCard title="🎯 Условия (фильтры аудитории)">
                <div class="space-y-3">
                    <div v-for="(cond, idx) in editForm.conditions" :key="idx"
                         class="flex items-center gap-2 flex-wrap">
                        <select v-model="cond.field"
                                class="px-2 py-1.5 rounded-lg text-xs border"
                                style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                            <option value="segment">Сегмент</option>
                            <option value="loyalty">Уровень лояльности</option>
                            <option value="age">Возраст</option>
                            <option value="ltv">LTV</option>
                            <option value="visits">Кол-во визитов</option>
                            <option value="favorite_service">Любимая услуга</option>
                            <option value="favorite_master">Любимый мастер</option>
                            <option value="source">Источник</option>
                            <option value="tag">Тег</option>
                        </select>
                        <select v-model="cond.operator"
                                class="px-2 py-1.5 rounded-lg text-xs border"
                                style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                            <option value="eq">=</option>
                            <option value="neq">≠</option>
                            <option value="gt">&gt;</option>
                            <option value="lt">&lt;</option>
                            <option value="gte">≥</option>
                            <option value="lte">≤</option>
                            <option value="contains">содержит</option>
                        </select>
                        <VInput v-model="cond.value" placeholder="Значение" class="flex-1 min-w-[120px]" />
                        <button class="text-red-400 text-sm p-1" @click="removeCondition(idx)">✕</button>
                    </div>
                    <button class="text-xs px-3 py-1.5 rounded-lg border transition"
                            style="color:var(--t-primary);border-color:var(--t-border)"
                            @click="addCondition">
                        ➕ Добавить условие
                    </button>
                </div>
            </VCard>
        </div>

        <!-- 3B. Channels & Messages -->
        <div v-if="activeEditorTab === 'message'" class="space-y-4">
            <VCard title="📡 Каналы отправки">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <label v-for="ch in allChannels" :key="ch.key"
                           class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition"
                           :style="editForm.channels.includes(ch.key)
                               ? 'background:var(--t-primary-dim);border-color:var(--t-primary)'
                               : 'background:var(--t-bg);border-color:var(--t-border)'">
                        <input type="checkbox" :checked="editForm.channels.includes(ch.key)"
                               @change="toggleChannel(ch.key)" class="sr-only" />
                        <span class="text-lg">{{ ch.icon }}</span>
                        <div>
                            <div class="text-xs font-medium" style="color:var(--t-text)">{{ ch.label }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">{{ ch.sublabel }}</div>
                        </div>
                        <div v-if="editForm.channels.includes(ch.key)"
                             class="ml-auto w-4 h-4 rounded-full flex items-center justify-center text-[10px]"
                             style="background:var(--t-primary);color:#fff">✓</div>
                    </label>
                </div>
            </VCard>

            <!-- Message text -->
            <VCard title="✍️ Текст сообщения">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Тема (для Email)</label>
                        <VInput v-model="editForm.subject" placeholder="Тема письма..." />
                    </div>
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Текст сообщения</label>
                        <textarea v-model="editForm.messageText" rows="5"
                                  class="w-full px-3 py-2 rounded-lg text-sm border resize-y"
                                  style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"
                                  placeholder="Привет, {name}! Ваш мастер {master} ждёт вас..."></textarea>
                    </div>

                    <!-- Personalization tokens -->
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-3)">Переменные персонализации:</label>
                        <div class="flex flex-wrap gap-1">
                            <button v-for="token in personalizationTokens" :key="token.key"
                                    class="px-2 py-1 rounded text-[10px] border transition hover:shadow"
                                    style="background:var(--t-surface);color:var(--t-primary);border-color:var(--t-border)"
                                    @click="insertToken(token.key)">
                                {{ token.key }} · {{ token.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-[10px] font-medium mb-1" style="color:var(--t-text-3)">Предпросмотр:</div>
                        <div class="text-sm" style="color:var(--t-text)">{{ previewMessage }}</div>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- 3C. A/B Testing -->
        <div v-if="activeEditorTab === 'ab'" class="space-y-4">
            <VCard title="🔬 A/B Тестирование">
                <div class="space-y-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="editForm.abEnabled" class="w-4 h-4 rounded" style="accent-color:var(--t-primary)" />
                        <span class="text-sm" style="color:var(--t-text)">Включить A/B тест</span>
                    </label>

                    <template v-if="editForm.abEnabled">
                        <div class="grid md:grid-cols-2 gap-4">
                            <!-- Variant A -->
                            <div class="p-4 rounded-xl border" style="border-color:var(--t-primary);background:var(--t-bg)">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                         style="background:var(--t-primary);color:#fff">A</div>
                                    <span class="text-sm font-medium" style="color:var(--t-text)">Вариант A</span>
                                    <span class="text-[10px] ml-auto" style="color:var(--t-text-3)">{{ editForm.abSplit }}%</span>
                                </div>
                                <textarea v-model="editForm.messageText" rows="3"
                                          class="w-full px-3 py-2 rounded-lg text-xs border resize-none"
                                          style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"
                                          placeholder="Текст варианта A..."></textarea>
                            </div>

                            <!-- Variant B -->
                            <div class="p-4 rounded-xl border" style="border-color:var(--t-accent);background:var(--t-bg)">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                                         style="background:var(--t-accent);color:#fff">B</div>
                                    <span class="text-sm font-medium" style="color:var(--t-text)">Вариант B</span>
                                    <span class="text-[10px] ml-auto" style="color:var(--t-text-3)">{{ 100 - editForm.abSplit }}%</span>
                                </div>
                                <textarea v-model="editForm.abVariantB" rows="3"
                                          class="w-full px-3 py-2 rounded-lg text-xs border resize-none"
                                          style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"
                                          placeholder="Текст варианта B..."></textarea>
                            </div>
                        </div>

                        <!-- Split slider -->
                        <div>
                            <label class="text-xs block mb-1" style="color:var(--t-text-2)">Распределение: {{ editForm.abSplit }}% / {{ 100 - editForm.abSplit }}%</label>
                            <input type="range" v-model.number="editForm.abSplit" min="10" max="90" step="5"
                                   class="w-full" style="accent-color:var(--t-primary)" />
                        </div>

                        <!-- Winner criteria -->
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Критерий победителя</label>
                                <select v-model="editForm.abWinnerCriteria"
                                        class="w-full px-3 py-2 rounded-lg text-sm border"
                                        style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                                    <option value="open_rate">Open Rate</option>
                                    <option value="click_rate">Click Rate</option>
                                    <option value="conversion">Конверсия в запись</option>
                                    <option value="revenue">Выручка</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Авто-выбор победителя через</label>
                                <div class="flex gap-2">
                                    <input v-model.number="editForm.abTestDuration" type="number" min="1" max="72"
                                           class="w-20 px-3 py-2 rounded-lg text-sm border"
                                           style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)" />
                                    <span class="text-sm self-center" style="color:var(--t-text-2)">часов</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </VCard>
        </div>

        <!-- 3D. Frequency & Schedule -->
        <div v-if="activeEditorTab === 'schedule'" class="space-y-4">
            <VCard title="🕐 Расписание и лимиты">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Часы отправки</label>
                        <div class="flex items-center gap-2">
                            <input v-model="editForm.sendFromHour" type="time"
                                   class="px-3 py-2 rounded-lg text-sm border"
                                   style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)" />
                            <span class="text-xs" style="color:var(--t-text-3)">—</span>
                            <input v-model="editForm.sendToHour" type="time"
                                   class="px-3 py-2 rounded-lg text-sm border"
                                   style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Дни отправки</label>
                        <div class="flex gap-1 flex-wrap">
                            <button v-for="d in weekDays" :key="d.key"
                                    class="w-8 h-8 rounded-lg text-[10px] font-medium transition"
                                    :style="editForm.sendDays.includes(d.key)
                                        ? 'background:var(--t-primary);color:#fff'
                                        : 'background:var(--t-surface);color:var(--t-text-2);border:1px solid var(--t-border)'"
                                    @click="toggleDay(d.key)">
                                {{ d.short }}
                            </button>
                        </div>
                    </div>
                </div>
            </VCard>

            <VCard title="🚦 Частотные лимиты">
                <div class="space-y-4">
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Макс. сообщений клиенту за</label>
                            <div class="flex gap-2">
                                <input v-model.number="editForm.freqLimitCount" type="number" min="1" max="10"
                                       class="w-16 px-3 py-2 rounded-lg text-sm border"
                                       style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)" />
                                <span class="text-xs self-center" style="color:var(--t-text-2)">шт. за</span>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Период</label>
                            <select v-model="editForm.freqLimitPeriod"
                                    class="w-full px-3 py-2 rounded-lg text-sm border"
                                    style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                                <option value="day">день</option>
                                <option value="3days">3 дня</option>
                                <option value="week">неделю</option>
                                <option value="month">месяц</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Тихий период между</label>
                            <div class="flex gap-2">
                                <input v-model.number="editForm.quietPeriodHours" type="number" min="0" max="168"
                                       class="w-16 px-3 py-2 rounded-lg text-sm border"
                                       style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)" />
                                <span class="text-xs self-center" style="color:var(--t-text-2)">часов</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quiet hours note -->
                    <div class="p-3 rounded-lg flex items-start gap-2" style="background:var(--t-primary-dim)">
                        <span class="text-sm">💡</span>
                        <div class="text-xs" style="color:var(--t-text)">
                            Рекомендация: не более 1 сообщения клиенту за 3 дня. Отправка только с 09:00 до 21:00.
                            Частое сообщение повышает отписку и снижает лояльность.
                        </div>
                    </div>
                </div>
            </VCard>

            <!-- Automation name & status -->
            <VCard title="📝 Общие настройки">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Название автоматизации</label>
                        <VInput v-model="editForm.name" placeholder="Напр.: Возврат спящих клиентов" />
                    </div>
                    <div>
                        <label class="text-xs font-medium block mb-1" style="color:var(--t-text-2)">Категория</label>
                        <select v-model="editForm.category"
                                class="w-full px-3 py-2 rounded-lg text-sm border"
                                style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                            <option value="trigger">Триггерная</option>
                            <option value="behavioral">Поведенческая</option>
                            <option value="seasonal">Сезонная</option>
                            <option value="holiday">Праздничная</option>
                        </select>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- 3E. Visual Flow -->
        <div v-if="activeEditorTab === 'flow'" class="space-y-4">
            <VCard title="🔀 Визуальный конструктор">
                <div class="space-y-1">
                    <!-- Flow steps -->
                    <div v-for="(step, idx) in editForm.flowSteps" :key="idx"
                         class="flex items-start gap-3">
                        <!-- Connector line -->
                        <div class="flex flex-col items-center w-8 shrink-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                 :style="stepStyles[step.type]">
                                {{ step.type === 'trigger' ? '⚡' : step.type === 'condition' ? '🔀' : step.type === 'action' ? '📤' : '⏱️' }}
                            </div>
                            <div v-if="idx < editForm.flowSteps.length - 1" class="w-0.5 h-6" style="background:var(--t-border)"></div>
                        </div>

                        <!-- Step card -->
                        <div class="flex-1 p-3 rounded-lg border mb-1"
                             style="background:var(--t-bg);border-color:var(--t-border)">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium" style="color:var(--t-text)">
                                    {{ stepTypeLabels[step.type] }}
                                </span>
                                <button v-if="step.type !== 'trigger'" class="text-red-400 text-[10px]"
                                        @click="removeFlowStep(idx)">✕</button>
                            </div>
                            <div class="text-[11px]" style="color:var(--t-text-3)">{{ step.label }}</div>

                            <!-- Branch for conditions -->
                            <div v-if="step.type === 'condition'" class="mt-2 grid grid-cols-2 gap-2">
                                <div class="p-2 rounded border text-center text-[10px]"
                                     style="border-color:var(--t-primary);color:var(--t-primary)">
                                    ✓ Да → {{ step.yesAction || 'Отправить' }}
                                </div>
                                <div class="p-2 rounded border text-center text-[10px]"
                                     style="border-color:var(--t-border);color:var(--t-text-3)">
                                    ✕ Нет → {{ step.noAction || 'Пропустить' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add step buttons -->
                    <div class="flex items-center gap-2 pt-2 pl-11">
                        <button class="px-3 py-1.5 rounded-lg border text-[10px] transition hover:shadow"
                                style="color:var(--t-text-2);border-color:var(--t-border)"
                                @click="addFlowStep('condition')">
                            ➕ Условие (если…)
                        </button>
                        <button class="px-3 py-1.5 rounded-lg border text-[10px] transition hover:shadow"
                                style="color:var(--t-text-2);border-color:var(--t-border)"
                                @click="addFlowStep('action')">
                            ➕ Действие (отправить)
                        </button>
                        <button class="px-3 py-1.5 rounded-lg border text-[10px] transition hover:shadow"
                                style="color:var(--t-text-2);border-color:var(--t-border)"
                                @click="addFlowStep('wait')">
                            ➕ Задержка (ждать)
                        </button>
                    </div>
                </div>
            </VCard>

            <!-- Flow example / help -->
            <VCard title="💡 Пример цепочки">
                <div class="text-xs space-y-1" style="color:var(--t-text-3)">
                    <p>⚡ <strong>Триггер:</strong> Клиент не был 60 дней</p>
                    <p>↓ 🔀 <strong>Условие:</strong> LTV &gt; 10 000 ₽?</p>
                    <p>&nbsp;&nbsp;↓ ✓ <strong>Да:</strong> Отправить WhatsApp «Дарим скидку 20%»</p>
                    <p>&nbsp;&nbsp;↓ ✕ <strong>Нет:</strong> Отправить SMS «Скучаем! Скидка 10%»</p>
                    <p>↓ ⏱️ <strong>Ждать:</strong> 3 дня</p>
                    <p>↓ 🔀 <strong>Условие:</strong> Записался?</p>
                    <p>&nbsp;&nbsp;↓ ✕ <strong>Нет:</strong> Отправить Push «Последний шанс!»</p>
                </div>
            </VCard>
        </div>

        <!-- Editor footer -->
        <div class="flex items-center justify-between pt-2 border-t" style="border-color:var(--t-border)">
            <VButton variant="ghost" size="sm" @click="activeView = 'dashboard'">Отмена</VButton>
            <div class="flex gap-2">
                <VButton variant="ghost" size="sm" @click="saveAutomation('draft')">💾 Сохранить черновик</VButton>
                <VButton size="sm" @click="saveAutomation('active')">▶️ Запустить</VButton>
            </div>
        </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- 4. AUTOMATION STATS DETAIL                                      -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <template v-if="activeView === 'stats' && activeAutomation">
        <!-- Stats header cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Сработал" :value="String(activeAutomation.stats.triggered)" icon="⚡" />
            <VStatCard title="Отправлено" :value="String(activeAutomation.stats.sent)" icon="📤" />
            <VStatCard title="Открыто" :value="activeAutomation.stats.sent > 0 ? Math.round(activeAutomation.stats.opened / activeAutomation.stats.sent * 100) + '%' : '—'" icon="📬">
                <template #trend><span class="text-xs" :class="activeAutomation.stats.opened / (activeAutomation.stats.sent || 1) > 0.4 ? 'text-green-400' : 'text-yellow-400'">
                    {{ activeAutomation.stats.opened }} из {{ activeAutomation.stats.sent }}
                </span></template>
            </VStatCard>
            <VStatCard title="Выручка" :value="fmtMoney(activeAutomation.stats.revenue)" icon="💰">
                <template #trend><span class="text-green-400 text-xs">ROI {{ roiPercent }}%</span></template>
            </VStatCard>
        </div>

        <!-- Funnel -->
        <VCard title="📊 Воронка">
            <div class="space-y-2">
                <div v-for="step in funnelSteps" :key="step.label"
                     class="flex items-center gap-3">
                    <span class="text-sm w-6 text-center">{{ step.icon }}</span>
                    <span class="text-xs w-28" style="color:var(--t-text)">{{ step.label }}</span>
                    <div class="flex-1 h-6 rounded-full overflow-hidden" style="background:var(--t-surface)">
                        <div class="h-full rounded-full transition-all flex items-center justify-end pr-2"
                             :style="{ width: step.pct + '%', background: step.color }">
                            <span v-if="step.pct > 15" class="text-[10px] text-white font-medium">{{ step.value }}</span>
                        </div>
                    </div>
                    <span class="text-xs w-12 text-right font-medium" :style="{ color: step.color }">{{ step.pct }}%</span>
                </div>
            </div>
        </VCard>

        <!-- A/B Results (if applicable) -->
        <VCard v-if="activeAutomation.abEnabled" title="🔬 Результаты A/B теста">
            <div class="grid md:grid-cols-2 gap-4">
                <div v-for="variant in abResults" :key="variant.name"
                     class="p-4 rounded-xl border"
                     :style="variant.winner ? 'border-color:var(--t-primary);background:var(--t-primary-dim)' : 'border-color:var(--t-border);background:var(--t-bg)'">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                             :style="variant.winner ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-border);color:var(--t-text-2)'">
                            {{ variant.name }}
                        </div>
                        <span class="text-sm font-medium" style="color:var(--t-text)">Вариант {{ variant.name }}</span>
                        <VBadge v-if="variant.winner" color="green" size="sm">🏆 Победитель</VBadge>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="p-2 rounded-lg" style="background:var(--t-surface)">
                            <div style="color:var(--t-text-3)">Open Rate</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ variant.openRate }}%</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-surface)">
                            <div style="color:var(--t-text-3)">Конверсия</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ variant.conversion }}%</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-surface)">
                            <div style="color:var(--t-text-3)">Отправлено</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ variant.sent }}</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-surface)">
                            <div style="color:var(--t-text-3)">Выручка</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(variant.revenue) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Per-channel stats -->
        <VCard title="📡 По каналам">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr style="color:var(--t-text-3)">
                            <th class="text-left py-2 px-2">Канал</th>
                            <th class="text-right py-2 px-2">Отправлено</th>
                            <th class="text-right py-2 px-2">Доставлено</th>
                            <th class="text-right py-2 px-2">Открыто</th>
                            <th class="text-right py-2 px-2">Клики</th>
                            <th class="text-right py-2 px-2">Конверсия</th>
                            <th class="text-right py-2 px-2">Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ch in activeAutomation.channelStats" :key="ch.channel"
                            class="border-t" style="border-color:var(--t-border)">
                            <td class="py-2 px-2" style="color:var(--t-text)">
                                {{ channelIcons[ch.channel] }} {{ channelNames[ch.channel] }}
                            </td>
                            <td class="text-right py-2 px-2" style="color:var(--t-text-2)">{{ ch.sent }}</td>
                            <td class="text-right py-2 px-2" style="color:var(--t-text-2)">{{ ch.delivered }}</td>
                            <td class="text-right py-2 px-2" style="color:var(--t-primary)">{{ ch.opened }} ({{ ch.sent > 0 ? Math.round(ch.opened/ch.sent*100) : 0 }}%)</td>
                            <td class="text-right py-2 px-2" style="color:var(--t-text-2)">{{ ch.clicks }}</td>
                            <td class="text-right py-2 px-2 font-medium" style="color:var(--t-primary)">{{ ch.conversions }}</td>
                            <td class="text-right py-2 px-2 font-medium" style="color:var(--t-text)">{{ fmtMoney(ch.revenue) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>

        <!-- Recent activity log -->
        <VCard title="📜 Последние срабатывания">
            <div class="space-y-1 max-h-64 overflow-y-auto">
                <div v-for="log in recentLogs" :key="log.id"
                     class="flex items-center gap-3 py-2 px-2 rounded-lg text-xs hover:bg-black/5"
                     style="color:var(--t-text-2)">
                    <span class="text-sm">{{ channelIcons[log.channel] }}</span>
                    <span class="font-medium" style="color:var(--t-text)">{{ log.clientName }}</span>
                    <VBadge :color="log.status === 'opened' ? 'green' : log.status === 'delivered' ? 'blue' : log.status === 'converted' ? 'purple' : 'gray'" size="sm">
                        {{ logStatusLabels[log.status] }}
                    </VBadge>
                    <span class="ml-auto text-[10px]" style="color:var(--t-text-3)">{{ log.time }}</span>
                </div>
            </div>
        </VCard>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            <VButton variant="ghost" size="sm" @click="editAutomation(activeAutomation)">✏️ Редактировать</VButton>
            <VButton variant="ghost" size="sm" @click="duplicateAutomation(activeAutomation)">📋 Дублировать</VButton>
            <VButton variant="ghost" size="sm"
                     @click="toggleAutomation(activeAutomation)">
                {{ activeAutomation.status === 'active' ? '⏸️ Приостановить' : '▶️ Запустить' }}
            </VButton>
        </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- DELETE CONFIRMATION MODAL                                       -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <VModal :show="showDeleteModal" title="🗑️ Удалить автоматизацию?" @close="showDeleteModal = false">
        <p class="text-sm mb-4" style="color:var(--t-text)">
            Вы уверены, что хотите удалить «{{ deleteTarget?.name }}»?
            Статистика будет потеряна.
        </p>
        <div class="flex justify-end gap-2">
            <VButton variant="ghost" size="sm" @click="showDeleteModal = false">Отмена</VButton>
            <VButton size="sm" class="!bg-red-500 hover:!bg-red-600" @click="deleteAutomation">Удалить</VButton>
        </div>
    </VModal>
</div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VStatCard from '../../UI/VStatCard.vue';

/* ═══════════════════════════════════════════════════════════════════ */
/*  PROPS & EMITS                                                      */
/* ═══════════════════════════════════════════════════════════════════ */
const props = defineProps({
    clients:  { type: Array, default: () => [] },
    masters:  { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
});

const emit = defineEmits(['open-client', 'send-message', 'create-campaign']);

/* ═══════════════════════════════════════════════════════════════════ */
/*  VIEWS & NAVIGATION                                                 */
/* ═══════════════════════════════════════════════════════════════════ */
const activeView = ref('dashboard');
const activeAutomation = ref(null);
const activeFilter = ref('all');
const activeTemplateCategory = ref('all');
const activeEditorTab = ref('trigger');

function goBack() {
    if (activeView.value === 'stats') { activeView.value = 'dashboard'; activeAutomation.value = null; }
    else if (activeView.value === 'editor') { activeView.value = 'dashboard'; }
    else if (activeView.value === 'templates') { activeView.value = 'dashboard'; }
    else { activeView.value = 'dashboard'; }
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  CONSTANTS                                                          */
/* ═══════════════════════════════════════════════════════════════════ */
const statusColors = { active: 'green', paused: 'yellow', completed: 'blue', draft: 'gray' };
const statusLabels = { active: 'Работает', paused: 'Приостановлена', completed: 'Завершена', draft: 'Черновик' };

const categoryColors = {
    trigger:    'var(--t-primary-dim)',
    behavioral: '#fef3c7',
    seasonal:   '#dbeafe',
    holiday:    '#fce7f3',
};
const categoryLabels   = { trigger: 'Триггерная', behavioral: 'Поведенческая', seasonal: 'Сезонная', holiday: 'Праздничная' };
const categoryBadgeColors = { trigger: 'purple', behavioral: 'yellow', seasonal: 'blue', holiday: 'pink' };

const channelIcons = { sms: '📱', push: '🔔', whatsapp: '💬', telegram: '✈️', email: '📧', chat: '💭' };
const channelNames = { sms: 'SMS', push: 'Push', whatsapp: 'WhatsApp', telegram: 'Telegram', email: 'Email', chat: 'Чат' };

const allChannels = [
    { key: 'sms',      icon: '📱', label: 'SMS',       sublabel: '~2.5 ₽ / сообщение' },
    { key: 'push',     icon: '🔔', label: 'Push',      sublabel: 'Бесплатно' },
    { key: 'whatsapp', icon: '💬', label: 'WhatsApp',  sublabel: '~4 ₽ / сообщение' },
    { key: 'telegram', icon: '✈️', label: 'Telegram',  sublabel: 'Бесплатно' },
    { key: 'email',    icon: '📧', label: 'Email',     sublabel: '~0.5 ₽ / письмо' },
    { key: 'chat',     icon: '💭', label: 'In-App Чат', sublabel: 'Бесплатно' },
];

const personalizationTokens = [
    { key: '{name}',    label: 'Имя клиента' },
    { key: '{master}',  label: 'Мастер' },
    { key: '{service}', label: 'Услуга' },
    { key: '{bonus}',   label: 'Бонусы' },
    { key: '{date}',    label: 'Дата записи' },
    { key: '{salon}',   label: 'Салон' },
    { key: '{discount}', label: 'Скидка' },
    { key: '{days}',    label: 'Кол-во дней' },
];

const weekDays = [
    { key: 'mon', short: 'Пн' }, { key: 'tue', short: 'Вт' }, { key: 'wed', short: 'Ср' },
    { key: 'thu', short: 'Чт' }, { key: 'fri', short: 'Пт' }, { key: 'sat', short: 'Сб' },
    { key: 'sun', short: 'Вс' },
];

const stepStyles = {
    trigger:   'background:var(--t-primary);color:#fff',
    condition: 'background:#fef3c7;color:#92400e',
    action:    'background:#dbeafe;color:#1d4ed8',
    wait:      'background:var(--t-surface);color:var(--t-text-2)',
};
const stepTypeLabels = { trigger: 'Триггер', condition: 'Условие (если…)', action: 'Действие', wait: 'Задержка' };
const logStatusLabels = { sent: 'Отправлено', delivered: 'Доставлено', opened: 'Открыто', clicked: 'Клик', converted: 'Записался', failed: 'Ошибка' };

const editorTabs = [
    { key: 'trigger',  icon: '⚡', label: 'Триггер' },
    { key: 'message',  icon: '✍️', label: 'Сообщение' },
    { key: 'flow',     icon: '🔀', label: 'Цепочка' },
    { key: 'ab',       icon: '🔬', label: 'A/B тест' },
    { key: 'schedule', icon: '🕐', label: 'Расписание' },
];

const templateCategories = [
    { key: 'all',        icon: '📋', label: 'Все' },
    { key: 'trigger',    icon: '⚡', label: 'Триггерные' },
    { key: 'behavioral', icon: '👀', label: 'Поведенческие' },
    { key: 'seasonal',   icon: '📅', label: 'Сезонные' },
    { key: 'holiday',    icon: '🎉', label: 'Праздничные' },
];

function fmtMoney(v) {
    if (v == null) return '0 ₽';
    return Number(v).toLocaleString('ru-RU') + ' ₽';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  AUTOMATIONS DATA                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const automations = ref([
    {
        id: 1, icon: '🎂', name: 'День рождения — поздравление',
        description: 'За 3 дня: напоминание мастерам. В сам день: поздравление + скидка 15%.',
        category: 'trigger', status: 'active', channels: ['whatsapp', 'push'],
        abEnabled: false,
        stats: { triggered: 24, sent: 48, opened: 41, clicks: 18, conversions: 12, revenue: 62400, cost: 192 },
        channelStats: [
            { channel: 'whatsapp', sent: 24, delivered: 23, opened: 21, clicks: 10, conversions: 8, revenue: 41600 },
            { channel: 'push',     sent: 24, delivered: 24, opened: 20, clicks: 8,  conversions: 4, revenue: 20800 },
        ],
    },
    {
        id: 2, icon: '🏆', name: 'Первый визит — благодарность',
        description: 'Через 2 часа после первого визита: «Спасибо! 500 бонусов на след. визит».',
        category: 'trigger', status: 'active', channels: ['sms', 'push'],
        abEnabled: false,
        stats: { triggered: 38, sent: 76, opened: 58, clicks: 22, conversions: 15, revenue: 45000, cost: 190 },
        channelStats: [
            { channel: 'sms',  sent: 38, delivered: 37, opened: 30, clicks: 12, conversions: 9,  revenue: 27000 },
            { channel: 'push', sent: 38, delivered: 38, opened: 28, clicks: 10, conversions: 6,  revenue: 18000 },
        ],
    },
    {
        id: 3, icon: '😴', name: 'Спящий клиент — 60 дней',
        description: 'Не были 60 дней: WhatsApp + промокод 20%. Через 3 дня: повтор Push.',
        category: 'trigger', status: 'active', channels: ['whatsapp', 'push'],
        abEnabled: true,
        stats: { triggered: 12, sent: 24, opened: 15, clicks: 8, conversions: 5, revenue: 32500, cost: 96 },
        channelStats: [
            { channel: 'whatsapp', sent: 12, delivered: 11, opened: 9,  clicks: 5, conversions: 3, revenue: 19500 },
            { channel: 'push',     sent: 12, delivered: 12, opened: 6,  clicks: 3, conversions: 2, revenue: 13000 },
        ],
    },
    {
        id: 4, icon: '✅', name: 'Подтверждение записи',
        description: 'Сразу: подтверждение. За 48ч: напоминание. За 2ч: финальное напоминание.',
        category: 'trigger', status: 'active', channels: ['sms', 'whatsapp', 'push'],
        abEnabled: false,
        stats: { triggered: 156, sent: 468, opened: 410, clicks: 0, conversions: 0, revenue: 0, cost: 1170 },
        channelStats: [
            { channel: 'sms',      sent: 156, delivered: 152, opened: 140, clicks: 0, conversions: 0, revenue: 0 },
            { channel: 'whatsapp', sent: 156, delivered: 155, opened: 148, clicks: 0, conversions: 0, revenue: 0 },
            { channel: 'push',     sent: 156, delivered: 156, opened: 122, clicks: 0, conversions: 0, revenue: 0 },
        ],
    },
    {
        id: 5, icon: '❌', name: 'Отмена записи — перезапись',
        description: 'Сразу после отмены: «Жаль! Хотите выбрать другое время?» + ссылка.',
        category: 'trigger', status: 'active', channels: ['whatsapp'],
        abEnabled: false,
        stats: { triggered: 8, sent: 8, opened: 7, clicks: 5, conversions: 3, revenue: 12600, cost: 32 },
        channelStats: [
            { channel: 'whatsapp', sent: 8, delivered: 8, opened: 7, clicks: 5, conversions: 3, revenue: 12600 },
        ],
    },
    {
        id: 6, icon: '💳', name: 'Бонусы сгорают — 7 дней',
        description: 'За 7 дней до сгорания бонусов: напоминание + рекомендация услуги.',
        category: 'trigger', status: 'active', channels: ['push', 'email'],
        abEnabled: false,
        stats: { triggered: 18, sent: 36, opened: 28, clicks: 14, conversions: 9, revenue: 38700, cost: 18 },
        channelStats: [
            { channel: 'push',  sent: 18, delivered: 18, opened: 16, clicks: 8,  conversions: 5, revenue: 21500 },
            { channel: 'email', sent: 18, delivered: 17, opened: 12, clicks: 6,  conversions: 4, revenue: 17200 },
        ],
    },
    {
        id: 7, icon: '👀', name: 'Просмотр без записи — 3 дня',
        description: 'Просмотрел услугу 2+ раз, не записался → персональное предложение.',
        category: 'behavioral', status: 'active', channels: ['push', 'telegram'],
        abEnabled: true,
        stats: { triggered: 42, sent: 84, opened: 52, clicks: 28, conversions: 11, revenue: 33000, cost: 0 },
        channelStats: [
            { channel: 'push',     sent: 42, delivered: 42, opened: 30, clicks: 16, conversions: 7,  revenue: 21000 },
            { channel: 'telegram', sent: 42, delivered: 40, opened: 22, clicks: 12, conversions: 4,  revenue: 12000 },
        ],
    },
    {
        id: 8, icon: '❤️', name: 'Избранное — персональная скидка',
        description: 'Добавил в избранное, не записался за 5 дней → скидка 10% на эту услугу.',
        category: 'behavioral', status: 'paused', channels: ['whatsapp'],
        abEnabled: false,
        stats: { triggered: 15, sent: 15, opened: 12, clicks: 8, conversions: 4, revenue: 16800, cost: 60 },
        channelStats: [
            { channel: 'whatsapp', sent: 15, delivered: 14, opened: 12, clicks: 8, conversions: 4, revenue: 16800 },
        ],
    },
    {
        id: 9, icon: '📭', name: 'Не открыл — повтор другим текстом',
        description: 'Не открыл сообщение 24ч → пересылка с другой темой/текстом.',
        category: 'behavioral', status: 'active', channels: ['email', 'push'],
        abEnabled: false,
        stats: { triggered: 31, sent: 62, opened: 38, clicks: 15, conversions: 6, revenue: 19800, cost: 31 },
        channelStats: [
            { channel: 'email', sent: 31, delivered: 30, opened: 22, clicks: 10, conversions: 4, revenue: 13200 },
            { channel: 'push',  sent: 31, delivered: 31, opened: 16, clicks: 5,  conversions: 2, revenue: 6600 },
        ],
    },
    {
        id: 10, icon: '🌸', name: '8 Марта — спецпредложение',
        description: 'Подарочные наборы и услуги со скидкой 20% с 1 по 10 марта.',
        category: 'holiday', status: 'completed', channels: ['sms', 'push', 'email', 'whatsapp'],
        abEnabled: true,
        stats: { triggered: 120, sent: 480, opened: 312, clicks: 145, conversions: 68, revenue: 340000, cost: 1920 },
        channelStats: [
            { channel: 'sms',      sent: 120, delivered: 118, opened: 85,  clicks: 40, conversions: 20, revenue: 100000 },
            { channel: 'push',     sent: 120, delivered: 120, opened: 92,  clicks: 38, conversions: 18, revenue: 90000 },
            { channel: 'email',    sent: 120, delivered: 115, opened: 70,  clicks: 35, conversions: 15, revenue: 75000 },
            { channel: 'whatsapp', sent: 120, delivered: 119, opened: 65,  clicks: 32, conversions: 15, revenue: 75000 },
        ],
    },
    {
        id: 11, icon: '🎄', name: 'Новый год — подарочные сертификаты',
        description: 'Предложение подарочных сертификатов и праздничных комплексов с 15 декабря.',
        category: 'holiday', status: 'draft', channels: ['email', 'push'],
        abEnabled: false,
        stats: { triggered: 0, sent: 0, opened: 0, clicks: 0, conversions: 0, revenue: 0, cost: 0 },
        channelStats: [],
    },
    {
        id: 12, icon: '☀️', name: 'Летний уход — сезон',
        description: 'Спецпредложения на защиту от солнца, увлажнение, лёгкие стрижки.',
        category: 'seasonal', status: 'draft', channels: ['push', 'email'],
        abEnabled: false,
        stats: { triggered: 0, sent: 0, opened: 0, clicks: 0, conversions: 0, revenue: 0, cost: 0 },
        channelStats: [],
    },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  TEMPLATES DATA                                                     */
/* ═══════════════════════════════════════════════════════════════════ */
const automationTemplates = ref([
    { id: 't1', icon: '🎂', name: 'День рождения',          category: 'trigger',    triggerLabel: 'За 3 дня до ДР', delay: '3 дня',   defaultChannels: ['whatsapp', 'push'], expectedOpenRate: 78, expectedConversion: 35, description: 'Поздравление за 3 дня + в сам день. Персональная скидка 15%. Напоминание мастерам.' },
    { id: 't2', icon: '🏆', name: 'Первый визит',           category: 'trigger',    triggerLabel: 'После первого визита', delay: '2 часа', defaultChannels: ['sms', 'push'], expectedOpenRate: 82, expectedConversion: 40, description: 'Благодарность через 2 часа + 500 бонусов на следующий визит.' },
    { id: 't3', icon: '🔄', name: 'После каждого визита',   category: 'trigger',    triggerLabel: 'После визита',   delay: '24 часа',  defaultChannels: ['push'],         expectedOpenRate: 65, expectedConversion: 20, description: 'Благодарность + запрос отзыва + рекомендация следующей услуги.' },
    { id: 't4', icon: '😴', name: 'Спящий клиент 60 дн.',   category: 'trigger',    triggerLabel: '60 дней без визита', delay: null,   defaultChannels: ['whatsapp', 'push'], expectedOpenRate: 55, expectedConversion: 25, description: 'Персональное предложение + промокод 20%. Повтор через 3 дня если не открыл.' },
    { id: 't5', icon: '😴', name: 'Спящий клиент 90 дн.',   category: 'trigger',    triggerLabel: '90 дней без визита', delay: null,   defaultChannels: ['sms', 'whatsapp'], expectedOpenRate: 42, expectedConversion: 15, description: 'Агрессивное возвращение: скидка 30% + ограничение по времени.' },
    { id: 't6', icon: '✅', name: 'Подтверждение записи',   category: 'trigger',    triggerLabel: 'При создании записи', delay: 'Сразу', defaultChannels: ['sms', 'whatsapp', 'push'], expectedOpenRate: 92, expectedConversion: 0, description: 'Сразу: подтверждение. 48ч: напоминание. 2ч: финальное.' },
    { id: 't7', icon: '❌', name: 'Отмена записи',          category: 'trigger',    triggerLabel: 'При отмене',     delay: 'Сразу',    defaultChannels: ['whatsapp'],     expectedOpenRate: 75, expectedConversion: 38, description: 'Предложение перезаписаться на другое время/к другому мастеру.' },
    { id: 't8', icon: '🎓', name: 'Завершение курса',       category: 'trigger',    triggerLabel: 'Последняя процедура курса', delay: '2 часа', defaultChannels: ['whatsapp', 'email'], expectedOpenRate: 70, expectedConversion: 45, description: 'Поздравление + результаты + предложение поддерживающей процедуры.' },
    { id: 't9', icon: '💳', name: 'Бонусы сгорают',         category: 'trigger',    triggerLabel: 'За 7 дней до сгорания', delay: null, defaultChannels: ['push', 'email'], expectedOpenRate: 68, expectedConversion: 50, description: 'Напоминание о сгорающих бонусах + рекомендация услуги.' },
    { id: 't10', icon: '👀', name: 'Просмотр без записи',   category: 'behavioral', triggerLabel: '2+ просмотра, нет записи', delay: '3 дня', defaultChannels: ['push', 'telegram'], expectedOpenRate: 48, expectedConversion: 18, description: 'Персональное предложение по просмотренной услуге.' },
    { id: 't11', icon: '❤️', name: 'Избранное',             category: 'behavioral', triggerLabel: 'Добавление в избранное', delay: '5 дней', defaultChannels: ['whatsapp'], expectedOpenRate: 62, expectedConversion: 28, description: 'Скидка 10% на добавленную в избранное услугу.' },
    { id: 't12', icon: '📭', name: 'Не открыл сообщение',   category: 'behavioral', triggerLabel: 'Не открыто 24ч', delay: '24 часа',  defaultChannels: ['email', 'push'], expectedOpenRate: 35, expectedConversion: 12, description: 'Повторная отправка с изменённой темой и текстом.' },
    { id: 't13', icon: '🌸', name: '8 Марта',               category: 'holiday',    triggerLabel: '1–10 марта',     delay: null,       defaultChannels: ['sms', 'push', 'email', 'whatsapp'], expectedOpenRate: 72, expectedConversion: 30, description: 'Подарочные наборы и праздничные скидки.' },
    { id: 't14', icon: '🎄', name: 'Новый год',             category: 'holiday',    triggerLabel: '15–31 декабря',  delay: null,       defaultChannels: ['email', 'push'], expectedOpenRate: 68, expectedConversion: 28, description: 'Подарочные сертификаты, праздничные комплексы.' },
    { id: 't15', icon: '💪', name: '23 Февраля',            category: 'holiday',    triggerLabel: '20–23 февраля',  delay: null,       defaultChannels: ['push', 'sms'], expectedOpenRate: 58, expectedConversion: 22, description: 'Мужские услуги и подарки.' },
    { id: 't16', icon: '🏷️', name: 'Black Friday',          category: 'holiday',    triggerLabel: 'Последняя пт ноября', delay: null,  defaultChannels: ['sms', 'push', 'email', 'whatsapp'], expectedOpenRate: 74, expectedConversion: 35, description: 'Максимальные скидки на все услуги.' },
    { id: 't17', icon: '☀️', name: 'Летний уход',           category: 'seasonal',   triggerLabel: 'Июнь–август',    delay: null,       defaultChannels: ['push', 'email'], expectedOpenRate: 52, expectedConversion: 18, description: 'Защита от солнца, увлажнение, лёгкие стрижки.' },
    { id: 't18', icon: '🍂', name: 'Осенний уход',          category: 'seasonal',   triggerLabel: 'Сентябрь–ноябрь', delay: null,      defaultChannels: ['push', 'email'], expectedOpenRate: 55, expectedConversion: 20, description: 'Восстановление после лета, глубокое увлажнение, окрашивание.' },
]);

/* ═══════════════════════════════════════════════════════════════════ */
/*  COMPUTED                                                           */
/* ═══════════════════════════════════════════════════════════════════ */
const globalStats = computed(() => {
    const active = automations.value.filter(a => a.status !== 'draft');
    const totalSent = active.reduce((s, a) => s + a.stats.sent, 0);
    const totalOpened = active.reduce((s, a) => s + a.stats.opened, 0);
    const totalConversions = active.reduce((s, a) => s + a.stats.conversions, 0);
    const totalRevenue = active.reduce((s, a) => s + a.stats.revenue, 0);
    const totalCost = active.reduce((s, a) => s + (a.stats.cost || 0), 0);
    return {
        totalSent,
        sentThisMonth: 286,
        openRate: totalSent > 0 ? Math.round(totalOpened / totalSent * 100) : 0,
        conversionRate: totalSent > 0 ? Math.round(totalConversions / totalSent * 100) : 0,
        convDelta: 3.2,
        revenue: totalRevenue,
        roi: totalCost > 0 ? Math.round((totalRevenue - totalCost) / totalCost * 100) : 0,
    };
});

const filterTabs = computed(() => {
    const counts = { all: automations.value.length };
    for (const a of automations.value) {
        counts[a.status] = (counts[a.status] || 0) + 1;
        counts[a.category] = (counts[a.category] || 0) + 1;
    }
    return [
        { key: 'all',        label: 'Все',            count: counts.all },
        { key: 'active',     label: 'Работают',       count: counts.active || 0 },
        { key: 'paused',     label: 'На паузе',       count: counts.paused || 0 },
        { key: 'completed',  label: 'Завершённые',    count: counts.completed || 0 },
        { key: 'draft',      label: 'Черновики',      count: counts.draft || 0 },
        { key: 'trigger',    label: '⚡ Триггерные',   count: counts.trigger || 0 },
        { key: 'behavioral', label: '👀 Поведенческие', count: counts.behavioral || 0 },
        { key: 'seasonal',   label: '📅 Сезонные',    count: counts.seasonal || 0 },
        { key: 'holiday',    label: '🎉 Праздничные', count: counts.holiday || 0 },
    ];
});

const filteredAutomations = computed(() => {
    if (activeFilter.value === 'all') return automations.value;
    return automations.value.filter(a =>
        a.status === activeFilter.value || a.category === activeFilter.value
    );
});

const filteredTemplates = computed(() => {
    if (activeTemplateCategory.value === 'all') return automationTemplates.value;
    return automationTemplates.value.filter(t => t.category === activeTemplateCategory.value);
});

const channelBreakdown = computed(() => {
    const totals = {};
    let grandTotal = 0;
    for (const a of automations.value) {
        for (const cs of (a.channelStats || [])) {
            totals[cs.channel] = (totals[cs.channel] || 0) + cs.sent;
            grandTotal += cs.sent;
        }
    }
    return Object.entries(totals)
        .map(([channel, sent]) => ({ channel, sent, pct: grandTotal > 0 ? Math.round(sent / grandTotal * 100) : 0 }))
        .sort((a, b) => b.sent - a.sent);
});

const monthlyPerformance = computed(() => {
    const months = ['Ноя', 'Дек', 'Янв', 'Фев', 'Мар', 'Апр'];
    const values = [95, 210, 142, 185, 268, 286];
    const maxVal = Math.max(...values);
    return months.map((month, i) => ({
        month, sent: values[i], pct: Math.round(values[i] / maxVal * 100),
    }));
});

const roiPercent = computed(() => {
    if (!activeAutomation.value) return 0;
    const cost = activeAutomation.value.stats.cost || 1;
    return Math.round((activeAutomation.value.stats.revenue - cost) / cost * 100);
});

const funnelSteps = computed(() => {
    if (!activeAutomation.value) return [];
    const s = activeAutomation.value.stats;
    const max = s.triggered || 1;
    return [
        { icon: '⚡', label: 'Сработал',    value: s.triggered,   pct: 100,                                    color: 'var(--t-primary)' },
        { icon: '📤', label: 'Отправлено',  value: s.sent,        pct: Math.round(s.sent / max * 100),         color: '#3b82f6' },
        { icon: '📬', label: 'Открыто',     value: s.opened,      pct: Math.round(s.opened / max * 100),       color: '#22c55e' },
        { icon: '🖱️', label: 'Клики',       value: s.clicks,      pct: Math.round(s.clicks / max * 100),       color: '#f59e0b' },
        { icon: '📅', label: 'Конверсия',   value: s.conversions, pct: Math.round(s.conversions / max * 100),   color: '#a855f7' },
    ];
});

const abResults = computed(() => {
    if (!activeAutomation.value?.abEnabled) return [];
    return [
        { name: 'A', openRate: 68, conversion: 28, sent: 120, revenue: 84000, winner: true },
        { name: 'B', openRate: 54, conversion: 19, sent: 120, revenue: 57000, winner: false },
    ];
});

const recentLogs = computed(() => [
    { id: 1, clientName: 'Мария Королёва',      channel: 'whatsapp', status: 'converted', time: '08.04 14:30' },
    { id: 2, clientName: 'Елена Петрова',       channel: 'sms',      status: 'opened',    time: '08.04 13:20' },
    { id: 3, clientName: 'Виктория Соловьёва',  channel: 'push',     status: 'opened',    time: '08.04 12:55' },
    { id: 4, clientName: 'Алина Фёдорова',      channel: 'telegram', status: 'delivered',  time: '08.04 12:00' },
    { id: 5, clientName: 'Ирина Морозова',      channel: 'email',    status: 'clicked',    time: '07.04 18:40' },
    { id: 6, clientName: 'Дарья Волкова',       channel: 'push',     status: 'sent',       time: '07.04 16:15' },
    { id: 7, clientName: 'Наталья Белова',      channel: 'whatsapp', status: 'converted', time: '07.04 15:00' },
    { id: 8, clientName: 'Татьяна Новикова',    channel: 'sms',      status: 'failed',     time: '07.04 14:30' },
]);

const previewMessage = computed(() => {
    return (editForm.messageText || '')
        .replace('{name}', 'Мария')
        .replace('{master}', 'Анна Соколова')
        .replace('{service}', 'Окрашивание балаяж')
        .replace('{bonus}', '4 120')
        .replace('{date}', '12.04.2026')
        .replace('{salon}', 'BeautyLab Центр')
        .replace('{discount}', '15')
        .replace('{days}', '60');
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  EDITOR FORM                                                        */
/* ═══════════════════════════════════════════════════════════════════ */
const editForm = reactive({
    id: null,
    name: '',
    category: 'trigger',
    triggerType: 'birthday',
    delayValue: 0,
    delayUnit: 'hours',
    conditions: [],
    channels: ['push'],
    subject: '',
    messageText: '',
    abEnabled: false,
    abVariantB: '',
    abSplit: 50,
    abWinnerCriteria: 'open_rate',
    abTestDuration: 24,
    sendFromHour: '09:00',
    sendToHour: '21:00',
    sendDays: ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'],
    freqLimitCount: 1,
    freqLimitPeriod: '3days',
    quietPeriodHours: 72,
    flowSteps: [
        { type: 'trigger', label: 'Клиент активирует триггер' },
    ],
});

function resetEditForm() {
    Object.assign(editForm, {
        id: null, name: '', category: 'trigger', triggerType: 'birthday',
        delayValue: 0, delayUnit: 'hours', conditions: [], channels: ['push'],
        subject: '', messageText: '', abEnabled: false, abVariantB: '', abSplit: 50,
        abWinnerCriteria: 'open_rate', abTestDuration: 24, sendFromHour: '09:00', sendToHour: '21:00',
        sendDays: ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'], freqLimitCount: 1,
        freqLimitPeriod: '3days', quietPeriodHours: 72,
        flowSteps: [{ type: 'trigger', label: 'Клиент активирует триггер' }],
    });
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  ACTIONS                                                            */
/* ═══════════════════════════════════════════════════════════════════ */
function startCreateAutomation() {
    resetEditForm();
    activeEditorTab.value = 'trigger';
    activeView.value = 'editor';
}

function useTemplate(tpl) {
    resetEditForm();
    editForm.name = tpl.name;
    editForm.category = tpl.category;
    editForm.channels = [...tpl.defaultChannels];
    editForm.messageText = tpl.description;
    activeEditorTab.value = 'trigger';
    activeView.value = 'editor';
}

function editAutomation(auto) {
    resetEditForm();
    editForm.id = auto.id;
    editForm.name = auto.name;
    editForm.category = auto.category;
    editForm.channels = [...(auto.channels || [])];
    editForm.abEnabled = auto.abEnabled || false;
    activeEditorTab.value = 'trigger';
    activeView.value = 'editor';
}

function openAutomationStats(auto) {
    activeAutomation.value = auto;
    activeView.value = 'stats';
}

function toggleAutomation(auto) {
    if (auto.status === 'active') {
        auto.status = 'paused';
    } else if (auto.status === 'paused' || auto.status === 'draft') {
        auto.status = 'active';
    }
}

function duplicateAutomation(auto) {
    const clone = JSON.parse(JSON.stringify(auto));
    clone.id = Date.now();
    clone.name = auto.name + ' (копия)';
    clone.status = 'draft';
    clone.stats = { triggered: 0, sent: 0, opened: 0, clicks: 0, conversions: 0, revenue: 0, cost: 0 };
    clone.channelStats = [];
    automations.value.push(clone);
}

const showDeleteModal = ref(false);
const deleteTarget = ref(null);

function confirmDeleteAutomation(auto) {
    deleteTarget.value = auto;
    showDeleteModal.value = true;
}

function deleteAutomation() {
    if (deleteTarget.value) {
        automations.value = automations.value.filter(a => a.id !== deleteTarget.value.id);
    }
    showDeleteModal.value = false;
    deleteTarget.value = null;
}

function saveAutomation(status) {
    if (!editForm.name.trim()) return;

    if (editForm.id) {
        const existing = automations.value.find(a => a.id === editForm.id);
        if (existing) {
            existing.name = editForm.name;
            existing.category = editForm.category;
            existing.channels = [...editForm.channels];
            existing.abEnabled = editForm.abEnabled;
            existing.status = status;
        }
    } else {
        automations.value.push({
            id: Date.now(),
            icon: '🤖',
            name: editForm.name,
            description: editForm.messageText.substring(0, 80) + (editForm.messageText.length > 80 ? '...' : ''),
            category: editForm.category,
            status,
            channels: [...editForm.channels],
            abEnabled: editForm.abEnabled,
            stats: { triggered: 0, sent: 0, opened: 0, clicks: 0, conversions: 0, revenue: 0, cost: 0 },
            channelStats: [],
        });
    }

    activeView.value = 'dashboard';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  EDITOR HELPERS                                                     */
/* ═══════════════════════════════════════════════════════════════════ */
function addCondition() {
    editForm.conditions.push({ field: 'segment', operator: 'eq', value: '' });
}

function removeCondition(idx) {
    editForm.conditions.splice(idx, 1);
}

function toggleChannel(key) {
    const idx = editForm.channels.indexOf(key);
    if (idx >= 0) {
        if (editForm.channels.length > 1) editForm.channels.splice(idx, 1);
    } else {
        editForm.channels.push(key);
    }
}

function toggleDay(key) {
    const idx = editForm.sendDays.indexOf(key);
    if (idx >= 0) {
        if (editForm.sendDays.length > 1) editForm.sendDays.splice(idx, 1);
    } else {
        editForm.sendDays.push(key);
    }
}

function insertToken(token) {
    editForm.messageText += token;
}

function addFlowStep(type) {
    const labels = {
        condition: 'Проверить условие (напр., LTV > 10 000 ₽)',
        action:    'Отправить сообщение клиенту',
        wait:      'Подождать 3 дня',
    };
    const step = { type, label: labels[type] || '' };
    if (type === 'condition') {
        step.yesAction = 'Отправить';
        step.noAction = 'Пропустить';
    }
    editForm.flowSteps.push(step);
}

function removeFlowStep(idx) {
    if (editForm.flowSteps[idx]?.type !== 'trigger') {
        editForm.flowSteps.splice(idx, 1);
    }
}
</script>
