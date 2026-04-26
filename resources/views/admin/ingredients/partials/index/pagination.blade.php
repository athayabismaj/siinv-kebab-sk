@if(method_exists($ingredients, 'hasPages') && $ingredients->hasPages())
<div class="mt-8">
    {{ $ingredients->links() }}
</div>
@endif
