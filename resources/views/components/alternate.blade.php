@php
/**
 *  @var array<string, string> $alternates Locale codes to fully-qualified URLs.
 *  @var string $canonical Fully-qualified URL of the canonical (fallback locale) route.
 */
@endphp
@foreach ($alternates as $locale => $href)
<link rel="alternate" hreflang="{{ $locale }}" href="{{ $href }}" />
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $canonical }}" />
