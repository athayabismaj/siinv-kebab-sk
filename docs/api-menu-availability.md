# API Contract: Menu Variant Availability

Dokumen ini menjadi kontrak stabil untuk client mobile terkait availability varian.

## Endpoint

- `GET /api/menus`
- `GET /api/menus/unavailable-variants` (owner/admin monitoring)

`GET /api/menus` menerima parameter:

- `category_id` (opsional)
- `search` (opsional, mencari nama menu atau varian)
- `page` (default `1`)
- `per_page` (default `20`, maksimum `50`)

Pagination dilakukan langsung terhadap `menu_variants`. Struktur
`menus[].variants[]` dipertahankan agar client lama tetap kompatibel.

## Rules (Server-side Source of Truth)

- `is_available` dihitung dari kombinasi:
  - flag manual varian,
  - resep varian (`menu_variant_ingredients`),
  - sesi stok harian kasir yang `open`,
  - kecukupan `remaining_qty` bahan pada sesi aktif.
- Tanggal sesi selalu dihitung pada timezone `Asia/Jakarta`.
- Checkout melakukan validasi ulang (hard guard), tidak hanya percaya hasil UI.

## Variant Payload

Setiap varian pada `GET /api/menus` memiliki field:

- `id` (int)
- `name` (string)
- `image_url` (string|null, URL absolut gambar varian untuk kartu menu POS)
- `price` (number)
- `is_available` (bool)
- `unavailable_reason` (string|null)
- `sort_order` (int)

Contoh:

```json
{
  "id": 12,
  "name": "Jumbo",
  "image_url": "https://example.com/media/menu-variants/abc123.webp",
  "price": 28000,
  "is_available": false,
  "unavailable_reason": "INSUFFICIENT_STOCK",
  "sort_order": 1
}
```

`required_ingredients` sengaja tidak dikirim pada katalog POS untuk
memperkecil payload. Field tersebut tetap tersedia pada endpoint diagnostik
`/api/menus/unavailable-variants`, sedangkan resep tetap digunakan oleh
backend untuk availability dan validasi checkout.

Response katalog juga memuat:

```json
{
  "categories": [{"id": 1, "name": "Kebab"}],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 93,
    "has_more": true
  }
}
```

## unavailable_reason Values

- `NO_SESSION`
- `NO_RECIPE`
- `INGREDIENT_NOT_TRANSFERRED`
- `INSUFFICIENT_STOCK`
- `MANUAL_DISABLED`
