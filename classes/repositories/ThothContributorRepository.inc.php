<?php

use ThothApi\GraphQL\Models\Contributor as ThothContributor;

class ThothContributorRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothContributor($data);
    }

    public function get($thothContributorId)
    {
        return $this->thothClient->Contributor($thothContributorId);
    }

    public function add($thothContributor)
    {
        return $this->thothClient->createContributor($thothContributor);
    }

    public function edit($thothPatchContributor)
    {
        return $this->thothClient->updateContributor($thothPatchContributor);
    }

    public function delete($thothContributorId)
    {
        return $this->thothClient->deleteContributor($thothContributorId);
    }
}
