# Product Import Specification

## Overview
Bulk import products via CSV/Excel file. Available through Filament UI and REST API.

## CSV Template Format

### Columns (required)
| Column | Type | Required | Description |
|--------|------|----------|-------------|
| `category` | string | Yes | Category name. Auto-created if not exists. |
| `name` | string | Yes | Product name. |
| `sku` | string | No | Stock Keeping Unit code. |
| `unit` | string | No | Unit of measure (e.g., PCS, KG, LITER). |
| `stock` | integer | No | Initial stock quantity. Default: 0. |
| `initial_price` | number | No | Cost/wholesale price. Default: 0. |
| `selling_price` | number | No | Retail price. Default: 0. |
| `type` | string | No | `product` (default) or `service`. |
| `barcode` | string | No | Primary barcode (EAN/UPC). |
| `other_price` | string | No | Format: `price,unit,stock` (e.g., `8000,BOX,10`). |

### Example CSV
```csv
category,name,sku,unit,stock,initial_price,selling_price,type,barcode,other_price
Makanan,Nasi Goreng Spesial,NG-001,PCS,50,8000,12000,product,8901234560001,
Minuman,Es Teh Manis,ET-001,PCS,100,2000,4000,product,,8000,LITER,10
```

## API Endpoint

### POST `/api/master/product/import`
- **Auth:** Bearer token (Sanctum)
- **Permission:** `create product`
- **Content-Type:** `multipart/form-data`
- **File field:** `file`
- **Accepted types:** CSV, XLSX
- **Max size:** Configurable via `upload.livewire_max_size`

#### Request
```bash
curl -X POST "https://your-domain.com/api/master/product/import" \
  -H "Authorization: Bearer {token}" \
  -F "file=@products.csv"
```

#### Response (success)
```json
{
  "success": true,
  "data": {
    "imported": 100
  },
  "message": "Import completed. 100 products imported."
}
```

#### Response (error)
```json
{
  "success": false,
  "message": "Import failed: [error details]"
}
```

## Filament UI

### Location
Products page → Header action buttons:
1. **Download Template** — downloads CSV template with headers + sample row
2. **Import product** — file upload modal (drag & drop or click)

### Feature flag
Controlled by `ProductImport` feature flag (`App\Features\ProductImport`).

## Validation Rules
- File is required
- Must be a file upload (not URL)
- Accepted MIME types: `application/vnd.ms-excel`, `text/csv`
- Max file size: `config('upload.livewire_max_size')`

## Business Logic

### Category handling
- Categories are auto-created via `Category::firstOrCreate()`
- If category exists for the tenant, it's reused
- `tenant_id` is explicitly set on creation

### Product creation
- `tenant_id` is explicitly set from `TenantContext::get()`
- Stock records are NOT auto-created (only product master data)
- Barcodes are created if `barcode` column is provided
- `other_price` creates `PriceUnit` records

### Tenant isolation
- Products, categories, and barcodes are scoped by `tenant_id`
- Import must run within tenant context (Filament UI or authenticated API)

## Known Gotchas
1. **TenantContext required:** Running import via `php artisan tinker` without setting `TenantContext` results in empty `tenant_id` — products become invisible in cashier
2. **Duplicate SKUs:** No unique constraint on SKU — duplicates are allowed
3. **No image import:** CSV import does not handle `hero_images` — use Filament form for image uploads
4. **Stock table:** Import only creates product master data. Stock records in `stocks` table must be created separately (via Purchasing or Stock Adjustment)
