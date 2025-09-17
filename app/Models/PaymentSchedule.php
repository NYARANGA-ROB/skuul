<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class PaymentSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_plan_id',
        'user_id',
        'fee_invoice_id',
        'amount',
        'amount_paid',
        'balance',
        'due_date',
        'paid_date',
        'status',
        'notes',
        'installment_number',
        'is_final'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'datetime',
        'is_final' => 'boolean',
    ];

    // Relationships
    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feeInvoice()
    {
        return $this->belongsTo(FeeInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'fee_invoice_id', 'fee_invoice_id')
            ->where('user_id', $this->user_id);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeDueSoon($query, $days = 7)
    {
        $dueDate = now()->addDays($days)->format('Y-m-d');
        return $query->where('due_date', '<=', $dueDate)
                    ->where('status', 'pending');
    }

    // Helper Methods
    public function recordPayment($amount, $paymentDetails = [])
    {
        $this->amount_paid += $amount;
        $this->balance = max(0, $this->amount - $this->amount_paid);
        
        // Update status based on payment
        if ($this->balance <= 0) {
            $this->status = 'paid';
            $this->paid_date = now();
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partial';
        }

        // Check if overdue
        if ($this->due_date < now() && $this->status !== 'paid') {
            $this->status = 'overdue';
        }

        $this->save();

        // Record the payment
        $payment = Payment::create([
            'user_id' => $this->user_id,
            'fee_invoice_id' => $this->fee_invoice_id,
            'amount' => $amount,
            'balance' => $this->balance,
            'status' => 'completed',
            'payment_mode' => $paymentDetails['payment_mode'] ?? 'mpesa',
            'transaction_date' => now(),
            'mpesa_receipt_number' => $paymentDetails['mpesa_receipt_number'] ?? null,
            'phone_number' => $paymentDetails['phone_number'] ?? null,
        ]);

        // Update related fee invoice
        if ($this->feeInvoice) {
            $this->feeInvoice->updateBalance($amount);
        }

        return $payment;
    }

    public function markAsPaid($paymentDetails = [])
    {
        $amount = $this->balance > 0 ? $this->balance : $this->amount;
        return $this->recordPayment($amount, $paymentDetails);
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'partial' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'overdue' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
        ];

        $status = strtolower($this->status);
        $class = $statuses[$status] ?? 'bg-gray-100 text-gray-800';
        
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $class . '">' . ucfirst($status) . '</span>';
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'overdue' || ($this->due_date < now() && !in_array($this->status, ['paid', 'cancelled']));
    }

    public function getRemainingDaysAttribute()
    {
        if (in_array($this->status, ['paid', 'cancelled'])) {
            return null;
        }

        $now = now();
        $dueDate = \Carbon\Carbon::parse($this->due_date);
        
        if ($dueDate->isPast()) {
            return -$dueDate->diffInDays($now);
        }
        
        return $dueDate->diffInDays($now);
    }
}
