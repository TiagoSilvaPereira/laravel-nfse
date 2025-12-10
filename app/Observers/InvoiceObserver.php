<?php

namespace App\Observers;

use App\Models\Invoice;

class InvoiceObserver
{
    public function creating(Invoice $invoice): void
    {
        if ($invoice->environment !== null) {
            return;
        }

        if (! $invoice->company) {
            return;
        }

        $invoice->environment = $invoice->company->environment;
    }
}
