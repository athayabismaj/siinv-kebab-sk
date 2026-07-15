<x-navigation.sidebar
    panel-label="Admin Panel"
    :sections="app(\App\View\Navigation\AdminNavigation::class)->sections()"
/>
