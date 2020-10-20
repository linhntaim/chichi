<?php

namespace App\ModelTraits;

use Illuminate\Database\Eloquent\Model;

trait FromModelTrait
{
    /**
     * @param Model|mixed $model
     * @return static
     */
    public static function factoryFromModel($model)
    {
        return (new static())->fromModel($model);
    }

    /**
     * @param Model|mixed $model
     * @return static
     */
    public function fromModel($model)
    {
        return $this->setRawAttributes($model->getAttributes(), true);
    }
}