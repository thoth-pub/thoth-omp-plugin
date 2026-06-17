<?php

use ThothApi\GraphQL\Enums\MarkupFormat;
use ThothApi\GraphQL\Inputs\PatchTitle as ThothTitle;

class ThothTitleRepository
{
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
        return $this->thothClient->createTitle(MarkupFormat::PLAIN_TEXT, $thothTitle);
    }

    public function edit($thothPatchTitle)
    {
        return $this->thothClient->updateTitle(MarkupFormat::PLAIN_TEXT, $thothPatchTitle);
    }

    public function delete($thothTitleId)
    {
        return $this->thothClient->deleteTitle($thothTitleId);
    }
}
