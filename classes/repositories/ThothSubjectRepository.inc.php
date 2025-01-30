<?php

use ThothApi\GraphQL\Models\Subject as ThothSubject;

class ThothSubjectRepository
{
    protected $thothClient;

    public function __construct($thothClient)
    {
        $this->thothClient = $thothClient;
    }

    public function new(array $data = [])
    {
        return new ThothSubject($data);
    }

    public function get($thothSubjectId)
    {
        return $this->thothClient->subject($thothSubjectId);
    }

    public function add($thothSubject)
    {
        return $this->thothClient->createSubject($thothSubject);
    }

    public function edit($thothPatchSubject)
    {
        return $this->thothClient->updateSubject($thothPatchSubject);
    }

    public function delete($thothSubjectId)
    {
        return $this->thothClient->deleteSubject($thothSubjectId);
    }
}
