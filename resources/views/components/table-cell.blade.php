@props(['number' => false])

<td {{ $attributes->merge(['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300' . ($number ? ' text-right' : '')]) }}>
  {{ $slot }}
</td>
