<?php

namespace Safaricom\Daraja;

use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app->has('daraja')) {
            return;
        }
    }
}
