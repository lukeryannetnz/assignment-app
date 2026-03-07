<svg {{ $attributes->merge(['class' => 'fill-current']) }} viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
    <!-- Graduation Cap -->
    <g filter="url(#shadow)">
        <!-- Main cap -->
        <path d="M60 25L15 45L60 65L105 45L60 25Z" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>

        <!-- Cap base -->
        <path d="M25 50V70C25 75 35 85 60 85C85 85 95 75 95 70V50" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>

        <!-- Tassel -->
        <circle cx="105" cy="45" r="3" fill="currentColor"/>
        <line x1="105" y1="48" x2="105" y2="58" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <circle cx="105" cy="60" r="4" fill="currentColor"/>

        <!-- Open Book -->
        <!-- Left page -->
        <path d="M35 70 L35 100 C35 102 36 103 38 103 L58 103 L58 70 C58 68 50 66 45 66 C40 66 35 68 35 70 Z"
              fill="currentColor" fill-opacity="0.95" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        <!-- Right page -->
        <path d="M62 70 L62 103 L82 103 C84 103 85 102 85 100 L85 70 C85 68 80 66 75 66 C70 66 62 68 62 70 Z"
              fill="currentColor" fill-opacity="0.95" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>

        <!-- Page lines on left page -->
        <line x1="40" y1="76" x2="55" y2="76" stroke="currentColor" stroke-width="1" stroke-linecap="round" opacity="0.4"/>
        <line x1="40" y1="82" x2="55" y2="82" stroke="currentColor" stroke-width="1" stroke-linecap="round" opacity="0.4"/>
        <line x1="40" y1="88" x2="52" y2="88" stroke="currentColor" stroke-width="1" stroke-linecap="round" opacity="0.4"/>

        <!-- Page lines on right page -->
        <line x1="65" y1="76" x2="80" y2="76" stroke="currentColor" stroke-width="1" stroke-linecap="round" opacity="0.4"/>
        <line x1="65" y1="82" x2="80" y2="82" stroke="currentColor" stroke-width="1" stroke-linecap="round" opacity="0.4"/>
        <line x1="68" y1="88" x2="80" y2="88" stroke="currentColor" stroke-width="1" stroke-linecap="round" opacity="0.4"/>

        <!-- Book spine/binding -->
        <line x1="60" y1="66" x2="60" y2="103" stroke="currentColor" stroke-width="2"/>
    </g>

    <defs>
        <filter id="shadow" x="0" y="0" width="120" height="120">
            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.3"/>
        </filter>
    </defs>
</svg>
