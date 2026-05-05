<?php

namespace App\Http\Requests;

class UpdateVisitRequest extends StoreVisitRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('EditAny:Visit') || $this->user()->can('EditDepartment:Visit') || $this->user()->can('EditOwn:Visit');
    }
}
