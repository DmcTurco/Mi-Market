<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

/**
 * Empresa del usuario conectado, sea la propia empresa (panel company) o su empleado (panel employee).
 * El middleware auth:{guard} deja ese guard como predeterminado, así que auth()->user() es el correcto.
 */
trait ScopedToCompany
{
    protected function companyId(): int
    {
        $user = auth()->user();

        return (int) ($user instanceof Company ? $user->id : $user->company_id);
    }

    protected function company(): Company
    {
        $user = auth()->user();

        return $user instanceof Company ? $user : $user->company;
    }

    /** 404 si el registro es de otra empresa */
    protected function ensureOwned(Model $model): void
    {
        abort_unless((int) $model->company_id === $this->companyId(), 404);
    }
}
