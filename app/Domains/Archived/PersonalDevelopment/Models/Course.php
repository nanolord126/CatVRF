<?php declare(strict_types=1);

namespace App\Domains\Archived\PersonalDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Course extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'pd_courses';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'title',


            'content_summary',


            'modules',


            'price_kopecks',


            'correlation_id',


        ];


        protected $casts = [


            'modules' => 'json',


            'price_kopecks' => 'integer',


            'tenant_id' => 'integer',


        ];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant', function (Builder $builder) {


                if (function_exists('tenant') && tenant('id')) {


                    $builder->where('tenant_id', tenant('id'));


                }


            });


            static::creating(function (Course $model) {


                $model->uuid = $model->uuid ?? (string) Str::uuid();


                $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();


                if (function_exists('tenant')) {


                    $model->tenant_id = $model->tenant_id ?? (int) tenant('id');


                }


            });


        }


        public function enrollments(): HasMany


        {


            return $this->hasMany(Enrollment::class, 'course_id');


        }


        public function reviews(): MorphMany


        {


            return $this->morphMany(Review::class, 'reviewable');


        }


        /**


         * Получить структуру модулей в виде коллекции.


         */


        public function getModuleCollection(): \Illuminate\Support\Collection


        {


            return collect($this->modules);


        }
}
