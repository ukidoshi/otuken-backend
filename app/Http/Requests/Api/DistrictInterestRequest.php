<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DistrictInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'min:10', 'max:30'],
            'districtId' => ['required', 'string', 'max:64'],
            'districtTitle' => ['required', 'string', 'max:200'],
            'districtType' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:64'],
            'page' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'name.min' => 'Имя слишком короткое.',
            'phone.required' => 'Укажите номер телефона.',
            'phone.min' => 'Номер телефона указан некорректно.',
            'districtId.required' => 'Не удалось определить район.',
            'districtTitle.required' => 'Не удалось определить район.',
        ];
    }
}
