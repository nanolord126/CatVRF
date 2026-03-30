<?php declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Upload3DModelRequest extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function authorize(): bool
        {
            // SECURITY: Проверка что пользователь имеет право загружать модели
            return auth()->check() && auth()->user()->can('upload_3d_models');
        }

        public function rules(): array
        {
            return [
                'model' => [
                    'required',
                    'file',
                    $this->file->types(['glb', 'gltf', 'obj', 'fbx'])
                        ->max(52428800) // 50MB
                        ->min(100), // 100 байт минимум
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'min:3',
                ],
                'description' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ];
        }

        public function messages(): array
        {
            return [
                'model.required' => 'Файл модели обязателен',
                'model.file' => 'Нужен файл, а не текст',
                'model.mimes' => 'Поддерживаются только форматы: GLB, GLTF, OBJ, FBX',
                'model.max' => 'Файл превышает лимит 50MB',
                'name.required' => 'Название модели обязательно',
                'name.max' => 'Название не может быть длиннее 255 символов',
            ];
        }
}
