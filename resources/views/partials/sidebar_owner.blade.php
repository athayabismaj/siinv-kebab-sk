<x-navigation.sidebar
    panel-label="Owner Panel"
    :sections="app(\App\View\Navigation\OwnerNavigation::class)->sections()"
/>
