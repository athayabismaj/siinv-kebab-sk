<?php

namespace App\View\Navigation;

class DeveloperNavigation extends SidebarNavigation
{
    public function sections(): array
    {
        return [
            ['label' => 'Ringkasan', 'items' => [
                $this->item('Dashboard', 'developer.panel', 'developer.panel', 'dashboard'),
            ]],
            ['label' => 'Kelola Sistem', 'items' => [
                $this->item('Manajemen Owner', 'developer.owners.index', 'developer.owners.*', 'owner-users'),
                $this->item('Manajemen Backup', 'developer.backups.index', 'developer.backups.*', 'database'),
            ]],
            ['label' => 'Navigasi Pintas', 'items' => [
                $this->item('Panel Admin', 'admin.panel', null, 'settings'),
                $this->item('Panel Owner', 'owner.panel', null, 'building'),
            ]],
        ];
    }
}
