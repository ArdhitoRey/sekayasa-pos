<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose = 'verify_email',
        public string $recipientName = 'Pengguna Tandha',
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'reset_password'
            ? 'Kode Reset Kata Sandi Akun Tandha POS'
            : 'Kode Verifikasi Email Pendaftaran Tandha POS';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $isReset = $this->purpose === 'reset_password';
        $title = $isReset ? 'Reset Kata Sandi' : 'Verifikasi Alamat Email';
        $desc = $isReset
            ? 'Kami menerima permintaan untuk mengatur ulang kata sandi akun Tandha POS Anda. Masukkan kode 6 digit berikut pada halaman reset password:'
            : 'Gunakan kode verifikasi 6 digit berikut untuk mengonfirmasi alamat email Anda saat mendaftarkan bisnis di Tandha POS:';
        $name = e($this->recipientName);
        $code = e($this->code);

        return <<<HTML
        <div style="font-family:'Plus Jakarta Sans',Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;background:#F8FAFC;color:#0F172A;">
            <div style="background:#FFFFFF;border:1px solid #E2E8F0;border-radius:10px;padding:24px;">
                <div style="font-size:11px;font-weight:700;color:#0D7A5F;text-transform:uppercase;letter-spacing:1px;">TANDHA · by SEKA GROUP</div>
                <h2 style="margin:8px 0 12px;font-size:20px;color:#0F172A;">{$title}</h2>
                <p style="font-size:14px;line-height:1.6;color:#475569;">Halo {$name},<br>{$desc}</p>
                <div style="margin:20px 0;padding:16px;background:#F1F5F9;border-radius:8px;text-align:center;font-family:monospace;font-size:28px;font-weight:800;letter-spacing:6px;color:#0D2620;">{$code}</div>
                <p style="font-size:12px;color:#64748B;">Kode berlaku selama <b>15 menit</b>. Jangan bagikan kode ini kepada siapa pun.</p>
            </div>
        </div>
        HTML;
    }
}
