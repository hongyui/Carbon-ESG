<?php

declare(strict_types=1);

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRequest extends FormRequest
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
            'datetime_start' => ['required', 'date'],
            'datetime_end' => ['required', 'date', 'after:datetime_start'],
            'content' => ['required', 'string', 'max:2000'],
            'before_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'after_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
