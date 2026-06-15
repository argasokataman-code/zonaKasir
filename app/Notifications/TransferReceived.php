<?php

namespace App\Notifications;

use App\Models\Tenants\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransferReceived extends Notification
{
    use Queueable;

    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $grossAmount = number_format(
            $this->withdrawal->amount + ($this->withdrawal->fee_amount ?? 0), 0, ',', '.'
        );
        $feeAmount = number_format($this->withdrawal->fee_amount ?? 0, 0, ',', '.');
        $netAmount = number_format($this->withdrawal->amount, 0, ',', '.');
        $status = ucfirst($this->withdrawal->status);
        $date = $this->withdrawal->processed_at->format('d/m/Y H:i');
        $transactionId = $this->withdrawal->disburse_id ?? $this->withdrawal->id;

        return (new MailMessage)
            ->subject("✅ Transfer Diterima - Rp {$netAmount}")
            ->greeting("Halo {$notifiable->name}!")
            ->line("Anda telah menerima transfer dari ZonaKasir.")
            ->line("**Detail Transaksi:**")
            ->line("──────────────────────────────────────")
            ->line("**ID Transaksi:** #{$transactionId}")
            ->line("**Tanggal:** {$date}")
            ->line("**Status:** {$status}")
            ->line("──────────────────────────────────────")
            ->line("**Rincian Keuangan:**")
            ->line("• Total Debit: Rp {$grossAmount}")
            ->line("• Biaya Transfer: Rp {$feeAmount}")
            ->line("• **Yang Anda Terima: Rp {$netAmount}**")
            ->line("──────────────────────────────────────")
            ->line("**Rekening Tujuan:**")
            ->line("• Bank: {$this->withdrawal->bank_name}")
            ->line("• No. Rekening: {$this->withdrawal->bank_account_number}")
            ->line("• Atas Nama: {$this->withdrawal->bank_account_name}")
            ->line("──────────────────────────────────────")
            ->line("Jika ada pertanyaan, silakan hubungi admin ZonaKasir.")
            ->action('Lihat Riwayat Transaksi', url('/admin/withdrawal'))
            ->line('Terima kasih telah menggunakan ZonaKasir.');
    }

    public function toArray($notifiable): array
    {
        return [
            'message'         => 'Transfer diterima: Rp ' . number_format($this->withdrawal->amount, 0, ',', '.'),
            'withdrawal_id'   => $this->withdrawal->id,
            'transaction_id'  => $this->withdrawal->disburse_id ?? $this->withdrawal->id,
            'gross_amount'    => $this->withdrawal->amount + ($this->withdrawal->fee_amount ?? 0),
            'fee_amount'      => $this->withdrawal->fee_amount ?? 0,
            'net_amount'      => $this->withdrawal->amount,
            'bank_name'       => $this->withdrawal->bank_name,
            'bank_account'    => $this->withdrawal->bank_account_number,
            'status'          => $this->withdrawal->status,
            'processed_at'    => $this->withdrawal->processed_at?->toISOString(),
        ];
    }
}
