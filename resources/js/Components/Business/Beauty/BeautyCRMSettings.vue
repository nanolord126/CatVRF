<template>
<div class="space-y-4">
    <!-- ═══ HEADER ═══ -->
    <div class="flex justify-between items-center flex-wrap gap-3">
        <h2 class="text-lg font-semibold" style="color:var(--t-text)">⚙️ Настройки CRM</h2>
        <VButton size="sm" @click="saveAllSettings">💾 Сохранить все</VButton>
    </div>

    <!-- ═══ SETTINGS SECTIONS NAV ═══ -->
    <div class="flex items-center gap-1 overflow-x-auto pb-1">
        <button v-for="sec in sections" :key="sec.key"
                class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors"
                :style="activeSection === sec.key
                    ? 'background:var(--t-primary);color:#fff'
                    : 'background:var(--t-surface);color:var(--t-text-2)'"
                @click="activeSection = sec.key">
            {{ sec.icon }} {{ sec.label }}
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1: ACCESS RIGHTS                                       -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeSection === 'access'" class="space-y-4">
        <VCard title="🔐 Права доступа персонала">
            <div class="space-y-3">
                <div v-for="role in accessRoles" :key="role.id"
                     class="p-4 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ role.icon }}</span>
                            <div>
                                <div class="text-sm font-semibold" style="color:var(--t-text)">{{ role.name }}</div>
                                <div class="text-[10px]" style="color:var(--t-text-3)">{{ role.description }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <VBadge color="blue" size="sm">{{ role.usersCount }} сотрудников</VBadge>
                            <VButton size="sm" variant="outline" @click="editRole(role)">✏️</VButton>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="perm in role.permissions" :key="perm"
                              class="text-[10px] px-2 py-0.5 rounded-full"
                              style="background:var(--t-primary-dim);color:var(--t-primary)">{{ perm }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t" style="border-color:var(--t-border)">
                <VButton size="sm" variant="outline" @click="showCreateRoleModal = true">+ Добавить роль</VButton>
            </div>
        </VCard>

        <!-- Employee assignment -->
        <VCard title="👩‍💼 Назначение сотрудников">
            <div class="space-y-2">
                <div v-for="emp in employeeAssignments" :key="emp.id"
                     class="flex items-center gap-3 p-2 rounded-lg" style="background:var(--t-bg)">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                         style="background:var(--t-primary-dim);color:var(--t-primary)">{{ emp.name.charAt(0) }}</div>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ emp.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ emp.position }}</div>
                    </div>
                    <select v-model="emp.roleId" class="px-2 py-1 rounded text-xs border"
                            style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)">
                        <option v-for="role in accessRoles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 2: LOYALTY PROGRAM CONFIG                              -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeSection === 'loyalty'" class="space-y-4">
        <VCard title="🏆 Уровни программы лояльности">
            <div class="space-y-3">
                <div v-for="level in loyaltyLevels" :key="level.id"
                     class="p-4 rounded-xl border" :style="`background:${level.gradientBg};border-color:var(--t-border)`">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ level.icon }}</span>
                            <div>
                                <div class="text-sm font-bold" style="color:var(--t-text)">{{ level.name }}</div>
                                <div class="text-[10px]" style="color:var(--t-text-3)">
                                    от {{ fmtMoney(level.minSpend) }}
                                </div>
                            </div>
                        </div>
                        <VButton size="sm" variant="outline" @click="editLoyaltyLevel(level)">✏️</VButton>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 rounded-lg" style="background:var(--t-surface)">
                            <div class="text-sm font-bold" style="color:var(--t-primary)">{{ level.cashbackPct }}%</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Кэшбэк</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-surface)">
                            <div class="text-sm font-bold" style="color:var(--t-text)">{{ level.discountPct }}%</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Скидка</div>
                        </div>
                        <div class="p-2 rounded-lg" style="background:var(--t-surface)">
                            <div class="text-sm font-bold" style="color:var(--t-text)">{{ level.clientsCount }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Клиентов</div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>

        <VCard title="🎯 Правила начисления бонусов">
            <div class="space-y-2">
                <div v-for="rule in bonusRules" :key="rule.id"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-lg">{{ rule.icon }}</span>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ rule.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ rule.description }}</div>
                    </div>
                    <div class="text-sm font-bold" style="color:var(--t-primary)">{{ rule.value }}</div>
                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" v-model="rule.active"
                               class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                        <span class="text-[10px]" style="color:var(--t-text-3)">Вкл</span>
                    </label>
                    <VButton size="sm" variant="outline" @click="editBonusRule(rule)">✏️</VButton>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t" style="border-color:var(--t-border)">
                <VButton size="sm" variant="outline" @click="showCreateBonusRuleModal = true">+ Добавить правило</VButton>
            </div>
        </VCard>

        <VCard title="🔧 Общие настройки программы">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Срок действия бонусов (дней)</label>
                    <VInput v-model="loyaltySettings.bonusExpireDays" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Макс. оплата бонусами (%)</label>
                    <VInput v-model="loyaltySettings.maxBonusPayPct" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Приветственный бонус (₽)</label>
                    <VInput v-model="loyaltySettings.welcomeBonus" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Бонус за отзыв (₽)</label>
                    <VInput v-model="loyaltySettings.reviewBonus" type="number" />
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 3: INTEGRATIONS                                        -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeSection === 'integrations'" class="space-y-4">
        <VCard title="🔗 Интеграции с мессенджерами">
            <div class="space-y-3">
                <div v-for="itg in integrations" :key="itg.id"
                     class="p-4 rounded-lg border flex items-center gap-4"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                         :style="`background:${itg.color}20`">{{ itg.icon }}</div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold" style="color:var(--t-text)">{{ itg.name }}</span>
                            <VBadge :color="itg.connected ? 'green' : 'gray'" size="sm">
                                {{ itg.connected ? 'Подключён' : 'Отключён' }}
                            </VBadge>
                        </div>
                        <div class="text-xs mt-0.5" style="color:var(--t-text-3)">{{ itg.description }}</div>
                        <div v-if="itg.connected" class="text-[10px] mt-1" style="color:var(--t-text-3)">
                            Последняя синхронизация: {{ itg.lastSync }}
                        </div>
                    </div>
                    <VButton size="sm" :variant="itg.connected ? 'outline' : 'primary'"
                             @click="toggleIntegration(itg)">
                        {{ itg.connected ? 'Настроить' : 'Подключить' }}
                    </VButton>
                </div>
            </div>
        </VCard>

        <VCard title="📱 Настройки уведомлений">
            <div class="space-y-3">
                <div v-for="notif in notificationSettings" :key="notif.id"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-lg">{{ notif.icon }}</span>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ notif.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ notif.description }}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <label v-for="ch in notif.channels" :key="ch.key"
                               class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" v-model="ch.enabled"
                                   class="w-3.5 h-3.5 rounded" style="accent-color:var(--t-primary)">
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ ch.label }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 4: MESSAGE TEMPLATES                                   -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeSection === 'templates'" class="space-y-4">
        <VCard title="📝 Шаблоны сообщений">
            <div class="space-y-3">
                <div v-for="tpl in messageTemplates" :key="tpl.id"
                     class="p-4 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ tpl.icon }}</span>
                            <div>
                                <div class="text-sm font-semibold" style="color:var(--t-text)">{{ tpl.name }}</div>
                                <div class="text-[10px]" style="color:var(--t-text-3)">
                                    {{ tpl.type }} · Использован {{ tpl.usageCount }} раз
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <VBadge v-for="ch in tpl.channels" :key="ch" color="blue" size="sm">{{ ch }}</VBadge>
                            <VButton size="sm" variant="outline" @click="editTemplate(tpl)">✏️</VButton>
                            <VButton size="sm" variant="outline" @click="deleteTemplate(tpl.id)">🗑️</VButton>
                        </div>
                    </div>
                    <div class="p-2 rounded text-xs whitespace-pre-wrap"
                         style="background:var(--t-surface);color:var(--t-text-2)">{{ tpl.body }}</div>
                    <div class="flex flex-wrap gap-1 mt-2">
                        <span v-for="vr in tpl.variables" :key="vr"
                              class="text-[10px] px-1.5 py-0.5 rounded"
                              style="background:var(--t-primary-dim);color:var(--t-primary)">{{ vr }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t" style="border-color:var(--t-border)">
                <VButton size="sm" variant="outline" @click="showCreateTemplateModal = true">+ Создать шаблон</VButton>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 5: IMPORT / EXPORT                                     -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeSection === 'import_export'" class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
            <VCard title="📥 Импорт клиентов">
                <div class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition hover:opacity-80"
                     style="border-color:var(--t-border)"
                     @click="triggerImport"
                     @dragover.prevent @drop.prevent="handleDrop">
                    <div class="text-3xl mb-2">📁</div>
                    <div class="text-sm font-medium" style="color:var(--t-text)">
                        Перетащите файл или нажмите
                    </div>
                    <div class="text-xs mt-1" style="color:var(--t-text-3)">
                        Формат: xlsx, csv · Максимум 5000 записей
                    </div>
                    <input ref="importFileInput" type="file" class="hidden" accept=".xlsx,.csv" @change="handleImportFile">
                </div>
                <div v-if="importResult" class="mt-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-sm font-medium" style="color:var(--t-text)">Результат импорта:</div>
                    <div class="text-xs mt-1" style="color:var(--t-text-2)">
                        ✅ Загружено: {{ importResult.imported }} · ⚠️ Пропущено: {{ importResult.skipped }} · ❌ Ошибок: {{ importResult.errors }}
                    </div>
                </div>
            </VCard>

            <VCard title="📤 Экспорт клиентов">
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="fmt in exportFormats" :key="fmt.key"
                                class="p-3 rounded-lg border text-center transition hover:shadow"
                                style="background:var(--t-bg);border-color:var(--t-border)"
                                @click="exportClients(fmt.key)">
                            <div class="text-xl mb-1">{{ fmt.icon }}</div>
                            <div class="text-xs font-medium" style="color:var(--t-text)">{{ fmt.label }}</div>
                        </button>
                    </div>
                    <div class="p-3 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-xs font-medium mb-2" style="color:var(--t-text-2)">Включить поля:</div>
                        <div class="flex flex-wrap gap-2">
                            <label v-for="field in exportFields" :key="field.key"
                                   class="flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" v-model="field.selected"
                                       class="w-3.5 h-3.5 rounded" style="accent-color:var(--t-primary)">
                                <span class="text-[10px]" style="color:var(--t-text-2)">{{ field.label }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Duplicate detection -->
        <VCard title="🔍 Поиск дубликатов">
            <div class="flex items-center gap-3 mb-3">
                <VButton size="sm" @click="scanDuplicates">🔍 Сканировать</VButton>
                <span class="text-xs" style="color:var(--t-text-3)">
                    Найдено: {{ duplicates.length }} потенциальных дубликатов
                </span>
            </div>
            <div class="space-y-2">
                <div v-for="dup in duplicates" :key="dup.id"
                     class="p-3 rounded-lg border flex items-center gap-3"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-xl">⚠️</div>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">
                            {{ dup.client1 }} ↔ {{ dup.client2 }}
                        </div>
                        <div class="text-xs" style="color:var(--t-text-3)">
                            Совпадение: {{ dup.matchFields.join(', ') }} · Точность: {{ dup.confidence }}%
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <VButton size="sm" variant="outline" @click="mergeDuplicates(dup)">Объединить</VButton>
                        <VButton size="sm" variant="outline" @click="ignoreDuplicate(dup.id)">Пропустить</VButton>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Blacklist -->
        <VCard title="🚫 Чёрный список">
            <div class="space-y-2">
                <div v-for="bl in blacklist" :key="bl.id"
                     class="p-3 rounded-lg border flex items-center gap-3"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-lg">🚫</div>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ bl.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ bl.phone }} · Причина: {{ bl.reason }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">Добавлен: {{ bl.addedDate }}</div>
                    </div>
                    <VButton size="sm" variant="outline" @click="removeFromBlacklist(bl.id)">Удалить</VButton>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t" style="border-color:var(--t-border)">
                <VButton size="sm" variant="outline" @click="showAddBlacklistModal = true">+ Добавить в чёрный список</VButton>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 6: FIELD CONFIGURATION                                 -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeSection === 'fields'" class="space-y-4">
        <VCard title="📋 Настройка полей карточки клиента">
            <div class="text-xs mb-3" style="color:var(--t-text-3)">
                Настройте, какие поля отображаются в карточке клиента, их порядок и обязательность.
            </div>
            <div class="space-y-2">
                <div v-for="field in clientCardFields" :key="field.key"
                     class="flex items-center gap-3 p-3 rounded-lg border"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <span class="text-sm cursor-move" style="color:var(--t-text-3)">⠿</span>
                    <span class="text-lg">{{ field.icon }}</span>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ field.label }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ field.fieldType }}</div>
                    </div>
                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" v-model="field.visible"
                               class="w-3.5 h-3.5 rounded" style="accent-color:var(--t-primary)">
                        <span class="text-[10px]" style="color:var(--t-text-3)">Видимое</span>
                    </label>
                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" v-model="field.required"
                               class="w-3.5 h-3.5 rounded" style="accent-color:var(--t-primary)">
                        <span class="text-[10px]" style="color:var(--t-text-3)">Обяз.</span>
                    </label>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t flex items-center justify-between"
                 style="border-color:var(--t-border)">
                <VButton size="sm" variant="outline" @click="addCustomField">+ Пользовательское поле</VButton>
                <VButton size="sm" @click="saveFieldConfig">💾 Сохранить конфигурацию</VButton>
            </div>
        </VCard>

        <VCard title="🏷️ Управление тегами">
            <div class="flex flex-wrap gap-2 mb-3">
                <span v-for="tag in availableTags" :key="tag.id"
                      class="px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1 cursor-pointer"
                      :style="`background:${tag.color}20;color:${tag.color}`">
                    {{ tag.name }}
                    <button class="text-[10px] opacity-60 hover:opacity-100 transition" @click="deleteTag(tag.id)">✕</button>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <VInput v-model="newTagName" placeholder="Новый тег..." class="flex-1" />
                <input v-model="newTagColor" type="color" class="w-8 h-8 rounded cursor-pointer">
                <VButton size="sm" @click="createTag">+ Добавить</VButton>
            </div>
        </VCard>

        <VCard title="📊 Пользовательские сегменты">
            <div class="text-xs mb-3" style="color:var(--t-text-3)">
                Настройте критерии автоматической сегментации клиентов.
            </div>
            <div class="space-y-2">
                <div v-for="seg in segmentRules" :key="seg.id"
                     class="p-3 rounded-lg border flex items-center gap-3"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="w-3 h-3 rounded-full" :style="`background:${seg.color}`"></div>
                    <div class="flex-1">
                        <div class="text-sm font-medium" style="color:var(--t-text)">{{ seg.name }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ seg.criteria }}</div>
                    </div>
                    <label class="flex items-center gap-1 cursor-pointer">
                        <input type="checkbox" v-model="seg.autoAssign"
                               class="w-3.5 h-3.5 rounded" style="accent-color:var(--t-primary)">
                        <span class="text-[10px]" style="color:var(--t-text-3)">Авто</span>
                    </label>
                    <VButton size="sm" variant="outline" @click="editSegmentRule(seg)">✏️</VButton>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 7: TRIGGERS                                            -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <div v-if="activeSection === 'triggers'" class="space-y-4">
        <VCard title="🤖 Настройки автоматических триггеров">
            <div class="space-y-3">
                <div v-for="trigger in triggerSettings" :key="trigger.id"
                     class="p-4 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ trigger.icon }}</span>
                            <div>
                                <div class="text-sm font-semibold" style="color:var(--t-text)">{{ trigger.name }}</div>
                                <div class="text-[10px]" style="color:var(--t-text-3)">{{ trigger.description }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <VBadge :color="trigger.active ? 'green' : 'gray'" size="sm">
                                {{ trigger.active ? 'Вкл' : 'Выкл' }}
                            </VBadge>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="trigger.active" class="sr-only peer">
                                <div class="w-9 h-5 rounded-full peer-checked:after:translate-x-full after:absolute after:left-[2px] after:top-[2px] after:w-4 after:h-4 after:rounded-full after:transition-all"
                                     :style="`background:${trigger.active ? 'var(--t-primary)' : 'var(--t-border)'}`">
                                    <div class="absolute top-[2px] left-[2px] w-4 h-4 rounded-full transition-transform"
                                         :style="`background:white;transform:translateX(${trigger.active ? '16px' : '0'})`"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 rounded" style="background:var(--t-surface)">
                            <div class="text-xs font-bold" style="color:var(--t-primary)">{{ trigger.sentCount }}</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Отправлено</div>
                        </div>
                        <div class="p-2 rounded" style="background:var(--t-surface)">
                            <div class="text-xs font-bold" style="color:var(--t-text)">{{ trigger.openRate }}%</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Открытия</div>
                        </div>
                        <div class="p-2 rounded" style="background:var(--t-surface)">
                            <div class="text-xs font-bold" style="color:#22c55e">{{ trigger.conversionRate }}%</div>
                            <div class="text-[10px]" style="color:var(--t-text-3)">Конверсия</div>
                        </div>
                    </div>
                </div>
            </div>
        </VCard>

        <VCard title="⏰ Расписание автоматизации">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Напоминание о записи за (часов)</label>
                    <VInput v-model="automationSchedule.reminderHoursBefore" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Благодарность за визит через (часов)</label>
                    <VInput v-model="automationSchedule.thanksHoursAfter" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Реактивация «спящих» через (дней)</label>
                    <VInput v-model="automationSchedule.reactivateDays" type="number" />
                </div>
                <div>
                    <label class="block text-xs mb-1" style="color:var(--t-text-2)">Поздравление с ДР за (дней)</label>
                    <VInput v-model="automationSchedule.birthdayDaysBefore" type="number" />
                </div>
            </div>
        </VCard>
    </div>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- MODALS                                                         -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <VModal :show="showCreateRoleModal" @close="showCreateRoleModal = false" title="Новая роль">
        <div class="space-y-3">
            <VInput v-model="newRoleName" placeholder="Название роли" />
            <textarea v-model="newRoleDescription" rows="2" placeholder="Описание"
                      class="w-full px-3 py-2 rounded-lg text-sm border"
                      style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"></textarea>
            <div class="text-xs font-medium mb-1" style="color:var(--t-text-2)">Права доступа:</div>
            <div class="flex flex-wrap gap-2">
                <label v-for="perm in allPermissions" :key="perm"
                       class="flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" v-model="newRolePermissions" :value="perm"
                           class="w-3.5 h-3.5 rounded" style="accent-color:var(--t-primary)">
                    <span class="text-xs" style="color:var(--t-text-2)">{{ perm }}</span>
                </label>
            </div>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showCreateRoleModal = false">Отмена</VButton>
            <VButton @click="createRole">Создать</VButton>
        </template>
    </VModal>

    <VModal :show="showAddBlacklistModal" @close="showAddBlacklistModal = false" title="Добавить в чёрный список">
        <div class="space-y-3">
            <VInput v-model="newBlEntry.name" placeholder="ФИО клиента" />
            <VInput v-model="newBlEntry.phone" placeholder="Телефон" />
            <textarea v-model="newBlEntry.reason" rows="2" placeholder="Причина блокировки"
                      class="w-full px-3 py-2 rounded-lg text-sm border"
                      style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"></textarea>
        </div>
        <template #footer>
            <VButton variant="outline" @click="showAddBlacklistModal = false">Отмена</VButton>
            <VButton @click="addToBlacklist">Добавить</VButton>
        </template>
    </VModal>

    <!-- Universal Inline Edit Modal -->
    <VModal :show="inlineEdit.show" @close="inlineEdit.show = false" :title="inlineEdit.title">
        <div class="space-y-3">
            <div v-for="(field, idx) in inlineEdit.fields" :key="idx">
                <label class="block text-xs mb-1" style="color:var(--t-text-2)">{{ field.label }}</label>
                <textarea v-if="field.type === 'textarea'"
                          v-model="field.value" rows="4"
                          class="w-full px-3 py-2 rounded-lg text-sm border"
                          style="background:var(--t-surface);color:var(--t-text);border-color:var(--t-border)"></textarea>
                <VInput v-else v-model="field.value" :type="field.type || 'text'" />
            </div>
            <label v-if="inlineEdit.checkbox" class="flex items-center gap-2 cursor-pointer pt-2">
                <input type="checkbox" v-model="inlineEdit.checkbox.value"
                       class="w-4 h-4 rounded" style="accent-color:var(--t-primary)">
                <span class="text-sm" style="color:var(--t-text)">{{ inlineEdit.checkbox.label }}</span>
            </label>
        </div>
        <template #footer>
            <VButton variant="outline" @click="inlineEdit.show = false">Отмена</VButton>
            <VButton @click="saveInlineEdit">💾 Сохранить</VButton>
        </template>
    </VModal>
</div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
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
});
const emit = defineEmits(['save-settings']);

/* ═══════════════════════════════════════════════════════════════════ */
/*  SECTIONS NAV                                                       */
/* ═══════════════════════════════════════════════════════════════════ */
const sections = [
    { key: 'access',        icon: '🔐', label: 'Доступ' },
    { key: 'loyalty',       icon: '🏆', label: 'Лояльность' },
    { key: 'integrations',  icon: '🔗', label: 'Интеграции' },
    { key: 'templates',     icon: '📝', label: 'Шаблоны' },
    { key: 'import_export', icon: '📥', label: 'Импорт/Экспорт' },
    { key: 'fields',        icon: '📋', label: 'Поля' },
    { key: 'triggers',      icon: '🤖', label: 'Триггеры' },
];
const activeSection = ref('access');

function fmtMoney(v) {
    return Number(v).toLocaleString('ru-RU') + ' ₽';
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  UNIVERSAL INLINE EDIT MODAL                                        */
/* ═══════════════════════════════════════════════════════════════════ */
const inlineEdit = reactive({
    show: false,
    title: '',
    fields: [],
    checkbox: null,
    onSave: null,
});
function openInlineEdit(config) {
    inlineEdit.title = config.title;
    inlineEdit.fields = config.fields.map(f => ({ ...f }));
    inlineEdit.checkbox = config.checkbox ? { ...config.checkbox } : null;
    inlineEdit.onSave = config.onSave;
    inlineEdit.show = true;
}
function saveInlineEdit() {
    if (inlineEdit.onSave) {
        inlineEdit.onSave(inlineEdit.fields, inlineEdit.checkbox);
    }
    inlineEdit.show = false;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  1. ACCESS RIGHTS                                                   */
/* ═══════════════════════════════════════════════════════════════════ */
const accessRoles = ref([
    { id: 1, icon: '👑', name: 'Администратор', description: 'Полный доступ ко всем функциям CRM', usersCount: 2, permissions: ['Клиенты', 'Записи', 'Финансы', 'Маркетинг', 'Настройки', 'Аналитика', 'Удаление'] },
    { id: 2, icon: '💇', name: 'Мастер', description: 'Доступ к своим клиентам и записям', usersCount: 5, permissions: ['Клиенты (свои)', 'Записи (свои)', 'Бонусы (начисление)'] },
    { id: 3, icon: '📋', name: 'Менеджер', description: 'Управление клиентами и записями', usersCount: 3, permissions: ['Клиенты', 'Записи', 'Бонусы', 'Маркетинг', 'Аналитика'] },
    { id: 4, icon: '📢', name: 'Маркетолог', description: 'Рассылки, кампании, аналитика', usersCount: 1, permissions: ['Рассылки', 'Кампании', 'Аналитика', 'Сегменты'] },
]);

const allPermissions = ['Клиенты', 'Записи', 'Финансы', 'Маркетинг', 'Настройки', 'Аналитика', 'Удаление', 'Рассылки', 'Кампании', 'Сегменты', 'Бонусы'];
const showCreateRoleModal = ref(false);
const newRoleName = ref('');
const newRoleDescription = ref('');
const newRolePermissions = ref([]);

const employeeAssignments = ref([
    { id: 1, name: 'Анна Соколова',     position: 'Стилист-колорист',  roleId: 2 },
    { id: 2, name: 'Ольга Демидова',    position: 'Мастер маникюра',   roleId: 2 },
    { id: 3, name: 'Светлана Романова', position: 'Мастер-косметолог', roleId: 2 },
    { id: 4, name: 'Кристина Лебедева', position: 'Бровист',           roleId: 2 },
    { id: 5, name: 'Евгения Ковалёва',  position: 'Администратор',     roleId: 3 },
    { id: 6, name: 'Дмитрий Орлов',     position: 'Маркетолог',        roleId: 4 },
]);

function editRole(role) {
    openInlineEdit({
        title: 'Редактирование роли',
        fields: [{ label: 'Название роли', value: role.name, type: 'text' }],
        onSave(fields) {
            if (fields[0].value.trim()) role.name = fields[0].value.trim();
        },
    });
}
function createRole() {
    if (!newRoleName.value) return;
    accessRoles.value.push({
        id: Date.now(), icon: '👤', name: newRoleName.value, description: newRoleDescription.value,
        usersCount: 0, permissions: [...newRolePermissions.value],
    });
    newRoleName.value = '';
    newRoleDescription.value = '';
    newRolePermissions.value = [];
    showCreateRoleModal.value = false;
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  2. LOYALTY PROGRAM                                                 */
/* ═══════════════════════════════════════════════════════════════════ */
const loyaltyLevels = ref([
    { id: 1, icon: '🥉', name: 'Bronze',   minSpend: 0,      cashbackPct: 3,  discountPct: 0,  clientsCount: 48, gradientBg: 'linear-gradient(135deg, var(--t-bg) 0%, #cd7f3220 100%)' },
    { id: 2, icon: '🥈', name: 'Silver',   minSpend: 10000,  cashbackPct: 5,  discountPct: 3,  clientsCount: 25, gradientBg: 'linear-gradient(135deg, var(--t-bg) 0%, #c0c0c020 100%)' },
    { id: 3, icon: '🥇', name: 'Gold',     minSpend: 30000,  cashbackPct: 7,  discountPct: 5,  clientsCount: 12, gradientBg: 'linear-gradient(135deg, var(--t-bg) 0%, #ffd70020 100%)' },
    { id: 4, icon: '💎', name: 'Platinum', minSpend: 100000, cashbackPct: 10, discountPct: 10, clientsCount: 4,  gradientBg: 'linear-gradient(135deg, var(--t-bg) 0%, #e5e4e220 100%)' },
]);
const bonusRules = ref([
    { id: 1, icon: '🎉', name: 'Приветственный бонус',   description: 'При первой регистрации', value: '200 ₽',  active: true },
    { id: 2, icon: '🎂', name: 'День рождения',          description: 'В день рождения клиента', value: '500 ₽',  active: true },
    { id: 3, icon: '⭐', name: 'Отзыв',                  description: 'За каждый отзыв с фото',  value: '100 ₽',  active: true },
    { id: 4, icon: '👫', name: 'Реферал',                 description: 'За приглашённого друга',   value: '300 ₽',  active: true },
    { id: 5, icon: '🔄', name: 'Реактивация',             description: 'Возврат через 60+ дней',  value: '150 ₽',  active: false },
]);
const loyaltySettings = reactive({
    bonusExpireDays: 365,
    maxBonusPayPct: 30,
    welcomeBonus: 200,
    reviewBonus: 100,
});
const showCreateBonusRuleModal = ref(false);

function editLoyaltyLevel(level) {
    openInlineEdit({
        title: `Редактирование уровня «${level.name}»`,
        fields: [
            { label: 'Мин. сумма (₽)', value: String(level.minSpend), type: 'number' },
            { label: 'Кешбэк (%)', value: String(level.cashbackPct), type: 'number' },
        ],
        onSave(fields) {
            level.minSpend = Number(fields[0].value) || 0;
            level.cashbackPct = Number(fields[1].value) || 0;
        },
    });
}
function editBonusRule(rule) {
    openInlineEdit({
        title: `Редактирование правила «${rule.name}»`,
        fields: [{ label: 'Значение бонуса', value: rule.value, type: 'text' }],
        checkbox: { label: `Правило «${rule.name}» — активно`, value: rule.active },
        onSave(fields, checkbox) {
            if (fields[0].value.trim()) rule.value = fields[0].value.trim();
            if (checkbox) rule.active = checkbox.value;
        },
    });
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  3. INTEGRATIONS                                                    */
/* ═══════════════════════════════════════════════════════════════════ */
const integrations = ref([
    { id: 1, icon: '📱', name: 'WhatsApp Business',  color: '#25D366', connected: true,  description: 'Двустороннее общение с клиентами', lastSync: '09.04.2026 14:30' },
    { id: 2, icon: '✈️', name: 'Telegram Bot',       color: '#0088CC', connected: true,  description: 'Бот для записи и уведомлений',     lastSync: '09.04.2026 14:28' },
    { id: 3, icon: '💬', name: 'SMS.ru',              color: '#FF6B35', connected: true,  description: 'SMS-рассылки и уведомления',        lastSync: '09.04.2026 10:00' },
    { id: 4, icon: '📧', name: 'Email (Mailgun)',     color: '#F06B66', connected: false, description: 'Email-рассылки и транзакционные письма', lastSync: null },
    { id: 5, icon: '📞', name: 'IP-телефония',        color: '#4CAF50', connected: false, description: 'Запись звонков и автоматизация',     lastSync: null },
    { id: 6, icon: '🏦', name: 'Онлайн-касса',        color: '#2196F3', connected: true,  description: 'Чеки и фискализация',               lastSync: '09.04.2026 13:00' },
]);

const notificationSettings = ref([
    { id: 1, icon: '📋', name: 'Новая запись',       description: 'Уведомление при создании записи', channels: [
        { key: 'whatsapp', label: 'WhatsApp', enabled: true }, { key: 'sms', label: 'SMS', enabled: true }, { key: 'push', label: 'Push', enabled: true },
    ]},
    { id: 2, icon: '⏰', name: 'Напоминание',        description: 'Напоминание перед визитом', channels: [
        { key: 'whatsapp', label: 'WhatsApp', enabled: true }, { key: 'sms', label: 'SMS', enabled: true }, { key: 'push', label: 'Push', enabled: false },
    ]},
    { id: 3, icon: '❌', name: 'Отмена',              description: 'Уведомление об отмене записи', channels: [
        { key: 'whatsapp', label: 'WhatsApp', enabled: true }, { key: 'sms', label: 'SMS', enabled: false }, { key: 'push', label: 'Push', enabled: true },
    ]},
    { id: 4, icon: '🎁', name: 'Начисление бонусов', description: 'Уведомление о начислении бонусов', channels: [
        { key: 'whatsapp', label: 'WhatsApp', enabled: true }, { key: 'sms', label: 'SMS', enabled: false }, { key: 'push', label: 'Push', enabled: true },
    ]},
    { id: 5, icon: '🎂', name: 'День рождения',      description: 'Поздравление с днём рождения', channels: [
        { key: 'whatsapp', label: 'WhatsApp', enabled: true }, { key: 'sms', label: 'SMS', enabled: true }, { key: 'push', label: 'Push', enabled: false },
    ]},
]);

function toggleIntegration(itg) {
    itg.connected = !itg.connected;
    if (itg.connected) {
        itg.lastSync = new Date().toLocaleString('ru-RU');
    }
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  4. MESSAGE TEMPLATES                                               */
/* ═══════════════════════════════════════════════════════════════════ */
const messageTemplates = ref([
    { id: 1, icon: '⏰', name: 'Напоминание о записи',   type: 'Автоматический', channels: ['WhatsApp', 'SMS'], usageCount: 342, body: '{name}, напоминаем о вашей записи на {service} завтра в {time}.\nМастер: {master}\nАдрес: {address}\n\nОтветьте «Да» для подтверждения или «Нет» для отмены.', variables: ['{name}', '{service}', '{time}', '{master}', '{address}'] },
    { id: 2, icon: '🙏', name: 'Благодарность за визит', type: 'Автоматический', channels: ['WhatsApp'],        usageCount: 285, body: '{name}, спасибо за визит! 🎉\nВам начислено {bonus} бонусов.\nБаланс: {balance} ₽\n\nБудем рады видеть вас снова!', variables: ['{name}', '{bonus}', '{balance}'] },
    { id: 3, icon: '🎂', name: 'Поздравление с ДР',      type: 'Автоматический', channels: ['WhatsApp', 'SMS'], usageCount: 48,  body: '{name}, с Днём рождения! 🎂🎁\nДарим вам {bonus} бонусов на любые услуги!\nВаш баланс: {balance} ₽\n\nЖдём вас!', variables: ['{name}', '{bonus}', '{balance}'] },
    { id: 4, icon: '📢', name: 'Акция / Промо',          type: 'Маркетинг',      channels: ['WhatsApp', 'SMS'], usageCount: 124, body: '{name}, специально для вас! 🔥\n{promo_text}\nСкидка до {discount}%\n\nЗаписаться: {link}', variables: ['{name}', '{promo_text}', '{discount}', '{link}'] },
    { id: 5, icon: '💤', name: 'Реактивация',             type: 'Автоматический', channels: ['WhatsApp'],        usageCount: 67,  body: '{name}, мы скучаем! 💕\nВас не было уже {days} дней.\nДарим {bonus} бонусов за возвращение!\n\nЗаписаться: {link}', variables: ['{name}', '{days}', '{bonus}', '{link}'] },
]);
const showCreateTemplateModal = ref(false);

function editTemplate(tpl) {
    openInlineEdit({
        title: `Редактирование шаблона «${tpl.name}»`,
        fields: [{ label: 'Текст шаблона', value: tpl.body, type: 'textarea' }],
        onSave(fields) {
            if (fields[0].value.trim()) tpl.body = fields[0].value.trim();
        },
    });
}
function deleteTemplate(id) { messageTemplates.value = messageTemplates.value.filter(t => t.id !== id); }

/* ═══════════════════════════════════════════════════════════════════ */
/*  5. IMPORT / EXPORT                                                 */
/* ═══════════════════════════════════════════════════════════════════ */
const importFileInput = ref(null);
const importResult = ref(null);
const exportFormats = [
    { key: 'xlsx', icon: '📗', label: 'Excel' },
    { key: 'csv',  icon: '📄', label: 'CSV' },
    { key: 'pdf',  icon: '📕', label: 'PDF' },
];
const exportFields = ref([
    { key: 'name',     label: 'Имя',           selected: true },
    { key: 'phone',    label: 'Телефон',        selected: true },
    { key: 'email',    label: 'Email',          selected: true },
    { key: 'visits',   label: 'Кол-во визитов', selected: true },
    { key: 'spent',    label: 'Сумма трат',     selected: true },
    { key: 'segment',  label: 'Сегмент',        selected: true },
    { key: 'loyalty',  label: 'Уровень лояльности', selected: false },
    { key: 'birthday', label: 'День рождения',  selected: false },
    { key: 'tags',     label: 'Теги',           selected: false },
    { key: 'source',   label: 'Источник',       selected: false },
]);

const duplicates = ref([
    { id: 1, client1: 'Мария Королёва', client2: 'Мария Е. Королёва', matchFields: ['Телефон', 'Email'], confidence: 92 },
    { id: 2, client1: 'Елена Петрова',  client2: 'Петрова Елена В.',  matchFields: ['Телефон'],          confidence: 78 },
]);

const blacklist = ref([
    { id: 1, name: 'Иванов Пётр', phone: '+7 900 000-00-01', reason: 'Неоднократные неявки', addedDate: '15.03.2026' },
    { id: 2, name: 'Сидорова Ольга', phone: '+7 900 000-00-02', reason: 'Грубое поведение', addedDate: '01.04.2026' },
]);
const showAddBlacklistModal = ref(false);
const newBlEntry = reactive({ name: '', phone: '', reason: '' });

function triggerImport() { importFileInput.value?.click(); }
function handleDrop(e) {
    const file = e.dataTransfer?.files?.[0];
    if (file) {
        importResult.value = { imported: Math.floor(Math.random() * 200) + 50, skipped: Math.floor(Math.random() * 10), errors: Math.floor(Math.random() * 3) };
    }
}
function handleImportFile(e) {
    const file = e.target?.files?.[0];
    if (!file) return;
    importResult.value = { imported: 127, skipped: 3, errors: 1 };
}
function exportClients(format) {
    const fields = exportFields.value.filter(f => f.selected).map(f => f.key);
    const header = '\uFEFF' + fields.join(';') + '\n';
    const rows = 'Demo Client;+7 999 000-00-01;demo@test.ru;5;12500;VIP\n';
    const blob = new Blob([header + rows], { type: format === 'csv' ? 'text/csv;charset=utf-8;' : 'application/octet-stream' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `clients_export_${Date.now()}.${format}`;
    a.click();
    URL.revokeObjectURL(url);
}
function scanDuplicates() {
    duplicates.value = [
        { id: Date.now(), client1: 'Мария Королёва', client2: 'Мария Е. Королёва', matchFields: ['Телефон', 'Email'], confidence: 92 },
        { id: Date.now() + 1, client1: 'Елена Петрова', client2: 'Петрова Елена В.', matchFields: ['Телефон'], confidence: 78 },
    ];
}
function mergeDuplicates(dup) {
    duplicates.value = duplicates.value.filter(d => d.id !== dup.id);
}
function ignoreDuplicate(id) { duplicates.value = duplicates.value.filter(d => d.id !== id); }
function addToBlacklist() {
    blacklist.value.push({ id: Date.now(), name: newBlEntry.name, phone: newBlEntry.phone, reason: newBlEntry.reason, addedDate: new Date().toLocaleDateString('ru-RU') });
    Object.assign(newBlEntry, { name: '', phone: '', reason: '' });
    showAddBlacklistModal.value = false;
}
function removeFromBlacklist(id) { blacklist.value = blacklist.value.filter(b => b.id !== id); }

/* ═══════════════════════════════════════════════════════════════════ */
/*  6. FIELD CONFIGURATION                                             */
/* ═══════════════════════════════════════════════════════════════════ */
const clientCardFields = ref([
    { key: 'name',       icon: '👤', label: 'ФИО',               fieldType: 'Текст',       visible: true,  required: true },
    { key: 'phone',      icon: '📞', label: 'Телефон',           fieldType: 'Телефон',     visible: true,  required: true },
    { key: 'email',      icon: '📧', label: 'Email',             fieldType: 'Email',       visible: true,  required: false },
    { key: 'birthday',   icon: '🎂', label: 'Дата рождения',     fieldType: 'Дата',        visible: true,  required: false },
    { key: 'segment',    icon: '🏷️', label: 'Сегмент',           fieldType: 'Выбор',       visible: true,  required: false },
    { key: 'source',     icon: '📡', label: 'Источник',          fieldType: 'Выбор',       visible: true,  required: false },
    { key: 'allergies',  icon: '⚠️', label: 'Аллергии',          fieldType: 'Текст',       visible: true,  required: false },
    { key: 'skinType',   icon: '🧴', label: 'Тип кожи',          fieldType: 'Выбор',       visible: true,  required: false },
    { key: 'hairType',   icon: '💇', label: 'Тип волос',          fieldType: 'Выбор',       visible: true,  required: false },
    { key: 'favMaster',  icon: '⭐', label: 'Любимый мастер',    fieldType: 'Связь',       visible: true,  required: false },
    { key: 'prefTime',   icon: '🕒', label: 'Предпочитаемое время', fieldType: 'Выбор',    visible: true,  required: false },
    { key: 'notes',      icon: '📝', label: 'Заметки',           fieldType: 'Текст (длин.)', visible: true, required: false },
    { key: 'tags',       icon: '🏷️', label: 'Теги',              fieldType: 'Множественный', visible: true, required: false },
]);

const availableTags = ref([
    { id: 1, name: 'VIP',        color: '#f59e0b' },
    { id: 2, name: 'Новичок',    color: '#22c55e' },
    { id: 3, name: 'Блондинка',  color: '#eab308' },
    { id: 4, name: 'Аллергия',   color: '#ef4444' },
    { id: 5, name: 'Невеста',    color: '#ec4899' },
    { id: 6, name: 'Корпоратив', color: '#6366f1' },
    { id: 7, name: 'Проблемная кожа', color: '#f97316' },
]);
const newTagName = ref('');
const newTagColor = ref('#3b82f6');

const segmentRules = ref([
    { id: 1, name: 'Новые клиенты',     color: '#22c55e', criteria: 'Визитов = 0, дней с регистрации ≤ 30',                     autoAssign: true },
    { id: 2, name: 'Лояльные',           color: '#3b82f6', criteria: 'Визитов ≥ 5, последний визит ≤ 30 дней',                   autoAssign: true },
    { id: 3, name: 'VIP',                color: '#f59e0b', criteria: 'Сумма ≥ 50 000 ₽, визитов ≥ 10',                           autoAssign: true },
    { id: 4, name: 'В зоне риска',       color: '#ef4444', criteria: 'Последний визит 30–60 дней, ранее визитов ≥ 3',            autoAssign: true },
    { id: 5, name: 'Потерянные',         color: '#6b7280', criteria: 'Последний визит > 90 дней',                                 autoAssign: true },
]);

function addCustomField() {
    openInlineEdit({
        title: 'Новое пользовательское поле',
        fields: [{ label: 'Название поля', value: '', type: 'text' }],
        onSave(fields) {
            if (fields[0].value.trim()) {
                clientCardFields.value.push({
                    key: 'custom_' + Date.now(),
                    icon: '✏️',
                    label: fields[0].value.trim(),
                    fieldType: 'Текст',
                    visible: true,
                    required: false,
                });
            }
        },
    });
}
function saveFieldConfig() {
    emit('save-settings', { clientCardFields: clientCardFields.value });
}
function createTag() {
    if (!newTagName.value) return;
    availableTags.value.push({ id: Date.now(), name: newTagName.value, color: newTagColor.value });
    newTagName.value = '';
}
function deleteTag(id) { availableTags.value = availableTags.value.filter(t => t.id !== id); }
function editSegmentRule(seg) {
    openInlineEdit({
        title: `Редактирование сегмента «${seg.name}»`,
        fields: [{ label: 'Критерии сегмента', value: seg.criteria, type: 'textarea' }],
        onSave(fields) {
            if (fields[0].value.trim()) seg.criteria = fields[0].value.trim();
        },
    });
}

/* ═══════════════════════════════════════════════════════════════════ */
/*  7. TRIGGER SETTINGS                                                */
/* ═══════════════════════════════════════════════════════════════════ */
const triggerSettings = ref([
    { id: 1, icon: '⏰', name: 'Напоминание о записи',      description: 'За 24 часа до визита',          active: true,  sentCount: 342, openRate: 78, conversionRate: 65 },
    { id: 2, icon: '🙏', name: 'Благодарность за визит',     description: 'Через 2 часа после визита',     active: true,  sentCount: 285, openRate: 82, conversionRate: 45 },
    { id: 3, icon: '🎂', name: 'Поздравление с ДР',          description: 'В день рождения клиента',       active: true,  sentCount: 48,  openRate: 90, conversionRate: 72 },
    { id: 4, icon: '💤', name: 'Реактивация «спящих»',       description: 'Через 30 дней без активности',   active: true,  sentCount: 67,  openRate: 45, conversionRate: 18 },
    { id: 5, icon: '📋', name: 'Предложение повторной записи', description: 'Через 2 недели после визита',  active: false, sentCount: 0,   openRate: 0,  conversionRate: 0 },
    { id: 6, icon: '⭐', name: 'Запрос отзыва',              description: 'Через 24 часа после визита',     active: true,  sentCount: 190, openRate: 55, conversionRate: 32 },
]);
const automationSchedule = reactive({
    reminderHoursBefore: 24,
    thanksHoursAfter: 2,
    reactivateDays: 30,
    birthdayDaysBefore: 0,
});

/* ═══════════════════════════════════════════════════════════════════ */
/*  GLOBAL SAVE                                                        */
/* ═══════════════════════════════════════════════════════════════════ */
function saveAllSettings() {
    const payload = {
        accessRoles: accessRoles.value,
        employeeAssignments: employeeAssignments.value,
        loyaltyLevels: loyaltyLevels.value,
        bonusRules: bonusRules.value,
        loyaltySettings,
        integrations: integrations.value,
        notificationSettings: notificationSettings.value,
        messageTemplates: messageTemplates.value,
        clientCardFields: clientCardFields.value,
        triggerSettings: triggerSettings.value,
        automationSchedule,
    };
    emit('save-settings', payload);
}
</script>
