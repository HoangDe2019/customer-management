<?php
// app/Policies/TransactionPolicy.php

namespace App\Policies;

use App\Models\User;
use App\Models\Transaction;

class TransactionPolicy
{
    public function viewAny(User $user)
    {
        return true; // All authenticated users can view their own transactions
    }

    public function view(User $user, Transaction $transaction)
    {
        return $user->isAdmin() || $user->hasAgentAccess($transaction->agent);
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Transaction $transaction)
    {
        return $user->isAdmin() || $user->hasAgentAccess($transaction->agent);
    }

    public function delete(User $user, Transaction $transaction)
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, Transaction $transaction)
    {
        return $user->isAdmin() || $user->hasAgentAccess($transaction->agent);
    }
}
