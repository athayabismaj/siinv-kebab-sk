@if(method_exists($categories, 'hasPages') && $categories->hasPages())
<div class="pt-4">
    {{ $categories->links() }}
</div>
@endif
