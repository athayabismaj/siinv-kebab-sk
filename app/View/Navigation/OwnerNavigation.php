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
            ['label' => 'Transaksi', 'items' => [
                $this->item('Riwayat Transaksi', 'owner.transactions.index', 'owner.transactions.*', 'clipboard'),
            ]],
            ['label' => 'Inventory', 'items' => [
                $this->item('Monitoring Stok', 'owner.stocks.index', 'owner.stocks.*', 'cube'),
                $this->item('Riwayat Stok', 'owner.stock-logs.index', 'owner.stock-logs.*', 'stock-log'),
            ]],
            ['label' => 'Laporan & Keuangan', 'items' => [
                $this->item('Laporan Penjualan', 'owner.reports.sales', ['owner.reports.sales', 'owner.reports.sales.export'], 'chart'),
                $this->item('Laporan Pemakaian', 'owner.reports.usage', ['owner.reports.usage', 'owner.reports.usage.export'], 'report'),
                $this->item('Pengeluaran Ops.', 'owner.reports.cashflow', ['owner.reports.cashflow', 'owner.reports.cashflow.export'], 'money'),
                $this->item('Tutup Buku', 'owner.reports.closing.index', 'owner.reports.closing.*', 'lock'),
            ]],
            ['label' => 'Performa & Analisis', 'items' => [
                $this->item('Analisis Menu', 'owner.analytics.menu', 'owner.analytics.menu', 'trend'),
            ]],
            ['label' => 'Manajemen', 'items' => [
                $this->item('Cabang Operasional', 'owner.branches.index', 'owner.branches.*', 'branch'),
                $this->item('Daftar Pengguna', 'owner.users.index', ['owner.users.index', 'owner.users.create', 'owner.users.edit', 'owner.users.reset.*', 'owner.users.archive'], 'users'),
            ]],
        ];
    }
}
