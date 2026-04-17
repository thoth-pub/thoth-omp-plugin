<?php

use ThothApi\GraphQL\Client as ThothClient;

import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.thoth.classes.i18n.ThothLocaleCode');

class ThothLocaleCodeTest extends PKPTestCase
{
    public function testFromPkpLocalePreservesSupportedLocaleGranularity()
    {
        $this->assertSame('EN_US', ThothLocaleCode::fromPkpLocale('en_US'));
        $this->assertSame('PT_BR', ThothLocaleCode::fromPkpLocale('pt-BR'));
        $this->assertSame('ZH_HANT_TW', ThothLocaleCode::fromPkpLocale('zh_Hant_TW'));
    }

    public function testFromPkpLocaleReturnsNullForUnsupportedLocale()
    {
        $this->assertNull(ThothLocaleCode::fromPkpLocale('zz_ZZ'));
        $this->assertNull(ThothLocaleCode::fromPkpLocale('und'));
        $this->assertNull(ThothLocaleCode::fromPkpLocale(null));
    }
}
