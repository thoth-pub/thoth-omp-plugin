<?php

use ThothApi\GraphQL\Enums\MarkupFormat;
use ThothApi\GraphQL\Inputs\PatchAbstract as ThothAbstract;

class ThothAbstractRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothAbstract($data);
    }

    public function add($thothAbstract)
    {
        return $this->thothClient->createAbstract(MarkupFormat::HTML, $thothAbstract);
    }

    public function edit($thothPatchAbstract)
    {
        return $this->thothClient->updateAbstract(MarkupFormat::HTML, $thothPatchAbstract);
    }

    public function delete($thothAbstractId)
    {
        return $this->thothClient->deleteAbstract($thothAbstractId);
    }
}
