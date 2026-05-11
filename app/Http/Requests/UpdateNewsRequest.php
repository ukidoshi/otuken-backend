<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('news.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'title.ru' => ['sometimes', 'required', 'string', 'max:255'],
            'title.tuv' => ['nullable', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'unique:news,slug,'.$this->route('news')],
            'locale' => ['sometimes', 'required', 'in:ru,tuv,en'],
            'status' => ['sometimes', 'required', 'in:draft,scheduled,published,hidden,archived'],
        ];
    }
}
