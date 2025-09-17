<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'fee_invoice_id',
        'mpesa_receipt_number',
        'phone_number',
        'amount',
        'balance',
        'transaction_date',
        'status',
        'merchant_request_id',
        'checkout_request_id',
        'result_description',
        'result_code',
        'payment_mode',
        'receipt_sent',
        'payment_plan_type',
        'payment_plan_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'transaction_date' => 'datetime',
        'receipt_sent' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feeInvoice()
    {
        return $this->belongsTo(FeeInvoice::class);
    }

    public function paymentPlan()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('fee_invoice_id', $invoiceId);
    }

    // Status update methods
    public function markAsCompleted($mpesaReceipt = null, $resultCode = '0', $resultDesc = 'Success')
    {
        $this->update([
            'status' => 'completed',
            'mpesa_receipt_number' => $mpesaReceipt ?? $this->mpesa_receipt_number,
            'result_code' => $resultCode,
            'result_description' => $resultDesc,
            'transaction_date' => $this->transaction_date ?? now(),
        ]);

        // Update invoice balance if applicable
        if ($this->feeInvoice) {
            $this->feeInvoice->updateBalance($this->amount);
        }

        // Generate and send receipt
        $this->generateAndSendReceipt();

        return $this;
    }

    public function markAsFailed($resultCode, $resultDesc = 'Payment failed')
    {
        $this->update([
            'status' => 'failed',
            'result_code' => $resultCode,
            'result_description' => $resultDesc,
        ]);

        return $this;
    }

    public function markAsPending()
    {
        $this->update(['status' => 'pending']);
        return $this;
    }

    // Receipt generation
    public function generateAndSendReceipt($channels = ['email', 'sms'])
    {
        try {
            // If receipt was already sent, don't send again
            if ($this->receipt_sent) {
                Log::info("Receipt already sent for payment #{$this->id}");
                return false;
            }

            // Only generate receipt for completed payments
            if ($this->status !== 'completed') {
                Log::warning("Cannot generate receipt for non-completed payment #{$this->id}");
                return false;
            }

            // Generate PDF receipt
            $receiptPath = $this->generatePdfReceipt();
            
            // Send notifications
            $notificationService = app(\App\Services\NotificationService::class);
            $result = $notificationService->sendPaymentReceipt($this, $channels);
            
            // Update payment record
            $this->update([
                'receipt_sent' => true,
                'receipt_path' => $receiptPath,
                'receipt_sent_at' => now(),
            ]);

            Log::info("Receipt generated and sent for payment #{$this->id}", [
                'channels' => $channels,
                'result' => $result
            ]);

            return [
                'success' => true,
                'receipt_path' => $receiptPath,
                'notifications' => $result
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate/send receipt for payment #{$this->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ];
        }
    }

    protected function generatePdfReceipt()
    {
        try {
            // Create receipts directory if it doesn't exist
            $receiptsDir = storage_path('app/receipts');
            if (!file_exists($receiptsDir)) {
                mkdir($receiptsDir, 0755, true);
            }

            $filename = 'receipt_' . $this->id . '.pdf';
            $filepath = $receiptsDir . '/' . $filename;
            
            // Generate PDF using DomPDF or your preferred PDF library
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receipt', [
                'payment' => $this,
                'user' => $this->user,
                'invoice' => $this->feeInvoice,
                'date' => now()->format('d/m/Y H:i:s'),
            ]);
            
            // Save the PDF
            $pdf->save($filepath);
            
            return 'receipts/' . $filename;
            
        } catch (\Exception $e) {
            Log::error("Failed to generate PDF receipt for payment #{$this->id}: " . $e->getMessage());
            throw $e;
        }
    }

    // Helper methods
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function getReceiptUrl()
    {
        if (!$this->receipt_sent || empty($this->receipt_path)) {
            return null;
        }
        
        return route('api.payments.receipt', $this->id);
    }

    public function paymentPlan()
    {
        return $this->morphTo('payment_plan', 'payment_plan_type', 'payment_plan_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helper Methods
    public function markAsCompleted($mpesaDetails = [])
    {
        $this->status = 'completed';
        $this->mpesa_receipt_number = $mpesaDetails['mpesa_receipt_number'] ?? null;
        $this->transaction_date = now();
        $this->result_code = $mpesaDetails['result_code'] ?? '0';
        $this->result_description = $mpesaDetails['result_description'] ?? 'Payment completed successfully';
        
        // Update related fee invoice
        if ($this->feeInvoice) {
            $this->feeInvoice->updateBalance($this->amount);
        }

        $this->save();
        
        // Generate and send receipt
        $this->generateAndSendReceipt();
        
        return $this;
    }

    public function markAsFailed($errorDetails)
    {
        $this->status = 'failed';
        $this->result_code = $errorDetails['errorCode'] ?? '1';
        $this->result_description = $errorDetails['errorMessage'] ?? 'Payment failed';
        $this->save();
        
        return $this;
    }

    protected function generateAndSendReceipt()
    {
        try {
            // Generate receipt (PDF)
            $receiptPath = $this->generateReceiptPdf();
            
            // Send notification with receipt
            $user = $this->user;
            $message = "Payment of KES " . number_format($this->amount, 2) . " received. Receipt: " . url('/receipts/' . basename($receiptPath));
            
            // Send SMS
            if ($user->phone) {
                // TODO: Implement SMS sending logic
                // $this->sendSms($user->phone, $message);
            }
            
            // Send email with receipt
            if ($user->email) {
                // TODO: Implement email sending with attachment
                // Mail::to($user->email)->send(new PaymentReceipt($this, $receiptPath));
            }
            
            $this->receipt_sent = true;
            $this->save();
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to generate/send receipt: ' . $e->getMessage());
            return false;
        }
    }
    
    protected function generateReceiptPdf()
    {
        // TODO: Implement PDF generation using DomPDF or similar
        // This should return the path to the generated PDF
        return storage_path('app/receipts/receipt_' . $this->id . '.pdf');
    }
}
