<?php
class InfoElement extends WidgetElement
{
    public function __construct($text, $icon)
    {
        $this->icon = $icon;
        $this->text = $text;
    }

    public function render()
    {
        return htmlReady($this->text);
    }
}
