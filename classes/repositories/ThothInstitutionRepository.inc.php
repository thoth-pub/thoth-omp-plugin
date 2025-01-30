<?php

use ThothApi\GraphQL\Models\Institution as ThothInstitution;

class ThothInstitutionRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothInstitution($data);
    }

    public function get($thothInstitutionId)
    {
        return $this->thothClient->institution($thothInstitutionId);
    }

    public function add($thothInstitution)
    {
        return $this->thothClient->createInstitution($thothInstitution);
    }

    public function edit($thothPatchInstitution)
    {
        return $this->thothClient->updateInstitution($thothPatchInstitution);
    }

    public function delete($thothInstitutionId)
    {
        return $this->thothClient->deleteInstitution($thothInstitutionId);
    }
}
