<?php

namespace App\View\Components\mail;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Mail\Message;
use Illuminate\View\Component;

class header_component extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(protected Message $message, protected String $url)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.mail.header_component')
            ->with('message', $this->message)
            ->with('url', $this->url);
    }
}
