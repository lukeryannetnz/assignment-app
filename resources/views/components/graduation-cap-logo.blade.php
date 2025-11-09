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

        <!-- Book pages -->
        <rect x="45" y="65" width="30" height="35" rx="2" fill="currentColor" fill-opacity="0.9" stroke="currentColor" stroke-width="2"/>
        <line x1="50" y1="75" x2="70" y2="75" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
        <line x1="50" y1="82" x2="70" y2="82" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5"/>
        <line x1="50" y1="89" x2="65" y2="89" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.5"/>

        <!-- Book spine -->
        <line x1="60" y1="65" x2="60" y2="100" stroke="currentColor" stroke-width="2.5"/>
    </g>

    <defs>
        <filter id="shadow" x="0" y="0" width="120" height="120">
            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.3"/>
        </filter>
    </defs>
</svg>
