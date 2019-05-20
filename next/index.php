<?php

namespace _\next {
    function meta($content) {
        extract($GLOBALS, \EXTR_SKIP);
        if (isset($page) && $page->exist) {
            if ($i = \substr_count(\file_get_contents($page->path), '<!-- next -->')) {
                global $url;
                $q = \plugin('next')['q'];
                $current = \HTTP::get($q) ?? 1;
                // Add `<link>` tag(s) for SEO ;)
                $out = "";
                if ($current < $i) {
                    if ($current > 1) {
                        $out .= '<link href="' . $url->clean . $url->query('&amp;', [$q => $current - 1]) . '" rel="prev">';
                    }
                    $out .= '<link href="' . $url->clean . $url->query('&amp;', [$q => $current + 1]) . '" rel="next">';
                }
                return \str_replace('</head>', $out . '</head>', $content);
            }
        }
        return $content;
    }
    \Hook::set('content', __NAMESPACE__ . "\\meta", 1.9);
}

namespace _ {
    function next($content) {
        if (!$content || \strpos($content, '<!-- next -->') === false) {
            return $content;
        }
        global $language, $url;
        $state = \plugin('next');
        $steps = \explode('<!-- next -->', $content);
        $current = \HTTP::get($q = $state['q']) ?? 1;
        $a = [];
        return ($current > -1 && isset($steps[$current - 1]) ? \trim($steps[$current - 1]) : '<p>' . $language->pageErrorDescription . '</p>') . '<nav class="next p">' . call_user_func(function($current, $count, $chunk, $peek, $fn, $first, $prev, $next, $last) {
            $begin = 1;
            $end = (int) ceil($count / $chunk);
            $out = "";
            if ($end <= 1) {
                return $out;
            }
            if ($current <= $peek + $peek) {
                $min = $begin;
                $max = min($begin + $peek + $peek, $end);
            } else if ($current > $end - $peek - $peek) {
                $min = $end - $peek - $peek;
                $max = $end;
            } else {
                $min = $current - $peek;
                $max = $current + $peek;
            }
            if ($prev) {
                $out = '<span>';
                if ($current === $begin) {
                    $out .= '<b title="' . $prev . '">' . $prev . '</b>';
                } else {
                    $out .= '<a href="' . call_user_func($fn, $current - 1) . '" title="' . $prev . '" rel="prev">' . $prev . '</a>';
                }
                $out .= '</span> ';
            }
            if ($first && $last) {
                $out .= '<span>';
                if ($min > $begin) {
                    $out .= '<a href="' . call_user_func($fn, $begin) . '" title="' . $first . '" rel="prev">' . $begin . '</a>';
                    if ($min > $begin + 1) {
                        $out .= ' <span>&#x2026;</span>';
                    }
                }
                for ($i = $min; $i <= $max; ++$i) {
                    if ($current === $i) {
                        $out .= ' <b title="' . $i . '">' . $i . '</b>';
                    } else {
                        $out .= ' <a href="' . call_user_func($fn, $i) . '" title="' . $i . '" rel="' . ($current >= $i ? 'prev' : 'next') . '">' . $i . '</a>';
                    }
                }
                if ($max < $end) {
                    if ($max < $end - 1) {
                        $out .= ' <span>&#x2026;</span>';
                    }
                    $out .= ' <a href="' . call_user_func($fn, $end) . '" title="' . $last . '" rel="next">' . $end . '</a>';
                }
                $out .= '</span>';
            }
            if ($next) {
                $out .= ' <span>';
                if ($current === $end) {
                    $out .= '<b title="' . $next . '">' . $next . '</b>';
                } else {
                    $out .= '<a href="' . call_user_func($fn, $current + 1) . '" title="' . $next . '" rel="next">' . $next . '</a>';
                }
                $out .= '</span>';
            }
            return $out;
        }, $current, count($steps), 1, $state['peek'] ?? 2, function($i) use($q, $url) {
            return $url->clean . $url->query('&amp;', [$q => $i > 1 ? $i : false]);
        }, $language->first, !empty($state['prev']) ? $language->prev : false, !empty($state['next']) ? $language->next : false, $language->last) . '</nav>';
    }
    \Hook::set('page.content', __NAMESPACE__ . "\\next", 2.1);
}