<?php

use ThothApi\GraphQL\Models\Location as ThothLocation;

class ThothLocationRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothLocation($data);
    }

    public function get($thothLocationId)
    {
        return $this->thothClient->location($thothLocationId);
    }

    public function add($thothLocation)
    {
        return $this->thothClient->createLocation($thothLocation);
    }

    public function edit($thothPatchLocation)
    {
        return $this->thothClient->updateLocation($thothPatchLocation);
    }

    public function delete($thothLocationId)
    {
        return $this->thothClient->deleteLocation($thothLocationId);
    }
}
