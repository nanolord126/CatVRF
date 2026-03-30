<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CalculateRatingsJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    	public int $tries = 1;


    	public int $timeout = 120;


    	public function handle(): void


    	{


    		try {


    			DB::transaction(function () {


    				$studios = PhotoStudio::all();


    				foreach ($studios as $studio) {


    					$avgRating = $studio->reviews()->avg('rating') ?? 0;


    					$reviewCount = $studio->reviews()->count();


    					$studio->update([


    						'rating' => $avgRating,


    						'review_count' => $reviewCount,


    					]);


    				}


    				$photographers = Photographer::all();


    				foreach ($photographers as $photographer) {


    					$avgRating = $photographer->reviews()->avg('rating') ?? 0;


    					$photographer->update(['rating' => $avgRating]);


    				}


    				Log::channel('audit')->info('Photography: Batch ratings calculated', [


    					'studios_count' => $studios->count(),


    					'photographers_count' => $photographers->count(),


    				]);


    			});


    		} catch (\Exception $e) {


    			Log::channel('audit')->error('Photography: Ratings calculation failed', [


    				'error' => $e->getMessage(),


    			]);


    			throw $e;


    		}


    	}
}
