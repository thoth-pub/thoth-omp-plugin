<?php

namespace APP\plugins\generic\thoth\classes\components\forms;

class ThothValidationMessageFormatter
{
    public static function formatWarning(array $errors): string
    {
        $message = '<div class="pkpNotification pkpNotification--warning">';
        $message .= htmlspecialchars(
            __('plugins.generic.thoth.register.warning'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $message .= '<ul>';

        foreach ($errors as $error) {
            $message .= '<li>' . htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }

        return $message . '</ul></div>';
    }
}
