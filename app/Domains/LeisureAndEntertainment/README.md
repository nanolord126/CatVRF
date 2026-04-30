# Leisure & Entertainment Super-Vertical

**Purpose:** Досуг и Развлечения - билетная касса, мероприятия, концерты, кино, выставки, тусовки.

**Structure:**
```
LeisureAndEntertainment/
├── Domain/                    # Доменная логика
├── Services/
│   └── LeisureAndEntertainmentService.php  # Основной сервис
└── AI/
    └── LeisureAndEntertainmentConstructorService.php  # AI-рекомендации
```

**Sub-Verticals (конфигурация, не отдельные папки):**
- Tickets (билетная касса) - основной сервис
- Concerts (концерты)
- Cinema (кино)
- Exhibitions (выставки)
- Parties & Тусовки
- Leisure Activities (активности для досуга)

**Service Responsibilities:**
- `LeisureAndEntertainmentService`: Орchestration всех типов развлечений
- `LeisureAndEntertainmentConstructorService`: AI рекомендации по досугу

**Migration Status:**
- [x] Directory structure created
- [x] Super-vertical service created
- [x] AI constructor service created
- [ ] Sub-vertical logic implementation
- [ ] Route files updated
- [ ] Service providers updated

**Integration Points:**
- Technical domains: Geo (location), Payment, Cart, FraudDetection
- Common domain: Product (для билетов), Category (типы мероприятий)
