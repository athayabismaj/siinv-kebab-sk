<?php

namespace App\View\Navigation;

class AdminNavigation extends SidebarNavigation
{
    public function sections(): array
    {
        return [
            ['label' => 'Utama', 'items' => [
                $this->item('Dashboard', 'admin.panel', 'admin.panel', 'dashboard'),
            ]],
            ['label' => 'Penjualan', 'items' => [
                $this->item('Riwayat Transaksi', 'admin.transactions.index', 'admin.transactions.*', 'cart'),
            ]],
            ['label' => 'Produk & Menu', 'items' => [
                $this->item('Kategori Menu', 'admin.menu-categories.index', 'admin.menu-categories.*', 'menu'),
                $this->item('Manajemen Menu', 'admin.menus.index', ['admin.menus.index', 'admin.menus.create', 'admin.menus.edit', 'admin.menu-variants.*'], 'clipboard'),
                $this->item('Manajemen Resep', 'admin.recipes.index', 'admin.recipes.*', 'book'),
            ]],
            ['label' => 'Inventory & Stok', 'items' => [
                $this->item('Kategori Bahan', 'admin.ingredient-categories.index', 'admin.ingredient-categories.*', 'tag'),
                $this->item('Manajemen Bahan', 'admin.ingredients.index', ['admin.ingredients.index', 'admin.ingredients.create', 'admin.ingredients.edit'], 'archive-stack'),
                $this->item('Sesi Stok Harian', 'admin.daily-stocks.index', 'admin.daily-stocks.*', 'cube'),
                $this->item('Restok & Penyesuaian', 'admin.stocks.index', ['admin.stocks.index', 'admin.stocks.restock.*', 'admin.stocks.adjust.*'], 'cube'),
                $this->item('Riwayat Stok', 'admin.stocks.logs', 'admin.stocks.logs', 'clipboard'),
            ]],
            ['label' => 'Laporan', 'items' => [
                $this->item('Laporan Pemakaian', 'admin.reports.usage', ['admin.reports.usage', 'admin.reports.usage.export'], 'report'),
                $this->item('Laporan Stok Harian', 'admin.reports.daily-stock', ['admin.reports.daily-stock', 'admin.reports.daily-stock.export'], 'cube'),
                $this->item('Pengeluaran Ops.', 'admin.reports.cashflow', ['admin.reports.cashflow', 'admin.reports.cashflow.*'], 'money'),
            ]],
            ['label' => 'Arsip Data', 'items' => [
                $this->item('Daftar Arsip', 'admin.ingredients.archive', ['admin.ingredients.archive', 'admin.menus.archive'], 'archive'),
            ]],
        ];
    }
}
