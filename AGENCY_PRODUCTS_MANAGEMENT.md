# إدارة منتجات الوكالات

## نظرة عامة

تم إضافة وظائف لإدارة منتجات الوكالات مع صلاحيات مختلفة لمدير الشركة ومدير الوكالة.

## الوظائف المتاحة

### لمدير الشركة (Company Manager)

#### عرض منتجات وكالة معينة

```
GET /api/show_agency_products/{agency_id}
```

**المتطلبات:**

- يجب أن يكون المدير مسجل دخول
- يجب أن تكون الوكالة موجودة

**الاستجابة:**

```json
{
    "message": "Agency products retrieved successfully",
    "products": [
        {
            "id": 1,
            "product_name": "Solar Panel XYZ",
            "product_type": "solar_panel",
            "price": 500,
            "currency": "USD"
            // ... باقي بيانات المنتج
        }
    ]
}
```

### لمدير الوكالة (Agency Manager)

#### عرض منتجات الوكالة

```
GET /api/show_agency_products
```

**المتطلبات:**

- يجب أن يكون المدير مسجل دخول
- يجب أن تكون الوكالة نشطة

**الاستجابة:**

```json
{
    "message": "Agency products retrieved successfully",
    "products": [
        {
            "id": 1,
            "product_name": "Solar Panel XYZ",
            "product_type": "solar_panel",
            "price": 500,
            "currency": "USD"
            // ... باقي بيانات المنتج
        }
    ]
}
```

#### تحديث منتج الوكالة

```
POST /api/update_agency_product/{product_id}
```

**المتطلبات:**

- يجب أن يكون المدير مسجل دخول
- يجب أن تكون الوكالة نشطة
- يجب أن تكون الوكالة مشتركة في سياسة اشتراك
- يجب أن يكون المنتج ملك للوكالة

**البيانات المرسلة:**

```json
{
    "product_name": "Updated Product Name",
    "price": 600,
    "currency": "USD",
    "product_image": "file"
}
```

**الاستجابة:**

```json
{
  "message": "Product updated successfully",
  "product": { ... },
  "product_image": "path/to/image"
}
```

#### حذف منتج الوكالة

```
DELETE /api/delete_agency_product/{product_id}
```

**المتطلبات:**

- يجب أن يكون المدير مسجل دخول
- يجب أن تكون الوكالة نشطة
- يجب أن تكون الوكالة مشتركة في سياسة اشتراك
- يجب أن يكون المنتج ملك للوكالة

**الاستجابة:**

```json
{
    "message": "Product deleted successfully"
}
```

## الصلاحيات والمتطلبات

### Middlewares المستخدمة:

#### لمدير الشركة:

- `check_auth` - التحقق من المصادقة
- `check_company_manager` - التحقق من نوع المستخدم
- `check_company_manager_active` - التحقق من نشاط المدير
- `check_company_active` - التحقق من نشاط الشركة

#### لمدير الوكالة:

- `check_auth` - التحقق من المصادقة
- `check_Agency_manager` - التحقق من نوع المستخدم
- `check_agency_manager_active` - التحقق من نشاط المدير
- `check_agency_active` - التحقق من نشاط الوكالة
- `check_agency_subscription` - التحقق من الاشتراك (للتعديل والحذف)

## رموز الاستجابة

- `200` - نجح الطلب
- `400` - خطأ في البيانات المرسلة
- `401` - غير مصرح له
- `403` - الحساب أو الكيان غير نشط
- `404` - المنتج أو الوكالة غير موجودة

## ملاحظات مهمة

1. **الأمان**: جميع العمليات محمية بـ middlewares متعددة لضمان الأمان
2. **الملكية**: مدير الوكالة يمكنه فقط تعديل وحذف منتجات وكالته
3. **النشاط**: يتم التحقق من نشاط المدير والوكالة قبل أي عملية
4. **الاشتراك**: عمليات التعديل والحذف تتطلب اشتراك نشط
5. **الصور**: يتم التعامل مع رفع الصور تلقائياً في مجلد `storage/app/public/products`
