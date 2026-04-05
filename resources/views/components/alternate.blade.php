@foreach ($alternates as $locale => $href)
<link rel="alternate" hreflang="{{ $locale }}" href="{{ $href }}" />
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $canonical }}" />
