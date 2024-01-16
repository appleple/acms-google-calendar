<?php

namespace Acms\Plugins\GoogleCalendar;

use ACMS_POST_Form_Submit;

class Hook
{
    /**
     * POSTモジュール処理前
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param \ACMS_POST $thisModule
     */
    public function afterPostFire($thisModule)
    {
        // Hook処理動作条件
        if (!($thisModule instanceof ACMS_POST_Form_Submit)) {
            return;
        }

        if (!$thisModule->Post->get('id')) {
            return;
        }
        if ($thisModule
            ->loadForm($thisModule->Post->get('id'))['data']
            ->getChild('mail')
            ->get('calendar_void')
            !== 'on'
        ) {
            return;
        };
        if (!$thisModule->Post->isValidAll()) {
            return;
        }
        $step = $thisModule->Post->get('error');
        if (empty($step)) {
            $step = $thisModule->Get->get('step');
        }
        $step = $thisModule->Post->get('step', $step);
        if (in_array($step, ['forbidden', 'repeated'])) {
            return;
        }

        $formCode = $thisModule->Post->get('id');

        try {
            $engine = new Engine($formCode, $thisModule);
            $engine->send();
        } catch (\Exception $e) {
            userErrorLog('ACMS Warning: Google Calendar plugin, ' . $e->getMessage());
        }
    }
}
