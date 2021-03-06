<?php

namespace Acms\Plugins\GoogleCalendar;

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
        $moduleName = get_class($thisModule);

        /* プログラム動作条件 */
        if ($moduleName !== 'ACMS_POST_Form_Submit') {
            return;
        }
        if (!$thisModule->Post->isValidAll()) {
            return;
        }
        if ($thisModule->Post->get('step') !== "result") {
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
