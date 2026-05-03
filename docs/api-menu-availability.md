# API Contract: Menu Variant Availability

Dokumen ini menjadi kontrak stabil untuk client mobile terkait availability varian.

## Endpoint

- `GET /api/menus`
- `GET /api/menus/unavailable-variants` (owner/admin monitoring)

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
- `price` (number)
- `is_available` (bool)
- `unavailable_reason` (string|null)
- `required_ingredients` (array, opsional tapi disediakan)
- `sort_order` (int)

Contoh:

```json
{
  "id": 12,
  "name": "Jumbo",
  "price": 28000,
  "is_available": false,
  "unavailable_reason": "INSUFFICIENT_STOCK",
  "required_ingredients": [
    {
      "ingredient_id": 2,
      "ingredient_name": "Roti Burger",
      "required_qty": 1,
      "remaining_qty": 0
    }
  ],
  "sort_order": 1
}
```

## unavailable_reason Values

- `NO_SESSION`
- `NO_RECIPE`
- `INGREDIENT_NOT_TRANSFERRED`
- `INSUFFICIENT_STOCK`
- `MANUAL_DISABLED`

