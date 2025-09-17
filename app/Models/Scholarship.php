<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Scholarship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'amount',
        'status',
        'start_date',
        'end_date',
        'approval_notes',
        'approved_by',
        'approved_at',
        'supporting_documents',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'supporting_documents' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payment_plan');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeActive($query)
    {
        $now = now()->format('Y-m-d');
        return $query->where('status', 'approved')
                    ->where('start_date', '<=', $now)
                    ->where(function($q) use ($now) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $now);
                    });
    }

    // Helper Methods
    public function approve($approverId, $notes = null)
    {
        $this->status = 'approved';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->rejection_reason = null;
        $this->save();

        // Apply scholarship to any pending fee invoices
        $this->applyToPendingInvoices();

        return $this;
    }

    public function reject($approverId, $reason = null)
    {
        $this->status = 'rejected';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->save();

        return $this;
    }

    public function revoke($reason = null)
    {
        $this->status = 'revoked';
        $this->rejection_reason = $reason;
        $this->save();

        // TODO: Handle any necessary cleanup or notifications
        
        return $this;
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'revoked' => 'bg-gray-100 text-gray-800',
        ];

        $status = strtolower($this->status);
        $class = $statuses[$status] ?? 'bg-gray-100 text-gray-800';
        
        return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $class . '">' . ucfirst($status) . '</span>';
    }

    public function getSupportingDocumentsUrlsAttribute()
    {
        if (empty($this->supporting_documents)) {
            return [];
        }

        return array_map(function($document) {
            return [
                'name' => basename($document),
                'url' => Storage::url($document),
                'path' => $document
            ];
        }, $this->supporting_documents);
    }

    public function addSupportingDocument($file)
    {
        $path = $file->store('scholarships/documents/' . $this->id, 'public');
        
        $documents = $this->supporting_documents ?? [];
        $documents[] = $path;
        
        $this->supporting_documents = $documents;
        $this->save();
        
        return $path;
    }

    public function removeSupportingDocument($path)
    {
        $documents = $this->supporting_documents ?? [];
        
        if (($key = array_search($path, $documents)) !== false) {
            unset($documents[$key]);
            
            // Reset array keys
            $documents = array_values($documents);
            
            $this->supporting_documents = $documents;
            $this->save();
            
            // Delete the file from storage
            Storage::disk('public')->delete($path);
            
            return true;
        }
        
        return false;
    }

    protected function applyToPendingInvoices()
    {
        // Get all pending fee invoices for this student
        $invoices = FeeInvoice::where('user_id', $this->user_id)
            ->where('status', '!=', 'paid')
            ->get();

        foreach ($invoices as $invoice) {
            $this->applyToInvoice($invoice);
        }
    }

    public function applyToInvoice($feeInvoice)
    {
        // Create a payment record for this scholarship
        $payment = Payment::create([
            'user_id' => $this->user_id,
            'fee_invoice_id' => $feeInvoice->id,
            'amount' => $this->amount,
            'balance' => $this->amount,
            'status' => 'completed',
            'payment_mode' => 'scholarship',
            'transaction_date' => now(),
            'payment_plan_type' => get_class($this),
            'payment_plan_id' => $this->id,
        ]);

        // Update the invoice balance
        $feeInvoice->updateBalance($this->amount);

        return $payment;
    }
}
