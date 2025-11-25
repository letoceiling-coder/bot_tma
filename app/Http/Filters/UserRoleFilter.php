<?php


namespace App\Http\Filters;


use Illuminate\Database\Eloquent\Builder;

class UserRoleFilter extends AbstractFilter
{


    public const SEARCH = 'search';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const SORT = 'sort';


    protected function getCallbacks(): array
    {
        return [
            self::DESCRIPTION => [$this, 'active'],

            self::SEARCH => [$this, 'search'],


            self::SORT => [$this, 'sort'],
            self::NAME => [$this, 'name'],



        ];
    }


    public function sort(Builder $builder, $value)
    {
        if ($value == 'orderBy'){
            $builder->orderBy('id');
        }elseif ($value == 'orderByDesc'){
            $builder->orderByDesc('id');
        }

    }

    public function search(Builder $builder, $value)
    {
        $builder->where('name', 'LIKE', "%$value%")
            ->orWhere('description', 'LIKE', "%$value%");
    }


    public function name(Builder $builder, $value)
    {
        $builder->where('name', 'LIKE', "%$value%");
    }

    public function description(Builder $builder, $value)
    {
        $builder->where('description', 'LIKE', "%$value%");
    }







}
