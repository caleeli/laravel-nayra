<?php
namespace ProcessMaker\Laravel\Contracts;

interface RequestRepositoryInterface
{
    /**
     * @param string $id
     *
     * @return Request
     */
    public function find($id);

    /**
     * @param array $data
     *
     * @return Request
     */
    public function make(array $data);
}
