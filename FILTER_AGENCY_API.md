# Filter Agency API Documentation

## Endpoint

```
GET /api/company_manager/filter_agency
```

## Description

يسمح هذا الـ endpoint لمدير الشركة الشمسية بفلترة الوكالات بناءً على معايير متعددة تشمل اسم الوكالة، الموقع، والمنتجات.

## Authentication

يجب أن يكون المستخدم مصادقاً كـ `company_manager` باستخدام Sanctum token.

## Request Parameters

### Basic Filters

- `agency_name` (string, optional): اسم الوكالة (بحث جزئي)

### Location Filters

- `governorate_id` (integer, optional): معرف المحافظة
- `area_id` (integer, optional): معرف المنطقة
- `neighborhood_id` (integer, optional): معرف الحي

**ملاحظة**: إذا تم تحديد `neighborhood_id`، يجب تحديد `area_id` و `governorate_id`. إذا تم تحديد `area_id`، يجب تحديد `governorate_id`.

### Product Filters

- `product_type` (string, optional): نوع المنتج (`solar_panel`, `inverter`, `battery`, `accessory`)
- `product_brand` (string, optional): ماركة المنتج
- `product_name` (string, optional): اسم المنتج
- `model_number` (string, optional): رقم الموديل

**ملاحظة**: إذا تم تحديد `model_number`، يجب تحديد `product_name`. إذا تم تحديد أي من تفاصيل المنتج، يجب تحديد `product_type`.

### Price Filters

- `currency` (string, optional): العملة (`USD`, `SY`)
- `product_price_min` (numeric, optional): السعر الأدنى
- `product_price_max` (numeric, optional): السعر الأعلى

**ملاحظة**: يتطلب فلاتر السعر تحديد معلومات المنتج الأساسية.

### Discount Filters

- `disscount_type` (string, optional): نوع الخصم (`percentage`, `fixed`)
- `disscount_value_min` (numeric, optional): قيمة الخصم الدنيا
- `disscount_value_max` (numeric, optional): قيمة الخصم العليا

**ملاحظة**: يتطلب فلاتر الخصم تحديد معلومات المنتج الأساسية ونوع الخصم.

## Response

### Success Response (200)

```json
{
    "message": "Agencies filtered successfully",
    "agencies": [
        {
            "id": 1,
            "agency_name": "Example Agency",
            "agency_email": "agency@example.com",
            "addresses": [
                {
                    "governorate": { "name": "Damascus" },
                    "area": { "name": "Center" },
                    "neighborhood": { "name": "Downtown" }
                }
            ],
            "products": [
                {
                    "product_name": "Solar Panel X1",
                    "product_type": "solar_panel",
                    "product_brand": "Brand A",
                    "model_number": "SP-100",
                    "price": 500,
                    "currency": "USD",
                    "disscount_type": "percentage",
                    "disscount_value": 10
                }
            ]
        }
    ]
}
```

### Error Response (404)

```json
{
    "message": "No agencies found matching the criteria"
}
```

### Validation Error Response (422)

```json
{
    "message": {
        "location": ["يجب تحديد المحافظة والمنطقة عند تحديد الحي"],
        "product": ["يجب تحديد نوع المنتج عند تحديد تفاصيل المنتج"]
    }
}
```

## Examples

### فلترة بالاسم فقط

```
GET /api/company_manager/filter_agency?agency_name=Tech
```

### فلترة بالموقع

```
GET /api/company_manager/filter_agency?governorate_id=1&area_id=5
```

### فلترة بالمنتج والسعر

```
GET /api/company_manager/filter_agency?product_type=solar_panel&product_brand=SunPower&currency=USD&product_price_min=100&product_price_max=1000
```

### فلترة بالخصم

```
GET /api/company_manager/filter_agency?product_type=inverter&disscount_type=percentage&disscount_value_min=5&disscount_value_max=20
```

## Implementation Details

- **Controller**: `SolarCompanyManager@filter_agency`
- **Service**: `SolarCompanyManagerService@filter_agency`
- **Repository**: `SolarCompanyManagerRepository@filter_agency`
- **Validation**: `FilterAgencyRequest`

يستخدم النظام علاقات Eloquent morph لربط الوكالات بالعناوين والمنتجات، مما يسمح بفلترة مرنة وفعالة.
