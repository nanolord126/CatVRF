# Pharmacy Super-Vertical

**Purpose:** Аптеки, лекарственные средства, медтовары с медицинским compliance.

**Compliance Requirements:**
- 152-ФЗ (персональные данные)
- ФЗ-323 (об основах охраны здоровья)
- Лицензирование фармацевтической деятельности
- Контроль рецептурных препаратов

**Structure:**
```
Pharmacy/
├── SubVerticals/
│   └── Pharmacy/              # Основной бизнес аптек
├── Domain/                    # Доменная логика
├── Services/
│   └── PharmacyService.php    # Основной сервис (существует)
├── AI/
│   └── PharmacyConstructorService.php  # AI-рекомендации (создать)
├── Models/                    # Eloquent модели
├── Routes/                    # API routes
└── Filament/                  # Admin interface
```

**Key Features:**
- Поиск лекарств по МНН и торговым названиям
- Контроль рецептурных препаратов
- Проверка взаимодействий лекарств
- Управление запасами
- Интеграция с Geo для поиска ближайших аптек
- Fraud detection для заказов

**Migration Status:**
- [x] Pharmacy module exists (full implementation)
- [x] Config updated to separate super-vertical
- [x] Sub-vertical removed from Beauty & Personal Care
- [ ] AI constructor service created
- [ ] Orchestrator service created
- [ ] README updated

**Integration Points:**
- Technical domains: Geo (location), Payment, Wallet, FraudDetection, Inventory
- Medical compliance: Prescription validation, drug interaction checking
