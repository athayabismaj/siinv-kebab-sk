<x-navigation.sidebar
    panel-label="Super Admin"
    variant="developer"
    :sections="app(\App\View\Navigation\DeveloperNavigation::class)->sections()"
/>
