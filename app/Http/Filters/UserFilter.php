<?php


namespace App\Http\Filters;


use Illuminate\Database\Eloquent\Builder;

class UserFilter extends AbstractFilter
{

    public const ACTIVE = 'active';
    public const LIMIT = 'limit';
    public const SEARCH = 'search';

    public const ROLE_ID = 'role_id';
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const SORT = 'sort';



    protected function getCallbacks(): array
    {
        return [
            self::ACTIVE => [$this, 'active'],
            self::LIMIT => [$this, 'limit'],
            self::SEARCH => [$this, 'search'],

            self::ROLE_ID => [$this, 'role'],
            self::NAME => [$this, 'name'],
            self::EMAIL => [$this, 'email'],
            self::SORT => [$this, 'sort'],


        ];
    }

    public function active(Builder $builder, $value)
    {

//        $builder->where('active', isBoolean($value));
    }

    public function sort(Builder $builder, $value)
    {
        if ($value == 'orderBy'){
            $builder->orderBy('id');
        }elseif ($value == 'orderByDesc'){
            $builder->orderByDesc('id');
        }

    }

    public function limit(Builder $builder, $value)
    {

        $builder->limit($value);

    }

    public function search(Builder $builder, $value)
    {
        $builder->where('name', 'LIKE', "%$value%")
            ->orWhere('email', 'LIKE', "%$value%");
    }


    public function name(Builder $builder, $value)
    {
        $builder->where('name', 'LIKE', "%$value%");
    }

    public function email(Builder $builder, $value)
    {
        $builder->where('email', 'LIKE', "%$value%");
    }

    public function role(Builder $builder, $value)
    {

        $builder->whereHas('role', function ($query) use ($value) {
            return $query->where('id', $value);
        });
    }





}
