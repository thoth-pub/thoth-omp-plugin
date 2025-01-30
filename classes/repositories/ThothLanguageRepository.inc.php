<?php

use ThothApi\GraphQL\Models\Language as ThothLanguage;

class ThothLanguageRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothLanguage($data);
    }

    public function get($thothLanguageId)
    {
        return $this->thothClient->language($thothLanguageId);
    }

    public function add($thothLanguage)
    {
        return $this->thothClient->createLanguage($thothLanguage);
    }

    public function edit($thothPatchLanguage)
    {
        return $this->thothClient->updateLanguage($thothPatchLanguage);
    }

    public function delete($thothLanguageId)
    {
        return $this->thothClient->deleteLanguage($thothLanguageId);
    }
}
