<?php

namespace Acms\Plugins\GoogleCalendar;

use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;
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
        $id = $thisModule->Post->get('id');
        if (!$id) {
            return;
        }
        $info = $thisModule->loadForm($id);
        if (empty($info)) {
            return;
        }
        if ($info['data']->getChild('mail')->get('calendar_void') !== 'on') {
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

        // Google カレンダーへの登録は、結果を画面に出さないため、応答を返したあとに行う（Google が遅くても、フォームの応答を待たせない）
        $this->runAfterResponse(function () use ($formCode, $thisModule) {
            try {
                $engine = new Engine($formCode, $thisModule);
                $engine->send();
            } catch (\Throwable $th) {
                Logger::error('【GoogleCalendar】GoogleCalendar へのデータ登録に失敗しました。', Common::exceptionArray($th));
            }
        });
    }

    /**
     * 応答を返したあとに処理を実行する（本体が対応しているときだけ）
     *
     * Why not: Response::afterResponse() を直接呼ばない。この仕組みが無い古い本体では、呼び出しが失敗して、
     * フォームの送信に影響するため、本体に afterResponse() があるときだけ使い、無ければ従来どおり、その場で実行する。
     *
     * @param callable $task
     * @return void
     */
    private function runAfterResponse(callable $task)
    {
        if (class_exists('Acms\\Services\\Facades\\Application')) {
            try {
                $response = \Acms\Services\Facades\Application::make('response');
                if (is_object($response) && method_exists($response, 'afterResponse')) {
                    $response->afterResponse($task);
                    return;
                }
            } catch (\Throwable $e) {
                // 取得できないときは、その場で実行する
            }
        }
        $task();
    }
}
