<?php

namespace Railken\Amethyst\Managers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Railken\Amethyst\Exceptions;
use Railken\Amethyst\Models\DataBuilder;
use Railken\Lem\Manager;
use Railken\Lem\Result;

class DataBuilderManager extends Manager
{
    /**
     * Describe this manager.
     *
     * @var string
     */
    public $comment = '...';

    /**
     * Register Classes.
     */
    public function registerClasses()
    {
        return Config::get('amethyst.data-builder.managers.data-builder');
    }

    /**
     * Validate data.
     *
     * @param DataBuilder $builder
     * @param array       $data
     *
     * @return \Railken\Lem\Contracts\ResultContract
     */
    public function validateRaw(DataBuilder $builder, array $data = [])
    {
        $schema = Collection::make($builder->input)->map(function ($value) {
            return Arr::get((array) $value, 'validation');
        })->toArray();

        $result = new Result();
        $result->addErrors($this->getValidator()->raw($schema, $data));

        return $result;
    }

    /**
     * Render an email.
     *
     * @param DataBuilder $builder
     * @param array       $data
     *
     * @return \Railken\Lem\Contracts\ResultContract
     */
    public function build(DataBuilder $builder, array $data = [])
    {
        $input = $builder->input;

        if ($data === null) {
            $data = $builder->mock_data;
        }

        $result = $this->validateRaw($builder, (array) $data);

        try {
            $query = $builder->newInstanceQuery((array) $data);

            $data = array_merge($data, $builder->parse($query->get())->toArray());

            $result->setResources(new Collection([$data]));
        } catch (\PDOException | \Railken\SQ\Exceptions\QuerySyntaxException $e) {
            $e = new Exceptions\DataBuilderBuildException($e->getMessage());
            $result->addErrors(new Collection([$e]));
        }

        return $result;
    }
}