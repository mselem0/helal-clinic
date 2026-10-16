<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'تسجيل الدخول' }} | د. محمد هلال</title>
    
    <!-- Google Fonts: Almarai -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Almarai', 'sans-serif'],
                    },
                    colors: {
                        navy: '#002855',
                        'cyan-brand': '#00B4D8',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Almarai', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #002855 0%, #001229 100%); }
    </style>
    @livewireStyles
    @seoHeader
</head>
<body class="bg-gray-50 text-navy antialiased">
    @seoFooter
    {{ $slot }}
    @livewireScripts
</body>
</html>
