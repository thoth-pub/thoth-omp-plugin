<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.templateFilters.ThothFeatureVideoTemplateFilter');

class ThothFeatureVideoTemplateFilterTest extends PKPTestCase
{
    public function testAddsFeatureVideoToBookMainEntry(): void
    {
        $filter = new FeatureVideoTemplateFilterStub([
            'title' => 'Book & trailer',
            'url' => 'https://cdn.thoth.pub/trailer.mp4?x=1&y=2',
            'width' => 640,
            'height' => 360,
        ]);
        $manager = new FeatureVideoBookTemplateManagerStub();
        $output = '<div class="main_entry"></div><!-- .main_entry -->';

        $filter->registerFilter($manager, 'frontend/pages/book.tpl');
        $result = $manager->apply($output);

        $this->assertStringContainsString('class="item thoth_feature_video"', $result);
        $this->assertStringContainsString('Book &amp; trailer', $result);
        $this->assertStringContainsString('src="https://cdn.thoth.pub/trailer.mp4?x=1&amp;y=2"', $result);
        $this->assertStringContainsString('width="640" height="360"', $result);
    }

    public function testDoesNotRenderUnsafeUrl(): void
    {
        $filter = new FeatureVideoTemplateFilterStub(['url' => 'javascript:alert(1)']);
        $manager = new FeatureVideoBookTemplateManagerStub();
        $output = '<div class="main_entry"></div><!-- .main_entry -->';
        $filter->registerFilter($manager, 'frontend/pages/book.tpl');
        $this->assertSame($output, $manager->apply($output));
    }
}

class FeatureVideoBookTemplateManagerStub
{
    private $publication;
    private $filter;

    public function __construct()
    {
        $this->publication = new class () {
            public function getData($name)
            {
                return $name === 'thothWorkId' ? 'work-id' : null;
            }
        };
    }

    public function getTemplateVars($name)
    {
        return $this->publication;
    }
    public function registerFilter($type, $callback): void
    {
        $this->filter = $callback;
    }
    public function apply($output)
    {
        return $this->filter ? call_user_func($this->filter, $output) : $output;
    }
}

class FeatureVideoTemplateFilterStub extends ThothFeatureVideoTemplateFilter
{
    private $video;

    public function __construct(array $video)
    {
        $this->video = $video;
    }

    protected function loadVideo($workId)
    {
        return $this->video;
    }
}
