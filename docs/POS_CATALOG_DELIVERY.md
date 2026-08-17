# POS Catalog Delivery

Endpoint katalog sudah membatasi payload dengan pagination varian dan gambar
kartu dinormalisasi ke WebP berukuran maksimal 640 piksel jika GD/WebP tersedia.

Repository ini tidak menyimpan konfigurasi Nginx, Apache, CDN, atau reverse
proxy production. Karena itu HTTP compression tidak diaktifkan dari kode
Laravel. Aktifkan gzip atau Brotli pada proxy/server yang benar-benar melayani
traffic production untuk response JSON, CSS, dan JavaScript.

Checklist deployment:

1. Pastikan client mengirim `Accept-Encoding: gzip` atau `br` (OkHttp
   melakukannya secara otomatis selama header tersebut tidak dioverride).
2. Pastikan response `/api/menus` memiliki `Content-Encoding` ketika payload
   memenuhi batas minimum compression server.
3. Tambahkan `Vary: Accept-Encoding` pada response terkompresi.
4. Jangan melakukan compression ulang pada gambar WebP.
5. Ukur ukuran transfer dan latency melalui proxy production; pengujian lokal
   Laravel tidak mewakili perilaku compression server.
