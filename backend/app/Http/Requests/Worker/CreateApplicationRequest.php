<?php

declare(strict_types=1);

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

class CreateApplicationRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:2000'],
            'has_experience' => ['required', 'boolean'],
            'age' => ['required', 'integer', 'min:18', 'max:99'],
            'residence' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
        ];
    }
}
