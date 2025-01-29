<?php

use ThothApi\GraphQL\Models\Contribution as ThothContribution;

class ThothContributionRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothContribution($data);
    }

    public function get($thothContributionId)
    {
        return $this->thothClient->Contribution($thothContributionId);
    }

    public function add($thothContribution)
    {
        return $this->thothClient->createContribution($thothContribution);
    }

    public function edit($thothPatchContribution)
    {
        return $this->thothClient->updateContribution($thothPatchContribution);
    }

    public function delete($thothContributionId)
    {
        return $this->thothClient->deleteContribution($thothContributionId);
    }
}
