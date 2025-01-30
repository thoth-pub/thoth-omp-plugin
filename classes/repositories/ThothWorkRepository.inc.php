<?php

use ThothApi\GraphQL\Models\Work as ThothWork;

class ThothWorkRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothWork($data);
    }

    public function get($thothWorkId)
    {
        return $this->thothClient->work($thothWorkId);
    }

    public function add($thothWork)
    {
        return $this->thothClient->createWork($thothWork);
    }

    public function edit($thothPatchWork)
    {
        return $this->thothClient->updateWork($thothPatchWork);
    }

    public function delete($thothWorkId)
    {
        return $this->thothClient->deleteWork($thothWorkId);
    }
}
