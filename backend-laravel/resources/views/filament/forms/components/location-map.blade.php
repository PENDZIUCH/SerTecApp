<div>
    @if($getRecord() && $getRecord()->latitude && $getRecord()->longitude)
        @php
            $lat = $getRecord()->latitude;
            $lng = $getRecord()->longitude;
        @endphp
        <div class="rounded-lg border border-gray-300 overflow-hidden">
            <iframe
                src="https://www.google.com/maps?q={{ $lat }},{{ $lng }}&output=embed"
                width="100%"
                height="300"
                style="border:0;"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Ubicación del parte"
            ></iframe>
        </div>
        <p class="mt-2">
            <a href="https://www.google.com/maps?q={{ $lat }},{{ $lng }}" target="_blank" class="text-primary-600 hover:underline font-medium">
                📍 Abrir en Google Maps ({{ $lat }}, {{ $lng }})
            </a>
        </p>
    @else
        <p class="text-sm text-gray-500">Sin datos de ubicación</p>
    @endif
</div>
