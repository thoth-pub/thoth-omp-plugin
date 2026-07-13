<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.templateFilters.ThothFeatureVideoTemplateFilter');

class ThothFeatureVideoTemplateFilterTest extends PKPTestCase
{
    public function testAddsFeatureVideoToBookMainEntry(): void
    {
        $filter = new ThothFeatureVideoTemplateFilter();
        $manager = new FeatureVideoBookTemplateManagerStub([
            'thothFeatureVideoTitle' => 'Book & trailer',
            'thothFeatureVideoUrl' => 'https://cdn.thoth.pub/trailer.mp4?x=1&y=2',
            'thothFeatureVideoWidth' => 640,
            'thothFeatureVideoHeight' => 360,
        ]);
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
        $filter = new ThothFeatureVideoTemplateFilter();
        $manager = new FeatureVideoBookTemplateManagerStub([
            'thothFeatureVideoUrl' => 'javascript:alert(1)',
        ]);
        $output = '<div class="main_entry"></div><!-- .main_entry -->';
        $filter->registerFilter($manager, 'frontend/pages/book.tpl');
        $this->assertSame($output, $manager->apply($output));
    }
}

class FeatureVideoBookTemplateManagerStub
{
    private $publication;
    private $filter;

    public function __construct(array $data)
    {
        $this->publication = new class ($data) {
            private $data;
            public function __construct(array $data) { $this->data = $data; }
            public function getData($name) { return $this->data[$name] ?? null; }
        };
    }

    public function getTemplateVars($name) { return $this->publication; }
    public function registerFilter($type, $callback): void { $this->filter = $callback; }
    public function apply($output) { return $this->filter ? call_user_func($this->filter, $output) : $output; }
}
