<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TurnstileService
{
    /**
     * Resolve active anti-bot driver: 'tandha' (native), 'turnstile' (Cloudflare), or 'off'.
     */
    public function activeDriver(): string
    {
        $driver = strtolower((string) config('services.antibot.driver', 'tandha'));
        $antibotEnabled = (bool) config('services.antibot.enabled', false);
        $turnstileConfigured = (bool) config('services.turnstile.enabled')
            && filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));

        if ($driver === 'turnstile' && $turnstileConfigured) {
            return 'turnstile';
        }

        if ($antibotEnabled && $driver === 'tandha') {
            return 'tandha';
        }

        if ($turnstileConfigured && !$antibotEnabled) {
            return 'turnstile';
        }

        return 'off';
    }

    public function isActive(): bool
    {
        return $this->activeDriver() !== 'off';
    }

    public function siteKey(): ?string
    {
        return $this->activeDriver() === 'turnstile'
            ? (string) config('services.turnstile.site_key')
            : null;
    }

    public const IMG_WIDTH = 640;
    public const IMG_HEIGHT = 232;
    public const PIECE_SIZE = 92;
    public const TOLERANCE_PX = 14;

    /**
     * Issue a short-lived Retina visual Jigsaw Puzzle anti-bot challenge generated server-side via PHP GD.
     */
    public function issueChallenge(?string $remoteIp, ?string $userAgent, string $context = 'login'): array
    {
        $challengeId = Str::random(32);
        $issuedMs = (int) round(microtime(true) * 1000);
        $targetX = random_int(230, self::IMG_WIDTH - self::PIECE_SIZE - 44);
        $targetY = random_int(36, self::IMG_HEIGHT - self::PIECE_SIZE - 32);

        $puzzleImages = $this->generatePuzzleImages($targetX, $targetY);

        Cache::put('antibot:challenge:' . $challengeId, [
            'ip' => $remoteIp,
            'ua' => hash('sha256', (string) $userAgent),
            'context' => in_array($context, ['login', 'register'], true) ? $context : 'login',
            'issued_ms' => $issuedMs,
            'target_x' => $targetX,
        ], now()->addMinutes(10));

        return [
            'challenge_id' => $challengeId,
            'bg_image' => $puzzleImages['bg_image'],
            'piece_image' => $puzzleImages['piece_image'],
            'piece_y' => $targetY,
            'img_width' => self::IMG_WIDTH,
            'img_height' => self::IMG_HEIGHT,
            'piece_size' => self::PIECE_SIZE,
        ];
    }

    /**
     * Check whether high-res local pixel (lx, ly) inside 184x184 supersampled piece belongs to jigsaw mask.
     */
    private function isInsideJigsawMaskHiRes(int $lx, int $ly): bool
    {
        $inCore = $lx >= 20 && $lx <= 148 && $ly >= 36 && $ly <= 164;
        $inRightTab = (($lx - 148) ** 2 + ($ly - 100) ** 2) <= 784; // r = 28
        $inTopTab = (($lx - 84) ** 2 + ($ly - 36) ** 2) <= 784;   // r = 28

        return $inCore || $inRightTab || $inTopTab;
    }

    /**
     * Draw a clean semi-transparent filled rounded rectangle in a single polygon pass (zero corner overdraw).
     */
    private function fillRoundedRect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $r = max(2, min($radius, (int) (($x2 - $x1) / 2), (int) (($y2 - $y1) / 2)));
        $corners = [
            [$x2 - $r, $y1 + $r, -90, 0],
            [$x2 - $r, $y2 - $r, 0, 90],
            [$x1 + $r, $y2 - $r, 90, 180],
            [$x1 + $r, $y1 + $r, 180, 270],
        ];
        $pts = [];
        foreach ($corners as [$cx, $cy, $degStart, $degEnd]) {
            for ($deg = $degStart; $deg <= $degEnd; $deg += 15) {
                $rad = deg2rad($deg);
                $pts[] = (int) round($cx + cos($rad) * $r);
                $pts[] = (int) round($cy + sin($rad) * $r);
            }
        }
        imagefilledpolygon($im, $pts, $color);
    }

    /**
     * Render a 2x Retina supersampled Tandha scene (1280x464 -> 640x232) and extract anti-aliased cutout piece.
     */
    private function generatePuzzleImages(int $targetX, int $targetY): array
    {
        $w = self::IMG_WIDTH;
        $h = self::IMG_HEIGHT;
        $p = self::PIECE_SIZE;

        $hiW = $w * 2;
        $hiH = $h * 2;
        $hiP = $p * 2;
        $hiTargetX = $targetX * 2;
        $hiTargetY = $targetY * 2;

        $hiBg = imagecreatetruecolor($hiW, $hiH);
        imagealphablending($hiBg, true);

        $palettes = [
            // Palette 0: Deep Forest & Luminous Mint
            ['bgTop' => [11, 32, 27], 'bgBot' => [13, 108, 84], 'card' => [18, 66, 54], 'accent' => [52, 211, 153], 'glow' => [167, 243, 208], 'wave' => [255, 255, 255], 'hill' => [9, 72, 56]],
            // Palette 1: Emerald Twilight & Warm Cream Cards
            ['bgTop' => [13, 42, 35], 'bgBot' => [10, 92, 71], 'card' => [243, 239, 230], 'accent' => [13, 122, 95], 'glow' => [52, 211, 153], 'wave' => [167, 243, 208], 'hill' => [17, 56, 46]],
            // Palette 2: Crisp Mint & Deep Pine Architectural
            ['bgTop' => [230, 244, 239], 'bgBot' => [198, 226, 216], 'card' => [255, 255, 255], 'accent' => [13, 122, 95], 'glow' => [13, 38, 32], 'wave' => [13, 122, 95], 'hill' => [168, 210, 194]],
        ];
        $theme = $palettes[array_rand($palettes)];

        // 1. Smooth vertical gradient base
        for ($y = 0; $y < $hiH; $y++) {
            $t = $y / max(1, $hiH - 1);
            $r = (int) round($theme['bgTop'][0] + ($theme['bgBot'][0] - $theme['bgTop'][0]) * $t);
            $g = (int) round($theme['bgTop'][1] + ($theme['bgBot'][1] - $theme['bgTop'][1]) * $t);
            $b = (int) round($theme['bgTop'][2] + ($theme['bgBot'][2] - $theme['bgTop'][2]) * $t);
            imageline($hiBg, 0, $y, $hiW - 1, $y, imagecolorallocate($hiBg, $r, $g, $b));
        }

        // 2. Soft diagonal architectural stripes + ambient radial bokeh halos
        $cGrid = imagecolorallocatealpha($hiBg, $theme['wave'][0], $theme['wave'][1], $theme['wave'][2], 114);
        imagesetthickness($hiBg, 2);
        for ($gx = 40; $gx < $hiW + 120; $gx += 72) {
            imageline($hiBg, $gx, 0, $gx - 110, $hiH, $cGrid);
        }

        $cBokeh1 = imagecolorallocatealpha($hiBg, $theme['accent'][0], $theme['accent'][1], $theme['accent'][2], 96);
        $cBokeh2 = imagecolorallocatealpha($hiBg, $theme['glow'][0], $theme['glow'][1], $theme['glow'][2], 102);
        for ($i = 0; $i < 6; $i++) {
            $cx = 90 + $i * 210 + random_int(-35, 35);
            $cy = random_int(60, $hiH - 60);
            $dia = random_int(110, 240);
            imagefilledellipse($hiBg, $cx, $cy, $dia, $dia, ($i % 2 === 0) ? $cBokeh1 : $cBokeh2);
        }

        // 3. Smooth curved layered hill horizon
        $cHill = imagecolorallocatealpha($hiBg, $theme['hill'][0], $theme['hill'][1], $theme['hill'][2], 32);
        $hillPts = [0, $hiH];
        $hBase = random_int(250, 320);
        $hAmp = random_int(35, 65);
        $hPhase = random_int(0, 314) / 100.0;
        for ($hx = 0; $hx <= $hiW; $hx += 32) {
            $hillPts[] = $hx;
            $hillPts[] = (int) round($hBase + sin($hx * 0.0055 + $hPhase) * $hAmp);
        }
        $hillPts[] = $hiW;
        $hillPts[] = $hiH;
        imagefilledpolygon($hiBg, $hillPts, $cHill);

        // 4. Stylized crisp POS UI card illustrations across the middle band for unmistakable puzzle texture
        $cCard = imagecolorallocatealpha($hiBg, $theme['card'][0], $theme['card'][1], $theme['card'][2], 34);
        $cCardBar = imagecolorallocatealpha($hiBg, $theme['accent'][0], $theme['accent'][1], $theme['accent'][2], 24);
        $cCardDot = imagecolorallocatealpha($hiBg, $theme['glow'][0], $theme['glow'][1], $theme['glow'][2], 40);

        for ($c = 0; $c < 3; $c++) {
            $rx1 = 70 + $c * 410 + random_int(-25, 35);
            $ry1 = random_int(52, $hiH - 220);
            $rw = random_int(190, 250);
            $rh = random_int(120, 160);
            $this->fillRoundedRect($hiBg, $rx1, $ry1, $rx1 + $rw, $ry1 + $rh, 24, $cCard);
            $this->fillRoundedRect($hiBg, $rx1 + 20, $ry1 + 22, $rx1 + $rw - 20, $ry1 + 48, 10, $cCardBar);
            // Mini chart / product bars inside each card
            for ($bar = 0; $bar < 4; $bar++) {
                $bx1 = $rx1 + 22 + $bar * 42;
                $bh = random_int(28, 64);
                $this->fillRoundedRect($hiBg, $bx1, $ry1 + $rh - 22 - $bh, $bx1 + 26, $ry1 + $rh - 22, 6, $cCardDot);
            }
        }

        // 5. Crisp multi-thickness smooth anti-OCR ribbons
        $cWave = imagecolorallocatealpha($hiBg, $theme['wave'][0], $theme['wave'][1], $theme['wave'][2], 82);
        imagesetthickness($hiBg, 3);
        for ($wIdx = 0; $wIdx < 3; $wIdx++) {
            $amp = random_int(18, 36);
            $freq = random_int(2, 4) * 0.009;
            $baseY = random_int(85, $hiH - 85);
            $phase = random_int(0, 314) / 100.0;
            $prevX = 0;
            $prevY = (int) round($baseY + sin($phase) * $amp);
            for ($x = 12; $x <= $hiW; $x += 12) {
                $curY = (int) round($baseY + sin($x * $freq + $phase) * $amp);
                imageline($hiBg, $prevX, $prevY, $x, $curY, $cWave);
                $prevX = $x;
                $prevY = $curY;
            }
        }
        imagesetthickness($hiBg, 1);

        // 6. Cut out supersampled 184x184 jigsaw piece & shaded socket, then downsample 2x -> 640x232 & 92x92
        $hiPiece = imagecreatetruecolor($hiP, $hiP);
        imagealphablending($hiPiece, false);
        imagesavealpha($hiPiece, true);
        imagefill($hiPiece, 0, 0, imagecolorallocatealpha($hiPiece, 0, 0, 0, 127));

        $mask = [];
        for ($ly = 0; $ly < $hiP; $ly++) {
            for ($lx = 0; $lx < $hiP; $lx++) {
                $mask[$ly][$lx] = $this->isInsideJigsawMaskHiRes($lx, $ly);
            }
        }

        $holeDark = imagecolorallocatealpha($hiBg, 6, 20, 16, 26);
        $holeRim = imagecolorallocatealpha($hiBg, 255, 255, 255, 16);
        $pieceOuterRim = imagecolorallocatealpha($hiPiece, 52, 211, 153, 0);
        $pieceInnerRim = imagecolorallocatealpha($hiPiece, 255, 255, 255, 0);

        for ($ly = 0; $ly < $hiP; $ly++) {
            for ($lx = 0; $lx < $hiP; $lx++) {
                if (!$mask[$ly][$lx]) {
                    continue;
                }

                $isOuter = ($lx <= 2 || $lx >= $hiP - 3 || $ly <= 2 || $ly >= $hiP - 3)
                    || !$mask[$ly - 3][$lx] || !$mask[$ly + 3][$lx]
                    || !$mask[$ly][$lx - 3] || !$mask[$ly][$lx + 3];

                $isInner = !$isOuter && (
                    ($lx <= 5 || $lx >= $hiP - 6 || $ly <= 5 || $ly >= $hiP - 6)
                    || !$mask[$ly - 6][$lx] || !$mask[$ly + 6][$lx]
                    || !$mask[$ly][$lx - 6] || !$mask[$ly][$lx + 6]
                );

                $srcColor = imagecolorat($hiBg, $hiTargetX + $lx, $hiTargetY + $ly);
                $rgb = imagecolorsforindex($hiBg, $srcColor);

                if ($isOuter) {
                    imagesetpixel($hiPiece, $lx, $ly, $pieceOuterRim);
                    imagesetpixel($hiBg, $hiTargetX + $lx, $hiTargetY + $ly, $holeRim);
                } elseif ($isInner) {
                    imagesetpixel($hiPiece, $lx, $ly, $pieceInnerRim);
                    imagesetpixel($hiBg, $hiTargetX + $lx, $hiTargetY + $ly, $holeDark);
                } else {
                    imagesetpixel($hiPiece, $lx, $ly, imagecolorallocatealpha($hiPiece, $rgb['red'], $rgb['green'], $rgb['blue'], 0));
                    imagesetpixel($hiBg, $hiTargetX + $lx, $hiTargetY + $ly, $holeDark);
                }
            }
        }

        // High-quality 2x LANCZOS/bicubic resample to crisp Retina 640x232 and 92x92
        $bg = imagecreatetruecolor($w, $h);
        imagecopyresampled($bg, $hiBg, 0, 0, 0, 0, $w, $h, $hiW, $hiH);

        $piece = imagecreatetruecolor($p, $p);
        imagealphablending($piece, false);
        imagesavealpha($piece, true);
        imagefill($piece, 0, 0, imagecolorallocatealpha($piece, 0, 0, 0, 127));
        imagecopyresampled($piece, $hiPiece, 0, 0, 0, 0, $p, $p, $hiP, $hiP);

        ob_start();
        imagepng($bg, null, 6);
        $bgBase64 = 'data:image/png;base64,' . base64_encode((string) ob_get_clean());

        ob_start();
        imagepng($piece, null, 6);
        $pieceBase64 = 'data:image/png;base64,' . base64_encode((string) ob_get_clean());

        imagedestroy($hiBg);
        imagedestroy($hiPiece);
        imagedestroy($bg);
        imagedestroy($piece);

        return [
            'bg_image' => $bgBase64,
            'piece_image' => $pieceBase64,
        ];
    }

    /**
     * Verify completed visual jigsaw alignment telemetry and issue a single-use signed token.
     *
     * @throws ValidationException
     */
    public function verifyChallenge(string $challengeId, array $telemetry, ?string $remoteIp, ?string $userAgent): string
    {
        if (filled($telemetry['honeypot'] ?? null)) {
            throw ValidationException::withMessages([
                'antibot' => ['Aktivitas otomatis terdeteksi. Permintaan ditolak.'],
            ]);
        }

        $challenge = Cache::pull('antibot:challenge:' . $challengeId);
        $uaHash = hash('sha256', (string) $userAgent);
        if (!$challenge || ($challenge['ip'] ?? null) !== $remoteIp || ($challenge['ua'] ?? null) !== $uaHash) {
            throw ValidationException::withMessages([
                'antibot' => ['Sesi gambar kedaluwarsa. Silakan muat ulang dan geser potongan puzzle.'],
            ]);
        }

        $nowMs = (int) round(microtime(true) * 1000);
        $serverElapsed = $nowMs - (int) ($challenge['issued_ms'] ?? 0);
        $submittedX = (int) ($telemetry['x'] ?? -1);
        $targetX = (int) ($challenge['target_x'] ?? -999);
        $clientDuration = (int) ($telemetry['duration_ms'] ?? 0);
        $dragPoints = (int) ($telemetry['drag_points'] ?? 0);
        $keySteps = (int) ($telemetry['key_steps'] ?? 0);
        $inputMethod = (string) ($telemetry['input_method'] ?? 'pointer');

        $validMotion = $inputMethod === 'keyboard'
            ? ($keySteps >= 2 && $clientDuration >= 120 && $serverElapsed >= 150)
            : ($dragPoints >= 3 && $clientDuration >= 150 && $serverElapsed >= 150);

        if (!$validMotion || abs($submittedX - $targetX) > self::TOLERANCE_PX) {
            throw ValidationException::withMessages([
                'antibot' => ['Posisi potongan gambar belum pas. Coba geser kembali pada gambar baru.'],
            ]);
        }

        $token = 'tsh_' . Str::random(40);
        Cache::put('antibot:token:' . $token, [
            'ip' => $remoteIp,
            'ua' => $uaHash,
            'context' => $challenge['context'] ?? 'login',
        ], now()->addMinutes(10));

        return $token;
    }

    /**
     * Verify anti-bot token (Tandha Shield or Cloudflare Turnstile) and honeypot on server side.
     *
     * @throws ValidationException
     */
    public function verifyOrFail(?string $token, ?string $remoteIp = null, ?string $userAgent = null, ?string $honeypot = null): void
    {
        if (filled($honeypot)) {
            throw ValidationException::withMessages([
                'cf_turnstile_response' => ['Verifikasi keamanan ditolak.'],
            ]);
        }

        $driver = $this->activeDriver();
        if ($driver === 'off') {
            return;
        }

        if ($driver === 'tandha') {
            if (blank($token)) {
                throw ValidationException::withMessages([
                    'cf_turnstile_response' => ['Harap geser tombol verifikasi keamanan Tandha Shield terlebih dahulu.'],
                ]);
            }

            $record = Cache::pull('antibot:token:' . $token);
            if (
                !$record
                || ($record['ip'] ?? null) !== $remoteIp
                || ($record['ua'] ?? null) !== hash('sha256', (string) $userAgent)
            ) {
                throw ValidationException::withMessages([
                    'cf_turnstile_response' => ['Verifikasi Tandha Shield tidak valid atau sudah kedaluwarsa. Silakan ulangi geser verifikasi.'],
                ]);
            }

            return;
        }

        if (blank($token)) {
            throw ValidationException::withMessages([
                'cf_turnstile_response' => ['Harap selesaikan verifikasi keamanan Cloudflare Turnstile terlebih dahulu.'],
            ]);
        }

        try {
            $payload = [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
            ];

            if ($remoteIp) {
                $payload['remoteip'] = $remoteIp;
            }

            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);

            if (!$response->successful() || $response->json('success') !== true) {
                throw ValidationException::withMessages([
                    'cf_turnstile_response' => ['Verifikasi anti-bot Cloudflare gagal atau kedaluwarsa. Silakan ulangi verifikasi.'],
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'cf_turnstile_response' => ['Tidak dapat memverifikasi keamanan Cloudflare saat ini. Silakan coba beberapa saat lagi.'],
            ]);
        }
    }
}
