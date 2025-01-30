<?php

use ThothApi\GraphQL\Models\WorkRelation as ThothWorkRelation;

class ThothWorkRelationRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothWorkRelation($data);
    }

    public function add($thothWorkRelation)
    {
        return $this->thothClient->createWorkRelation($thothWorkRelation);
    }

    public function edit($thothPatchWorkRelation)
    {
        return $this->thothClient->updateWorkRelation($thothPatchWorkRelation);
    }

    public function delete($thothWorkRelationId)
    {
        return $this->thothClient->deleteWorkRelation($thothWorkRelationId);
    }
}
