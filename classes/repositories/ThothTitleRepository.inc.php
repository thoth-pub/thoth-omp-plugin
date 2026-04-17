<?php

use ThothApi\GraphQL\Models\Title as ThothTitle;

class ThothTitleRepository
{
    private const MARKUP_FORMAT = 'PLAIN_TEXT';

    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothTitle($data);
    }

    public function add($thothTitle)
    {
        return $this->thothClient->createTitle($thothTitle, self::MARKUP_FORMAT);
    }

    public function edit($thothPatchTitle)
    {
        return $this->thothClient->updateTitle($thothPatchTitle, self::MARKUP_FORMAT);
    }

    public function delete($thothTitleId)
    {
        return $this->thothClient->deleteTitle($thothTitleId);
    }
}
