<?php

namespace JustCommunication\TelegramBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TelegramEvent extends Event
{
    protected string $location = '';
    protected string $eventName = '';
    protected string $mess = '';


    public function __construct(string $eventName, string $mess)
    {
        $this->eventName = $eventName;
        $this->mess = $mess;

        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];
        $this->location = $trace?('in '.$trace['file'].' on '.$trace['line']):('-not-defined- in '.__FILE__.' on '.__LINE__);
    }

    /**
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * @return string
     */
    public function getMess(): string
    {
        return $this->mess;
    }

    /**
     * @return String
     */
    public function getlocation(): String
    {
        return $this->location;
    }

}