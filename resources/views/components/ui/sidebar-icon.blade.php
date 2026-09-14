@props(['name'])

<svg {{ $attributes->merge(['class' => 'hafez-nav-link__icon', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('dashboard') <rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="3" y="14" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /> @break
        @case('competition') <path d="M8 4h8v4a4 4 0 0 1-8 0V4Z" /><path d="M8 6H5a3 3 0 0 0 3 3M16 6h3a3 3 0 0 1-3 3M12 12v5M8 21h8" /> @break
        @case('layers') <path d="m12 3 9 5-9 5-9-5 9-5Z" /><path d="m3 12 9 5 9-5M3 16l9 5 9-5" /> @break
        @case('registrations') <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" /><circle cx="9.5" cy="7" r="4" /><path d="M19 8v6M16 11h6" /> @break
        @case('users') <circle cx="9" cy="8" r="4" /><path d="M2 21v-2a5 5 0 0 1 10 0v2M16 11a3 3 0 1 0 0-6M16 15a5 5 0 0 1 6 5" /> @break
        @case('students') <circle cx="12" cy="8" r="4" /><path d="M4 21v-2a8 8 0 0 1 16 0v2M8 13h8" /> @break
        @case('settings') <circle cx="12" cy="12" r="3" /><path d="M19 12a7 7 0 0 0-.1-1.1l2-1.5-2-3.4-2.3 1a7 7 0 0 0-1.9-1.1L14.5 3h-4l-.3 2.9a7 7 0 0 0-1.9 1.1L6 6l-2 3.4 2 1.5A7 7 0 0 0 6 12c0 .4 0 .7.1 1.1l-2 1.5 2 3.4 2.3-1a7 7 0 0 0 1.9 1.1l.3 2.9h4l.3-2.9a7 7 0 0 0 1.9-1.1l2.3 1 2-3.4-2-1.5A7 7 0 0 0 19 12Z" /> @break
        @case('committees') <circle cx="9" cy="7" r="4" /><path d="M2 21v-2a5 5 0 0 1 10 0v2M16 3.5a4 4 0 0 1 0 7M22 21v-2a5 5 0 0 0-3.5-4.75" /> @break
        @case('evaluation') <rect x="3" y="3" width="18" height="18" rx="2" /><path d="m8 12 2.5 2.5L16.5 8" /> @break
        @case('results') <path d="M4 20V4M4 20h16" /><path d="m7 16 4-5 3 3 4-6" /> @break
        @case('certificate') <circle cx="12" cy="9" r="5" /><path d="m8.5 13.5-1 7 4.5-2 4.5 2-1-7" /> @break
        @case('report') <path d="M6 3h9l4 4v14H6z" /><path d="M15 3v5h5M9 13h6M9 17h6" /> @break
        @default <circle cx="12" cy="12" r="3" /><path d="M19 12a7 7 0 0 0-.1-1.1l2-1.5-2-3.4-2.3 1a7 7 0 0 0-1.9-1.1L14.5 3h-4l-.3 2.9a7 7 0 0 0-1.9 1.1L6 6l-2 3.4 2 1.5A7 7 0 0 0 6 12c0 .4 0 .7.1 1.1l-2 1.5 2 3.4 2.3-1a7 7 0 0 0 1.9 1.1l.3 2.9h4l.3-2.9a7 7 0 0 0 1.9-1.1l2.3 1 2-3.4-2-1.5c.1-.4.1-.7.1-1.1Z" />
    @endswitch
</svg>
