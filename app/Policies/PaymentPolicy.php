<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        // Only admins, bursars, and accountants can view all payments
        return $user->hasAnyRole(['admin', 'bursar', 'accountant']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return bool
     */
    public function view(User $user, Payment $payment)
    {
        // Admins and finance staff can view any payment
        if ($user->hasAnyRole(['admin', 'bursar', 'accountant'])) {
            return true;
        }

        // Parents can view payments for their children
        if ($user->hasRole('parent')) {
            return $user->children->contains('id', $payment->student_id);
        }

        // Students can view their own payments
        if ($user->hasRole('student')) {
            return $user->id === $payment->student_id;
        }

        // Teachers can view payments for students in their classes
        if ($user->hasRole('teacher')) {
            return $user->teacherClasses->contains('id', $payment->student->class_id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        // Only admins, bursars, and accountants can record payments
        return $user->hasAnyRole(['admin', 'bursar', 'accountant']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return bool
     */
    public function update(User $user, Payment $payment)
    {
        // Only admins and bursars can update payments
        return $user->hasAnyRole(['admin', 'bursar']);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return bool
     */
    public function delete(User $user, Payment $payment)
    {
        // Only admins can delete payments
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view payment receipts.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return bool
     */
    public function viewReceipt(User $user, Payment $payment)
    {
        // Same permissions as view
        return $this->view($user, $payment);
    }

    /**
     * Determine whether the user can process M-Pesa payments.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function processMpesa(User $user)
    {
        // Admins, bursars, and accountants can process M-Pesa payments
        return $user->hasAnyRole(['admin', 'bursar', 'accountant']);
    }
}
