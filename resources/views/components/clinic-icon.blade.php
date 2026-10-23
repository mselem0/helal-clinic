@props(['name', 'size' => 22])
<svg {{ $attributes->class(['clinic-icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
    @case('whatsapp')<path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.5L3 21l1.6-4.8a8.5 8.5 0 1 1 15.9-4.5Z"/><path d="m8 7.5 1.5 2-1 1c.8 1.8 2 3 3.8 3.8l1-1 2 1.5c-.3 1.5-1.5 2-2.8 1.5-3.5-1.2-5.6-3.3-6.8-6.8C6.2 8.2 6.7 7 8 7.5Z"/>@break
    @case('help')<circle cx="12" cy="12" r="9"/><path d="M9.5 8.5a2.5 2.5 0 0 1 5 .5c0 2-2.5 2-2.5 4M12 17h.01"/>@break
    @case('clipboard')<rect x="5" y="5" width="14" height="16" rx="2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/>@break
    @case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
    @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
    @case('google-maps')
        <path d="M12 2a7 7 0 0 0-5.1 2.2L10 8l5.7-5A7 7 0 0 0 12 2Z" fill="#1A73E8" stroke="none"/>
        <path d="M6.9 4.2A7 7 0 0 0 5 9c0 2.2 1 4 2.4 5.8L12 9 6.9 4.2Z" fill="#4285F4" stroke="none"/>
        <path d="m15.7 3-5.4 5.4a2.4 2.4 0 0 1 3.4 3.4l4.5-4.5A7 7 0 0 0 15.7 3Z" fill="#34A853" stroke="none"/>
        <path d="m18.2 7.3-4.5 4.5a2.4 2.4 0 0 1-3.4-3.4l-2.9 6.4c1.5 1.9 3.2 4 3.9 6.3.2.6 1.2.6 1.4 0 1.2-4 6.3-7.6 6.3-12.1 0-.6-.1-1.2-.8-1.7Z" fill="#FBBC04" stroke="none"/>
        <path d="m7.4 14.8 2.9-3a2.4 2.4 0 0 0 3.4 0l4.5-4.5c.5 4.5-3.3 8-5.2 11.5l-1 3.2c-.8 0-1-1.8-1.7-3.1-1-1.7-2-2.9-2.9-4.1Z" fill="#34A853" stroke="none"/>
        @break
    @case('map')<path d="m3 5 6-2 6 2 6-2v16l-6 2-6-2-6 2V5Z M9 3v16M15 5v16"/>@break
    @case('external')<path d="M14 3h7v7M21 3 10 14M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>@break
    @case('clinic')<rect x="4" y="3" width="16" height="18" rx="3"/><path d="M9 21v-6h6v6M12 6v5M9.5 8.5h5M7 12h1M16 12h1"/>@break
    @case('doctor')<circle cx="12" cy="6" r="3"/><path d="M4 21v-3a8 8 0 0 1 16 0v3M8 13v3a3 3 0 0 0 6 0v-3M11 19v2"/><circle cx="17" cy="18" r="1.5"/>@break
    @case('external-link')<path d="M14 3h7v7M21 3 10 14M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>@break
    @case('quote')<path d="M10 7H4v7h5c0 3-2 5-4 5M21 7h-6v7h5c0 3-2 5-4 5"/>@break
    @case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
    @case('play-triangle')<path d="m9 5 11 7-11 7V5Z" fill="currentColor" stroke="none"/>@break
    @case('info')<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>@break
    @case('compass')<circle cx="12" cy="12" r="9"/><path d="m16 8-2.5 5.5L8 16l2.5-5.5L16 8Z"/>@break
    @case('arrow-left')<path d="M19 12H5m6-6-6 6 6 6"/>@break
    @case('services')<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><path d="M17.5 14v7M14 17.5h7"/>@break
    @case('academic')<path d="m2 9 10-5 10 5-10 5-10-5Z M6 11v6c4 3 8 3 12 0v-6 M22 9v7"/>@break
    @case('badge')<path d="m12 3 3 2 3 .5.5 3 2 3-2 3-.5 3-3 .5-3 2-3-2-3-.5-.5-3-2-3 2-3 .5-3 3-.5 3-2Z"/><path d="m8.5 11.5 2.5 2.5 4.5-4.5"/>@break
    @case('pin')<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>@break
    @case('calendar')<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4M17 3v4M3 11h18m-13 5 2 2 4-4"/>@break
    @case('phone')<path d="m7 3 3 5-2 2c1 3 3 5 6 6l2-2 5 3-1 4C10 22 2 14 3 4l4-1Z"/>@break
    @case('message')<path d="M21 11a9 9 0 0 1-9 9H4l-2 2V11a9 9 0 0 1 19 0Z M7 9h9M7 13h6"/>@break
    @case('check')<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>@break
    @case('play')<rect x="3" y="4" width="18" height="16" rx="4"/><path d="m10 8 6 4-6 4V8Z"/>@break
@endswitch
</svg>
