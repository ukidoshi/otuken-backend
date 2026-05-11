<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('news.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'title.ru' => ['required', 'string', 'max:255'],
            'title.tuv' => ['nullable', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:news,slug'],
            'locale' => ['required', 'in:ru,tuv,en'],
            'status' => ['required', 'in:draft,scheduled,published,hidden,archived'],
        ];
    }
}
