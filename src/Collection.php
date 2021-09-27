<?php

namespace Laraneat\Modules;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as BaseCollection;
use Laraneat\Modules\Exceptions\InvalidJsonException;

class Collection extends BaseCollection
{
    /**
     * Get items collections.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     * @throws InvalidJsonException|FileNotFoundException
     */
    public function toArray(): array
    {
        return array_map(static function ($value) {
            if ($value instanceof Module) {
                $attributes = $value->json()->getAttributes();
                $attributes["path"] = $value->getPath();

                return $attributes;
            }

            return $value instanceof Arrayable ? $value->toArray(): $value;
        }, $this->items);
    }
}
