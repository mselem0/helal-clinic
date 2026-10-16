<title>{{ $site_seo['meta_title'] ?: 'عيادات د. محمد هلال | أمراض النساء والتوليد' }}</title>
<meta name="description" content="{{ $site_seo['meta_description'] ?: 'عيادات د. محمد هلال لأمراض النساء والتوليد والمناظير والحقن المجهري. تعرفي على الخدمات والفروع واطلبي موعدًا.' }}">
<link rel="canonical" href="{{ $site_seo['canonical_url'] ?: url()->current() }}">
<meta name="robots" content="{{ isset($preview) && $preview ? 'noindex,nofollow' : str_replace('_', ',', $site_seo['robots']) }}">
<meta property="og:title" content="{{ $site_seo['og_title'] ?: $site_seo['meta_title'] }}">
<meta property="og:description" content="{{ $site_seo['og_description'] ?: $site_seo['meta_description'] }}">
<meta property="og:locale" content="ar_EG"><meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}"><meta property="og:image" content="{{ asset('assets/cover.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
