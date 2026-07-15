<?php

namespace App\View\Navigation;

class OwnerNavigation extends SidebarNavigation
{
    public function sections(): array
    {
        return [
            ['label' => 'Utama', 'items' => [
                $this->item('Dashboard', 'owner.panel', 'owner.panel', 'dashboard'),
            ]],
            ['label' => 'Penjualan', 'items' => [
                $this->item('Riwayat Transaksi', 'owner.transactions.index', 'owner.transactions.*', 'clipboard'),
                $this->item('Laporan Penjualan', 'owner.reports.sales', ['owner.reports.sales', 'owner.reports.sales.export'], 'chart'),
            ]],
            ['label' => 'Inventory', 'items' => [
                $this->item('Monitoring Stok', 'owner.stocks.index', 'owner.stocks.*', 'cube'),
                $this->item('Riwayat Stok', 'owner.stock-logs.index', 'owner.stock-logs.*', 'stock-log'),
                $this->item('Laporan Pemakaian', 'owner.reports.usage', ['owner.reports.usage', 'owner.reports.usage.export'], 'report'),
            ]],
            ['label' => 'Performa & Analisis', 'items' => [
                $this->item('Target Harian', 'owner.targets.index', 'owner.targets.*', 'clock'),
                $this->item('Analisis Menu', 'owner.analytics.menu', 'owner.analytics.menu', 'trend'),
            ]],
            ['label' => 'Keuangan', 'items' => [
                $this->item('Tutup Buku', 'owner.reports.closing.index', 'owner.reports.closing.*', 'lock'),
                $this->item('Pengeluaran Ops.', 'owner.reports.cashflow', ['owner.reports.cashflow', 'owner.reports.cashflow.export'], 'money'),
            ]],
            ['label' => 'Pengaturan', 'items' => [
                $this->item('Cabang Operasional', 'owner.branches.index', 'owner.branches.*', 'branch'),
                $this->item('Daftar Pengguna', 'owner.users.index', ['owner.users.index', 'owner.users.create', 'owner.users.edit', 'owner.users.reset.*'], 'users'),
                $this->item('Arsip Pengguna', 'owner.users.archive', 'owner.users.archive', 'archive'),
            ]],
        ];
    }
}
