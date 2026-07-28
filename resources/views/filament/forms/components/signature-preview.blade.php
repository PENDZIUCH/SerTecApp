<div>
    @if($getRecord() && $getRecord()->signature)
        <div class="rounded-lg border border-gray-300 p-4 bg-white">
            <img 
                src="{{ $getRecord()->signature }}" 
                alt="Firma del Cliente" 
                class="max-w-full h-auto"
                style="max-height: 200px;"
            />
        </div>
    @else
        <p class="text-sm text-gray-500">Sin firma</p>
    @endif
</div>
