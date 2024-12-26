<?php

namespace Gingdev\IAskAI\Listeners;

use Gingdev\IAskAI\Events\JoinEvent;
use Gingdev\IAskAI\Providers\IAskProvider;

class JoinListener
{
    public function onJoin(JoinEvent $event): void
    {
        IAskProvider::handle($event);
    }
}
