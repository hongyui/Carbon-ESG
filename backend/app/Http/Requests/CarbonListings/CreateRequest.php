<?php

declare(strict_types=1);

namespace App\Http\Requests\CarbonListings;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'hectares' => ['required', 'numeric', 'gt:0'],
            'tonnes_co2e' => ['required', 'numeric', 'gt:0'],
            'location' => ['required', 'string', 'max:255'],
            'price_twd' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
