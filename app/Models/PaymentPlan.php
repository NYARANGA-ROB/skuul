<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class PaymentPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'amount',
        'percentage',
        'installment_count',
        'installment_period',
        'start_date',
        'end_date',
        'is_active',
        'applicable_classes',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'applicable_classes' => 'array',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payment_plan');
    }

    public function schedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        $now = now()->format('Y-m-d');
        return $query->where('is_active', true)
                    ->where('start_date', '<=', $now)
                    ->where(function($q) use ($now) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $now);
                    });
    }

    public function scopeForClass($query, $classId)
    {
        return $query->whereJsonContains('applicable_classes', (string)$classId)
                    ->orWhereNull('applicable_classes');
    }

    // Helper Methods
    public function calculateInstallmentAmount($totalAmount)
    {
        if ($this->type === 'discount' || $this->type === 'scholarship') {
            if ($this->amount) {
                return $totalAmount - $this->amount;
            }
            if ($this->percentage) {
                return $totalAmount * (1 - ($this->percentage / 100));
            }
        } elseif ($this->type === 'installment' && $this->installment_count > 0) {
            return $totalAmount / $this->installment_count;
        }
        
        return $totalAmount;
    }

    public function generateSchedule($user, $feeInvoice, $totalAmount)
    {
        if ($this->type !== 'installment' || !$this->installment_count || !$this->installment_period) {
            return false;
        }

        $installmentAmount = $this->calculateInstallmentAmount($totalAmount);
        $dueDate = $this->start_date;
        $schedules = [];

        for ($i = 1; $i <= $this->installment_count; $i++) {
            $dueDate = $this->calculateNextDueDate($dueDate, $i);
            
            $schedules[] = [
                'payment_plan_id' => $this->id,
                'user_id' => $user->id,
                'fee_invoice_id' => $feeInvoice->id,
                'amount' => $i === $this->installment_count ? 
                    $totalAmount - (($i - 1) * $installmentAmount) : // Handle rounding on last installment
                    $installmentAmount,
                'balance' => $i === $this->installment_count ? 
                    $totalAmount - (($i - 1) * $installmentAmount) : 
                    $installmentAmount,
                'due_date' => $dueDate,
                'installment_number' => "{$i}/{$this->installment_count}",
                'is_final' => $i === $this->installment_count,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return PaymentSchedule::insert($schedules);
    }

    protected function calculateNextDueDate($startDate, $installmentNumber)
    {
        $date = \Carbon\Carbon::parse($startDate);
        
        switch ($this->installment_period) {
            case 'weekly':
                return $date->addWeeks($installmentNumber);
            case 'bi-weekly':
                return $date->addWeeks($installmentNumber * 2);
            case 'monthly':
                return $date->addMonths($installmentNumber);
            case 'quarterly':
                return $date->addMonths($installmentNumber * 3);
            default:
                return $date->addMonths($installmentNumber);
        }
    }

    public function applyToInvoice($user, $feeInvoice, $amount)
    {
        if ($this->type === 'installment') {
            return $this->generateSchedule($user, $feeInvoice, $amount);
        } else {
            // For scholarships and discounts, create a single payment record
            $discountedAmount = $this->calculateInstallmentAmount($amount);
            
            return Payment::create([
                'user_id' => $user->id,
                'fee_invoice_id' => $feeInvoice->id,
                'amount' => $discountedAmount,
                'balance' => $discountedAmount,
                'payment_plan_type' => get_class($this),
                'payment_plan_id' => $this->id,
                'status' => $this->type === 'scholarship' ? 'pending' : 'completed',
                'payment_mode' => 'system',
                'transaction_date' => now(),
            ]);
        }
    }
}
