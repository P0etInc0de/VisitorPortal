<?php

namespace App\Http\Requests;

use App\Models\Visit;

class UpdateVisitRequest extends StoreVisitRequest
{
    public function authorize(): bool
    {
        $visit = $this->route('visit');

        return $visit instanceof Visit && $this->user()?->can('update', $visit);
    }
}
