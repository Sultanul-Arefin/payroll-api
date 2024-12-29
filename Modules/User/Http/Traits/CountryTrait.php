<?php

namespace Modules\User\Http\Traits;

use App\Models\Country;

trait CountryTrait
{
    public function country_list()
    {
        $countries = Country::query()
            ->when(! is_null(request('search')), function ($query) {
                $query->where(
                    'name',
                    'LIKE',
                    '%'.request('search').'%'
                );
            })
            ->when(! is_null(request('starts_with')), function ($query) {
                $query->where(
                    'name',
                    'LIKE',
                    request('starts_with').'%'
                );
            })
            ->select('id', 'name')
            ->get();

        return apiResponse(
            data: $countries,
            message: 'Countries Get Successfully!',
            status: 'success',
            statusCode: 200
        );
    }
}
