@php
/**
 *  @var array<string, string> $translations Locale codes to fully-qualified URLs.
 *  @var string $current Current locale code.
 */
@endphp
<select {{ $attributes->merge(['onchange' => 'window.location = this.value']) }}>
    @foreach ($translations as $locale => $href)
    <option value="{{ $href }}" @selected($locale === $current)>{{ $locale }}</option>
    @endforeach
</select>
