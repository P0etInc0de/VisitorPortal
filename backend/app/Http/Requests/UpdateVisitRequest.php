<?php

namespace App\Http\Requests;

class UpdateVisitRequest extends StoreVisitRequest {
	public function authorize(): bool
    {
        return $this->user()->can('editAny', Visit::class) || $this->user()->can('editDepartment', Visit::class) || $this->user()->can('editOwn', Visit::class);
    }
}
