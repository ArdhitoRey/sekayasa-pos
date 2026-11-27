@if($antibotDriver === 'tandha')
    <div class="space-y-1.5">
        <!-- Verified Compact State -->
        <div x-show="antiBot['{{ $ctx }}'].verified" x-cloak
             class="w-full h-12 px-3.5 rounded-xl bg-[#E6F4EF] border border-[#0D7A5F]/45 flex items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-7 h-7 rounded-lg bg-[#0D7A5F] text-white flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <span class="font-bold text-[#0D7A5F] truncate">Verifikasi Gambar Berhasil — Bukan Bot</span>
            </div>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#0D7A5F] shrink-0">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                <span>Tandha Shield</span>
            </span>
        </div>

        <!-- Visual Image Jigsaw Puzzle Card -->
        <div x-show="!antiBot['{{ $ctx }}'].verified"
             class="p-3 bg-white border rounded-xl space-y-2.5 shadow-2xs transition-colors"
             :class="antiBot['{{ $ctx }}'].error ? 'border-rose-300' : 'border-[#D1C9B8]'">
            <div class="flex items-center justify-between gap-2 text-[11px]">
                <span class="inline-flex items-center gap-1.5 font-semibold text-[#3D544E]">
                    <svg class="w-3.5 h-3.5 text-[#0D7A5F]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                    <span>Verifikasi Gambar Keamanan</span>
                </span>
                <button type="button" @click="refreshAntiBotChallenge('{{ $ctx }}')" :disabled="antiBot['{{ $ctx }}'].loadingImage || antiBot['{{ $ctx }}'].verifying"
                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#0D7A5F] hover:text-[#095C47] disabled:opacity-50 transition">
                    <svg class="w-3.5 h-3.5" :class="antiBot['{{ $ctx }}'].loadingImage ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    <span>Gambar Baru</span>
                </button>
            </div>

            <!-- Puzzle Artwork + Movable Cutout Piece -->
            <div class="relative w-full rounded-lg overflow-hidden border border-[#E5E0D5] bg-[#0D2620]" style="aspect-ratio: 320 / 116;">
                <div x-show="antiBot['{{ $ctx }}'].loadingImage || !antiBot['{{ $ctx }}'].bgImage" class="absolute inset-0 bg-[#143B32] animate-pulse flex items-center justify-center text-[11px] text-[#34D399] font-medium">
                    Memuat gambar verifikasi...
                </div>
                <img x-show="antiBot['{{ $ctx }}'].bgImage" :src="antiBot['{{ $ctx }}'].bgImage" alt="Puzzle Verifikasi Tandha"
                     class="w-full h-full object-fill block select-none pointer-events-none" draggable="false">
                <img x-show="antiBot['{{ $ctx }}'].pieceImage && !antiBot['{{ $ctx }}'].loadingImage" :src="antiBot['{{ $ctx }}'].pieceImage" alt="Potongan Puzzle"
                     :style="antiBotPieceStyle('{{ $ctx }}')"
                     class="absolute select-none pointer-events-none drop-shadow-[0_4px_10px_rgba(0,0,0,0.45)]" draggable="false">
            </div>

            <!-- Precision Slider Track -->
            <div :id="'antibot-track-{{ $ctx }}'"
                 role="slider"
                 tabindex="0"
                 aria-label="Geser potongan puzzle ke posisi yang pas"
                 :aria-valuenow="antiBot['{{ $ctx }}'].progress"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 @keydown.right.prevent="stepAntiBotKeyboard('{{ $ctx }}', 3)"
                 @keydown.left.prevent="stepAntiBotKeyboard('{{ $ctx }}', -3)"
                 @keydown.enter.prevent="submitAntiBotKeyboard('{{ $ctx }}')"
                 class="relative w-full h-10 rounded-lg border select-none overflow-hidden transition-colors flex items-center focus:outline-none focus:ring-4 focus:ring-[#0D7A5F]/10"
                 :class="antiBot['{{ $ctx }}'].error ? 'bg-rose-50/75 border-rose-300' : 'bg-[#F7FAF9] border-[#D1C9B8]'">
                <div class="absolute inset-y-0 left-0 bg-[#0D7A5F]/10 pointer-events-none"
                     :class="antiBot['{{ $ctx }}'].dragging ? '' : 'transition-all duration-150'"
                     :style="'width: ' + Math.max(0, antiBot['{{ $ctx }}'].progress) + '%'"></div>

                <div class="relative z-10 w-full text-center px-12 text-[11px] font-semibold pointer-events-none truncate"
                     :class="antiBot['{{ $ctx }}'].error ? 'text-rose-700' : 'text-[#3D544E]'"
                     x-text="antiBot['{{ $ctx }}'].verifying
                         ? 'Memeriksa kecocokan gambar...'
                         : (antiBot['{{ $ctx }}'].error || 'Geser potongan puzzle ke lubang yang pas')"></div>

                <button type="button"
                        tabindex="-1"
                        @pointerdown.prevent="startAntiBotDrag('{{ $ctx }}', $event)"
                        :style="'transform: translateX(' + antiBotThumbOffset('{{ $ctx }}') + 'px)'"
                        :class="antiBot['{{ $ctx }}'].dragging ? 'transition-none cursor-grabbing scale-[0.98]' : 'transition-transform duration-150 cursor-grab'"
                        class="touch-none absolute left-1 top-1 bottom-1 w-9 rounded-md bg-[#0D7A5F] hover:bg-[#095C47] text-white flex items-center justify-center shadow-xs z-20 focus:outline-none">
                    <svg x-show="!antiBot['{{ $ctx }}'].verifying" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/></svg>
                    <span x-show="antiBot['{{ $ctx }}'].verifying" x-cloak class="animate-spin rounded-full h-3.5 w-3.5 border-2 border-white border-t-transparent"></span>
                </button>
            </div>
        </div>

        <!-- Hidden honeypot field (invisible to humans, traps naive form-filling bots) -->
        <input type="text" name="company_website" x-model="honeypotField" tabindex="-1" autocomplete="off" aria-hidden="true"
               style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none;">
    </div>
@elseif($antibotDriver === 'turnstile')
    <div class="p-3.5 bg-[#F7FAF9] border border-[#E5E0D5] rounded-xl space-y-2">
        <div class="flex items-center justify-between text-[11px] font-semibold text-[#3D544E]">
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-[#0D7A5F]"></i>
                <span>Proteksi Anti-Bot Cloudflare Turnstile</span>
            </span>
            @if($isTurnstileSandbox)
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-white border border-[#E5E0D5] text-[#3D544E]">Sandbox Local</span>
            @endif
        </div>
        <div id="cf-turnstile-{{ $ctx }}"></div>
    </div>
@endif
