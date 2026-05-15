<?php

function component_card($title, $value, $icon = "", $subtext = "", $bg_color = "#e8f4f8", $text_color = "#212529", $border_color = "transparent", $link = null) {
    
    $title_color = ($text_color === '#ffffff') ? '#868e96' : '#6c757d';
    $subtext_color = ($text_color === '#ffffff') ? '#adb5bd' : '#868e96';
    $cursor = $link ? 'pointer' : 'default';

    $card_html = <<<CARD_GLANCE_EOT
    <div style="
        background-color: {$bg_color};
        border: 1px solid {$border_color};
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        height: 150px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: {$cursor};
        box-sizing: border-box;
    "
    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.1)';"
    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.05)';"
    >
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="color: {$title_color}; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">
                {$title}
            </span>
            <span style="font-size: 1.5rem;">
                {$icon}
            </span>
        </div>
        <div>
            <div style="color: {$text_color}; font-size: 2rem; font-weight: 800; line-height: 1.2; margin-bottom: 0.25rem;">
                {$value}
            </div>
            <div style="color: {$subtext_color}; font-size: 0.85rem;">
                {$subtext}
            </div>
        </div>
    </div>
CARD_GLANCE_EOT;
    if ($link) {
        return <<<CARD_LINK_EOT
        <a href="{$link}" style="text-decoration: none; color: inherit; display: block;">
            {$card_html}
        </a>
CARD_LINK_EOT;
    }

    return $card_html;
}
?>