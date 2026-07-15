<x-flash-alerts
    :container-class="$class ?? 'mb-5 w-full space-y-2'"
    :auto-dismiss="$autoDismiss ?? true"
    :include-errors="$includeErrors ?? false"
    :position="$position ?? 'inline'"
    :error-bag="$errors ?? null"
/>
