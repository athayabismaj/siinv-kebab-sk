<?php

namespace App\View\Navigation;

abstract class SidebarNavigation
{
    /**
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, active: bool, icon: string}>}>
     */
    abstract public function sections(): array;

    /**
     * @param  string|array<int, string>|null  $activePatterns
     * @return array{label: string, route: string, active: bool, icon: string}
     */
    protected function item(string $label, string $routeName, string|array|null $activePatterns, string $icon): array
    {
        $patterns = $activePatterns === null ? [] : (array) $activePatterns;

        return [
            'label' => $label,
            'route' => route($routeName),
            'active' => $patterns !== [] && request()->routeIs(...$patterns),
            'icon' => $icon,
        ];
    }
}
