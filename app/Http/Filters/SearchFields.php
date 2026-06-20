<?php

namespace App\Http\Filters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchFields implements Filter
{
    public function __invoke(Builder $query, $value, string $fields): Builder
    {
        if (!is_array($fields)) $fields = explode(',', $fields);

        $like = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $query->where(function($query) use ($fields, $value, $like) {
            foreach ($fields as $field) {
                if (stripos($field, '.')) {
                    $els = explode('.', $field);
                    $query->orWhereHas($els[0], function($query) use ($value, $els, $like) {
                        $query->where($els[1], $like, "%$value%");
                    });
                } else {
                    $query->orWhere($field, $like, "%$value%");
                }
            }
        });

        return $query;
    }
}
