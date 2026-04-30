# Common Domain

**Purpose:** Shared product logic across all super-verticals.

**Contains:**
- Base product entities (Product, Category, Brand)
- Shared value objects (Price, SKU, Quantity)
- Common repositories interfaces
- Shared DTOs
- Base services for product operations

**Architecture:**
```
Common/
├── Domain/
│   ├── Entities/
│   │   ├── Product.php
│   │   ├── Category.php
│   │   └── Brand.php
│   ├── ValueObjects/
│   │   ├── Price.php
│   │   ├── SKU.php
│   │   └── Quantity.php
│   ├── Repositories/
│   │   ├── ProductRepositoryInterface.php
│   │   └── CategoryRepositoryInterface.php
│   └── Services/
│       ├── ProductService.php
│       └── CategoryService.php
├── Infrastructure/
│   ├── Models/
│   │   ├── Product.php
│   │   └── Category.php
│   └── Repositories/
│       ├── ProductRepository.php
│       └── CategoryRepository.php
└── Application/
    ├── DTOs/
    │   ├── ProductDTO.php
    │   └── CategoryDTO.php
    └── Services/
        └── ProductApplicationService.php
```

**Usage by Super-Verticals:**
Each super-vertical extends common entities and services for DRY compliance.
