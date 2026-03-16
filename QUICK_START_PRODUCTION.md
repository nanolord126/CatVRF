# QUICK START GUIDE - Production Deployment

## 🚀 5-Minute Setup

### Step 1: Database Migration
```bash
php artisan migrate
```
Creates 6 new tables:
- marketplace_products
- marketplace_services
- customer_accounts
- customer_reviews
- customer_wishlists
- customer_addresses

### Step 2: Seed Test Data
```bash
php artisan db:seed
```
Automatically runs:
1. DatabaseSeeder
2. → TenantMasterSeeder
3. → MarketplaceProductSeeder
4. → MarketplaceServiceSeeder
5. → CustomerAccountSeeder
6. → CustomerReviewSeeder
7. → CustomerWishlistSeeder
8. → CustomerAddressSeeder

### Step 3: Verify Resources
```bash
php artisan filament:show-resources
```

### Step 4: Access Panels
- **CRM Panel**: http://localhost/crm
  - Manage products/services for Marketplace
  
- **Marketplace Panel**: http://localhost/marketplace
  - Manage customer accounts, reviews, wishlists, addresses

---

## ✅ Verification Checklist

### Database
```bash
php artisan tinker
> DB::table('marketplace_products')->count() // Should be > 0
> DB::table('customer_accounts')->count() // Should be > 0
```

### Models
```bash
php artisan tinker
> App\Models\Tenants\MarketplaceProduct::all()
> App\Models\Tenants\CustomerAccount::all()
```

### Policies
```bash
php artisan tinker
> Gate::denies('view', App\Models\Tenants\MarketplaceProduct::first())
```

### Tenant Isolation
```bash
php artisan tinker
> tenant('grand-hotel');
> App\Models\Tenants\MarketplaceProduct::count()
> tenant('spa-beauty');
> App\Models\Tenants\MarketplaceProduct::count() // Different scope
```

---

## 🔧 Configuration Files

All properly configured:
- ✅ `app/Providers/AuthServiceProvider.php` - 6 Policies registered
- ✅ `app/Providers/FilamentServiceProvider.php` (or panel provider) - CRM discovery
- ✅ `database/seeders/DatabaseSeeder.php` - Calls TenantMasterSeeder
- ✅ `database/seeders/TenantMasterSeeder.php` - Calls all 6 seeders

---

## 📦 What's New

### Models (6)
- MarketplaceProduct
- MarketplaceService
- CustomerAccount
- CustomerReview
- CustomerWishlist
- CustomerAddress

### Policies (6)
- MarketplaceProductPolicy
- MarketplaceServicePolicy
- CustomerAccountPolicy
- CustomerReviewPolicy
- CustomerWishlistPolicy
- CustomerAddressPolicy

### Resources (6)
- MarketplaceProductResource
- MarketplaceServiceResource
- CustomerAccountResource
- CustomerReviewResource
- CustomerWishlistResource
- CustomerAddressResource

### Pages (21)
- 4 for MarketplaceProductResource
- 4 for MarketplaceServiceResource
- 4 for CustomerAccountResource
- 3 for CustomerReviewResource
- 3 for CustomerWishlistResource
- 3 for CustomerAddressResource

---

## 🎯 Key Features

### CRM Panel
✅ Product Management (for Marketplace publication)
✅ Service Management (for Marketplace publication)
✅ Bulk operations
✅ Search and filtering

### Marketplace Panel
✅ Customer Account Management
✅ Review Moderation
✅ Wishlist Management
✅ Address Management

### Multi-Tenancy
✅ Automatic tenant scoping
✅ Data isolation
✅ Correlation ID tracking
✅ Audit logging

---

## ⚠️ Important Notes

1. **Passwords**: Default seeded passwords are "password"
2. **Tenants**: Automatically created:
   - grand-hotel (domain: hotel.localhost)
   - spa-beauty (domain: beauty.localhost)
3. **Admin**: admin@hotelbeauty.crm
4. **Encoding**: All files UTF-8 CRLF

---

## 🆘 Troubleshooting

### Resources not showing in Filament?
```bash
php artisan cache:clear
php artisan filament:show-resources
```

### Database errors?
```bash
php artisan migrate:fresh
php artisan db:seed
```

### Tenant not set?
```bash
# In Filament context, tenant is automatically set
# In CLI: php artisan tinker
> tenant('grand-hotel'); // Set context
```

---

## 📚 Full Documentation

See `PRODUCTION_DEPLOYMENT_CHECKLIST.md` for comprehensive guide.

---

**Status**: ✅ Production Ready
**Last Updated**: March 15, 2026
