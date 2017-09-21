<?php

function fn_next($content, $lot = []) {
    if (strpos($content, '<!-- next -->') === false) {
        return $content;
    }
    global $language, $url;
    $step = explode('<!-- next -->', $content);
    $a = [];
    $index = Request::get($q = Plugin::state(__DIR__, 'q'), 1);
    for ($i = 0, $j = count($step) - 1; $i < $j; ++$i) {
        $ii = $i + 1;
        $a[] = $index === $ii ? HTML::span($ii, ['classes' => ['a']]) : HTML::a($ii, $url->current . HTTP::query([$q => $i > 0 ? $ii : false]), false, ['rel' => $ii > $index ? 'next' : 'prev']);
    }
    return $index > -1 && isset($step[$index]) ? trim($step[$index]) . '<nav class="next"><strong>' . $language->page . '</strong> ' . implode(' ', $a) . '</nav>' : To::sentence($language->_finded);
}

Hook::set('page.content', 'fn_next', 2.1);

function fn_next_meta($input) {
    if ($page = Lot::get('page')) {
        if (isset($page->path) && file_exists($page->path)) {
            $content = file_get_contents($page->path);
            if ($count = substr_count($content, '<!-- next -->')) {
                global $url;
                $q = Plugin::state(__DIR__, 'q');
                $index = Request::get($q, 1);
                // Add `<link>` tag(s) for SEO ;)
                $s = "";
                if ($index < $count) {
                    if ($index > 1) {
                        $s .= '<link href="' . $url->current . HTTP::query([$q => $index - 1]) . '" rel="prev">';
                    }
                    $s .= '<link href="' . $url->current . HTTP::query([$q => $index + 1]) . '" rel="next">';
                }
                return str_replace('</head>', $s . '</head>', $input);
            }
        }
    }
    return $input;
}

Hook::set('shield.input', 'fn_next_meta', 1.9);