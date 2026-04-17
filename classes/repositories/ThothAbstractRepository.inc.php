<?php

use ThothApi\GraphQL\Models\AbstractText as ThothAbstract;

class ThothAbstractRepository
{
    private const MARKUP_FORMAT = 'HTML';

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
        return $this->thothClient->createAbstract($thothAbstract, self::MARKUP_FORMAT);
    }

    public function edit($thothPatchAbstract)
    {
        return $this->thothClient->updateAbstract($thothPatchAbstract, self::MARKUP_FORMAT);
    }

    public function delete($thothAbstractId)
    {
        return $this->thothClient->deleteAbstract($thothAbstractId);
    }
}
