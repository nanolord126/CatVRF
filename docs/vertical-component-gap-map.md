# Карта компонентных пробелов (Business UI)

Дата: 2026-04-09  
Источник: фактическое сканирование `resources/js/Components/Business` против `app/Domains/*`

## Метод анализа

Проверка выполнялась по двум критериям:

1. Для каждой вертикали из `app/Domains/*` должна существовать папка `resources/js/Components/Business/{Vertical}`.
2. Внутри вертикальной папки должен быть минимум 1 `.vue`-компонент.

Дополнительно зафиксированы:

- generic-компоненты уровня бизнес-кабинета;
- наличие/отсутствие `resources/js/Pages/Business`.

## Сводка

- Всего вертикалей в домене: **72**
- Вертикальных папок компонентов в Business UI: **1**
- Generic-компонентов верхнего уровня: **15**
- Вертикалей без собственной компонентной папки: **71**
- Компонентная папка найдена только для: **Beauty** (28 компонентов)

## Что уже есть

### Generic Business components (15)

- `AIConstructors.vue`
- `AnalyticsPanel.vue`
- `B2BPanel.vue`
- `BusinessDashboard.vue`
- `BusinessProfile.vue`
- `ClientsCRM.vue`
- `DeliveryTracking.vue`
- `EmployeeManagement.vue`
- `IntegrationsPanel.vue`
- `MarketingPanel.vue`
- `OrdersManagement.vue`
- `ProductsCatalog.vue`
- `SettingsPage.vue`
- `WalletFinance.vue`
- `WarehouseInventory.vue`

### Beauty components (28)

- `BeautyAutomation.vue`
- `BeautyBloggerCard.vue`
- `BeautyCalendar.vue`
- `BeautyCampaignCard.vue`
- `BeautyChat.vue`
- `BeautyClientCard.vue`
- `BeautyCRM.vue`
- `BeautyCRMAnalytics.vue`
- `BeautyCRMHistory.vue`
- `BeautyCRMSettings.vue`
- `BeautyFinances.vue`
- `BeautyInteractions.vue`
- `BeautyInventory.vue`
- `BeautyLoyalty.vue`
- `BeautyMasterCard.vue`
- `BeautyNotifications.vue`
- `BeautyPageStats.vue`
- `BeautyPanel.vue`
- `BeautyPublicPages.vue`
- `BeautyReports.vue`
- `BeautyReviews.vue`
- `BeautySalonCard.vue`
- `BeautySegmentation.vue`
- `BeautySocialCard.vue`
- `BeautySourceDetailCard.vue`
- `BeautyStaff.vue`
- `BeautyTryOn.vue`
- `BeautyVideoCard.vue`

## Недостающие вертикальные компонентные папки (71)

- Advertising
- AI
- Analytics
- Art
- Auto
- BooksAndLiterature
- CarRental
- CleaningServices
- Collectibles
- Common
- Communication
- Confectionery
- ConstructionAndRepair
- Consulting
- Content
- CRM
- Delivery
- DemandForecast
- Education
- Electronics
- EventPlanning
- FarmDirect
- Fashion
- Finances
- Fitness
- Flowers
- Food
- FraudML
- Freelance
- Furniture
- Gardening
- Geo
- GeoLogistics
- GroceryAndDelivery
- HobbyAndCraft
- HomeServices
- Hotels
- HouseholdGoods
- Insurance
- Inventory
- Legal
- Logistics
- Luxury
- Marketplace
- MeatShops
- Medical
- MusicAndInstruments
- OfficeCatering
- PartySupplies
- Payment
- PersonalDevelopment
- Pet
- Pharmacy
- Photography
- PromoCampaigns
- RealEstate
- Recommendation
- Referral
- ShortTermRentals
- Sports
- SportsNutrition
- Staff
- Taxi
- Tickets
- ToysAndGames
- Travel
- VeganProducts
- VerticalName
- Veterinary
- Wallet
- WeddingPlanning

## Отдельно: пробел в Pages-слое

- Папка `resources/js/Pages/Business` отсутствует.
- Это блокирует явное разделение Route Pages ↔ UI Components по вертикалям.

## Рекомендуемый порядок закрытия

### P0 (сначала)

- Logistics, Payment, Wallet, Inventory, CRM, Communication, Delivery, FraudML

### P1

- Auto, Taxi, Food, Furniture, RealEstate, Medical, Travel, Hotels, Fashion, Fitness

### P2

- Остальные вертикали в алфавитном порядке.

## Минимальный шаблон для каждой новой вертикали UI

Рекомендуемый минимум:

- `resources/js/Components/Business/{Vertical}/{Vertical}Panel.vue`
- `resources/js/Components/Business/{Vertical}/{Vertical}Dashboard.vue`
- `resources/js/Pages/Business/{Vertical}Page.vue`

Примечание: после создания компонента вертикаль удаляется из раздела «Недостающие ...» и обновляется сводка.
