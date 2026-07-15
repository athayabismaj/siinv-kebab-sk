# Fase 5C - Android End-to-End QA

## Cakupan

- Level 1: contract test Laravel untuk auth, profil/branch, menu, payment,
  checkout, stock-session shape, transaksi kosong, receipt, dan unauthorized.
- Level 2: unit/integration client Android untuk DTO, mapper, persistence,
  repository, ViewModel, pagination, receipt formatter, error mapper, logout,
  checkout, dan stok harian.
- Level 3: hanya dapat dinilai dengan ADB dan emulator/device lokal.
- Level 4: printer fisik ditunda ke Fase 5D.

## Perubahan perilaku terverifikasi

1. Logout memanggil `POST /api/auth/logout`, lalu membersihkan session lokal
   pada sukses, 401, maupun kegagalan jaringan. Kegagalan jaringan berarti token
   backend mungkin belum tercabut, tetapi device tidak tetap login.
2. Branch dari login/profil dipetakan ke `AuthSession` dan disimpan bersama
   token. Branch nullable tetap aman; logout menghapus branch.
3. Deklarasi lama `POST /sessions/{id}/close` beserta rantai kode mati dihapus.
   Alur aktif memakai `POST /daily-stock-sessions/close`.
4. Checkout tetap mempunyai guard double-submit dan tidak retry otomatis.
5. Paginator riwayat membaca envelope backend tanpa mengubah format.
6. Formatter struk diuji terhadap fixture backend, nama panjang, nominal besar,
   dan karakter non-ASCII tanpa printer fisik.

## Status level

Status akhir diisi dari hasil command regresi pada Fase 5C:

| Level | Target | Status |
| --- | --- | --- |
| 1 | Laravel contract | PASSED - 10 contract test / 121 assertion; full suite 188 / 1.086 |
| 2 | Android client integration | PASSED - 183 unit test, lint dan assemble debug |
| 3 | ADB local E2E | NOT RUN - ADB tersedia, tidak ada emulator/device terhubung |
| 4 | Printer/device fisik | DEFERRED ke Fase 5D |

Regresi backend dijalankan dua kali dengan hasil identik `188 passed`, `1086
assertions`, `0 failed`. PostgreSQL disposable lulus `11 passed`, `58
assertions`, dan database fixture dibersihkan pada teardown. Regresi Android
run kedua dipaksa dengan `--rerun-tasks` agar tidak hanya memakai cache Gradle.

## Risiko dan batasan

- Timeout setelah server memproses checkout dapat menghasilkan transaksi ambigu;
  periksa riwayat sebelum submit ulang.
- Revocation backend tidak dapat dijamin ketika logout terjadi tanpa jaringan.
- Unit test tidak membuktikan process death, jaringan nyata, variasi device,
  printer fisik, production base URL, signing, atau release APK.
- Nama item printer dipotong mengikuti lebar 32 karakter yang sudah ada; Fase 5C
  tidak mengubah layout struk.
- Level 3 tidak boleh disebut lulus tanpa emulator/device yang benar-benar aktif.
