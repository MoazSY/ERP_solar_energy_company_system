# Activity Check Middleware Documentation

## Overview

تم إنشاء 4 middleware جديدة للتحقق من نشاط الحسابات والكيانات في النظام:

## Available Middleware

### 1. `check_company_manager_active`

**الغرض**: التحقق من أن حساب مدير الشركة نشط
**الحقل المستخدم**: `Activate_Account = 'active'`
**الـ Guard**: `company_manager`

### 2. `check_agency_manager_active`

**الغرض**: التحقق من أن حساب مدير الوكالة نشط
**الحقل المستخدم**: `Activate_Account = 'active'`
**الـ Guard**: `agency_manager`

### 3. `check_company_active`

**الغرض**: التحقق من وجود شركة نشطة مرتبطة بمدير الشركة
**الحقل المستخدم**: `company_status = 'active'`
**الـ Guard**: `company_manager`

### 4. `check_agency_active`

**الغرض**: التحقق من وجود وكالة نشطة مرتبطة بمدير الوكالة
**الحقل المستخدم**: `agency_status = 'active'`
**الـ Guard**: `agency_manager`

## Usage Examples

### في Routes (routes/api.php):

```php
// للتحقق من نشاط مدير الشركة فقط
Route::middleware(['check_auth', 'check_company_manager', 'check_company_manager_active'])
    ->group(function () {
        Route::get('/company/dashboard', [CompanyController::class, 'dashboard']);
    });

// للتحقق من نشاط مدير الشركة والشركة
Route::middleware(['check_auth', 'check_company_manager', 'check_company_manager_active', 'check_company_active'])
    ->group(function () {
        Route::post('/company/products', [CompanyController::class, 'addProduct']);
    });

// للتحقق من نشاط مدير الوكالة فقط
Route::middleware(['check_auth', 'check_Agency_manager', 'check_agency_manager_active'])
    ->group(function () {
        Route::get('/agency/dashboard', [AgencyController::class, 'dashboard']);
    });

// للتحقق من نشاط مدير الوكالة والوكالة
Route::middleware(['check_auth', 'check_Agency_manager', 'check_agency_manager_active', 'check_agency_active'])
    ->group(function () {
        Route::post('/agency/products', [AgencyController::class, 'addProduct']);
    });
```

## Response Codes

- **401**: Token expired or invalid authentication
- **403**: Account or entity is not active
- **200**: Success - all checks passed

## Error Messages

- `"Token has expired"` - التوكن منتهي الصلاحية
- `"Unauthorized"` - غير مصرح له
- `"Company manager account is not active"` - حساب مدير الشركة غير نشط
- `"Agency manager account is not active"` - حساب مدير الوكالة غير نشط
- `"No active company found for this manager"` - لا توجد شركة نشطة لهذا المدير
- `"No active agency found for this manager"` - لا توجد وكالة نشطة لهذا المدير

## Implementation Notes

- جميع الـ middleware تتحقق من صحة التوكن أولاً
- التحقق من النشاط يتم بعد التحقق من المصادقة
- يمكن استخدام الـ middleware بشكل منفصل أو مجتمع
- ترتيب الـ middleware مهم للأداء الأمثل
