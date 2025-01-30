<?php

use ThothApi\GraphQL\Models\Publication as ThothPublication;

class ThothPublicationRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothPublication($data);
    }

    public function get($thothPublicationId)
    {
        return $this->thothClient->publication($thothPublicationId);
    }

    public function add($thothPublication)
    {
        return $this->thothClient->createPublication($thothPublication);
    }

    public function edit($thothPatchPublication)
    {
        return $this->thothClient->updatePublication($thothPatchPublication);
    }

    public function delete($thothPublicationId)
    {
        return $this->thothClient->deletePublication($thothPublicationId);
    }
}
