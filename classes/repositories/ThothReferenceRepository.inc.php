<?php

use ThothApi\GraphQL\Models\Reference as ThothReference;

class ThothReferenceRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothReference($data);
    }

    public function get($thothReferenceId)
    {
        return $this->thothClient->reference($thothReferenceId);
    }

    public function add($thothReference)
    {
        return $this->thothClient->createReference($thothReference);
    }

    public function edit($thothPatchReference)
    {
        return $this->thothClient->updateReference($thothPatchReference);
    }

    public function delete($thothReferenceId)
    {
        return $this->thothClient->deleteReference($thothReferenceId);
    }
}
