<title>{{ $site_seo['meta_title'] }}</title>
<meta name="description" content="{{ $site_seo['meta_description'] }}">
<link rel="canonical" href="{{ $site_seo['canonical_url'] }}">
<meta name="robots" content="{{ $site_seo['robots'] }}">
<meta property="og:title" content="{{ $site_seo['og_title'] }}">
<meta property="og:description" content="{{ $site_seo['og_description'] }}">
<meta property="og:locale" content="ar_EG"><meta property="og:type" content="{{ $site_seo['og_type'] }}">
<meta property="og:url" content="{{ $site_seo['canonical_url'] }}"><meta property="og:image" content="{{ $site_seo['og_image'] }}">
<meta property="og:image:alt" content="{{ $site_seo['og_title'] }}">
@if(str_contains($site_seo['og_image'], '/assets/doctor-social-cover.jpg'))<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630"><meta property="og:image:type" content="image/jpeg">@endif
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="{{ $site_seo['og_title'] }}"><meta name="twitter:description" content="{{ $site_seo['og_description'] }}"><meta name="twitter:image" content="{{ $site_seo['og_image'] }}">
@if(isset($site_seo['schema']))<script type="application/ld+json">{!! json_encode($site_seo['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>@endif
