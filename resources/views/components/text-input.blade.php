@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-200 focus:border-primary focus:ring-primary rounded-lg shadow-sm text-sm']) }}>
