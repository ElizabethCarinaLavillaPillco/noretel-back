<?php

namespace Modules\Services\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'upload_speed' => 'required|integer|min:0',
            'download_speed' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'commitment_period' => 'nullable|integer|min:0',
            'active' => 'boolean',
            'is_featured' => 'nullable|boolean',
            'featured_order' => 'nullable|integer|min:0',

            'promotion_ids' => 'nullable|array',
            'promotion_ids.*' => 'exists:promotions,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'service_id.required' => 'El servicio es obligatorio.',
            'service_id.exists' => 'El servicio seleccionado no existe.',
            'name.required' => 'El nombre del plan es obligatorio.',
            'name.max' => 'El nombre del plan no puede tener más de 100 caracteres.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número.',
            'price.min' => 'El precio no puede ser negativo.',
            'upload_speed.required' => 'La velocidad de subida es obligatoria.',
            'upload_speed.integer' => 'La velocidad de subida debe ser un número entero.',
            'upload_speed.min' => 'La velocidad de subida no puede ser negativa.',
            'download_speed.required' => 'La velocidad de bajada es obligatoria.',
            'download_speed.integer' => 'La velocidad de bajada debe ser un número entero.',
            'download_speed.min' => 'La velocidad de bajada no puede ser negativa.',
            'commitment_period.integer' => 'El período de permanencia debe ser un número entero.',
            'commitment_period.min' => 'El período de permanencia no puede ser negativo.',
            'promotion_ids.*.exists' => 'Una de las promociones seleccionadas no existe.'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Convertir active a boolean
        if ($this->has('active')) {
            $this->merge([
                'active' => filter_var($this->active, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    
        if ($this->has('features') && is_string($this->features)) {
            $features = json_decode($this->features, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['features' => $features]);
            }
        }
    }
}
