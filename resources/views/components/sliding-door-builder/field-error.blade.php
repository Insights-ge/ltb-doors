@props(['field'])

<p
    id="{{ $field }}-error"
    x-show="fieldError('{{ $field }}')"
    x-text="fieldError('{{ $field }}')"
    x-cloak
    role="alert"
    class="mt-1 text-sm text-danger-600 dark:text-danger-400"
></p>
