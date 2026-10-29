<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Sample location data for Yemen.
     * In production, this should come from database tables.
     */
    private array $locations = [
        'sanaa' => [
            'name' => 'Sana\'a',
            'name_ar' => 'صنعاء',
            'cities' => [
                'sanaa_city' => [
                    'name' => 'Sana\'a City',
                    'name_ar' => 'مدينة صنعاء',
                    'areas' => ['Al-Tahrir', 'Al-Hasabah', 'Al-Jiraf', 'Al-Sabeen', 'Bani Al-Harith'],
                ],
                'hamdan' => [
                    'name' => 'Hamdan',
                    'name_ar' => 'همدان',
                    'areas' => ['Al-Rawdah', 'Bait Al-Raei', 'Al-Dabab', 'Shibam'],
                ],
            ],
        ],
        'aden' => [
            'name' => 'Aden',
            'name_ar' => 'عدن',
            'cities' => [
                'aden_city' => [
                    'name' => 'Aden City',
                    'name_ar' => 'مدينة عدن',
                    'areas' => ['Al-Tawahi', 'Al-Mualla', 'Khormaksar', 'Sheikh Othman', 'Al-Mansoura'],
                ],
            ],
        ],
        'taiz' => [
            'name' => 'Taiz',
            'name_ar' => 'تعز',
            'cities' => [
                'taiz_city' => [
                    'name' => 'Taiz City',
                    'name_ar' => 'مدينة تعز',
                    'areas' => ['Al-Jomhouriya', 'Al-Qahira', 'Al-Masbah', 'Al-Muraba'],
                ],
            ],
        ],
        'hodeidah' => [
            'name' => 'Hodeidah',
            'name_ar' => 'الحديدة',
            'cities' => [
                'hodeidah_city' => [
                    'name' => 'Hodeidah City',
                    'name_ar' => 'مدينة الحديدة',
                    'areas' => ['Al-Hawak', 'Al-Durayhimi', 'Al-Marawa'],
                ],
            ],
        ],
        'ibb' => [
            'name' => 'Ibb',
            'name_ar' => 'إب',
            'cities' => [
                'ibb_city' => [
                    'name' => 'Ibb City',
                    'name_ar' => 'مدينة إب',
                    'areas' => ['Al-Qafr', 'Al-Sabra', 'Al-Mashannah', 'Al-Nadira'],
                ],
            ],
        ],
        'hadramout' => [
            'name' => 'Hadramout',
            'name_ar' => 'حضرموت',
            'cities' => [
                'mukalla' => [
                    'name' => 'Mukalla',
                    'name_ar' => 'المكلا',
                    'areas' => ['Al-Ghail', 'Al-Shihr', 'Ghayl Ba Wazir'],
                ],
                'seiyun' => [
                    'name' => 'Seiyun',
                    'name_ar' => 'سيئون',
                    'areas' => ['Al-Qatn', 'Al-Houta', 'Shibam'],
                ],
            ],
        ],
        'marib' => [
            'name' => 'Marib',
            'name_ar' => 'مأرب',
            'cities' => [
                'marib_city' => [
                    'name' => 'Marib City',
                    'name_ar' => 'مدينة مأرب',
                    'areas' => ['Al-Jubah', 'Al-Abdiyah', 'Sirwah'],
                ],
            ],
        ],
    ];

    /**
     * Get all governorates.
     */
    public function governorates(): JsonResponse
    {
        $governorates = [];
        foreach ($this->locations as $id => $data) {
            $governorates[] = [
                'id' => $id,
                'name' => $data['name'],
                'name_ar' => $data['name_ar'],
            ];
        }

        return ApiResponse::success($governorates);
    }

    /**
     * Get cities for a specific governorate.
     */
    public function cities(Request $request): JsonResponse
    {
        $request->validate([
            'governorate' => 'required|string',
        ]);

        $governorateId = $request->input('governorate');

        if (!isset($this->locations[$governorateId])) {
            return ApiResponse::error('Governorate not found', 404);
        }

        $cities = [];
        foreach ($this->locations[$governorateId]['cities'] as $id => $data) {
            $cities[] = [
                'id' => $id,
                'name' => $data['name'],
                'name_ar' => $data['name_ar'],
            ];
        }

        return ApiResponse::success($cities);
    }

    /**
     * Get areas/villages for a specific city.
     */
    public function areas(Request $request): JsonResponse
    {
        $request->validate([
            'governorate' => 'required|string',
            'city' => 'required|string',
        ]);

        $governorateId = $request->input('governorate');
        $cityId = $request->input('city');

        if (!isset($this->locations[$governorateId])) {
            return ApiResponse::error('Governorate not found', 404);
        }

        if (!isset($this->locations[$governorateId]['cities'][$cityId])) {
            return ApiResponse::error('City not found', 404);
        }

        $areas = [];
        foreach ($this->locations[$governorateId]['cities'][$cityId]['areas'] as $area) {
            $areas[] = [
                'id' => str_replace(' ', '_', strtolower($area)),
                'name' => $area,
            ];
        }

        return ApiResponse::success($areas);
    }
}
