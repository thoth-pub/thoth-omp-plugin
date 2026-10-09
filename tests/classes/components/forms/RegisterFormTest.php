<?php

/**
 * @file plugins/generic/thoth/tests/classes/components/forms/RegisterFormTest.php
 *
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

import('plugins.generic.thoth.classes.components.forms.RegisterForm');
import('lib.pkp.tests.PKPTestCase');

class RegisterFormTest extends PKPTestCase
{
    public function testConfirmationIncludesForthcomingNotice(): void
    {
        $form = new RegisterForm('/register', [], WORK_TYPE_AUTHORED_WORK, []);
        $confirmation = $form->fields[0]->getConfig();

        self::assertStringContainsString(__('plugins.generic.thoth.register.confirmation'), $confirmation['description']);
        self::assertStringContainsString(__('plugins.generic.thoth.register.forthcomingNotice'), $confirmation['description']);
    }
}
