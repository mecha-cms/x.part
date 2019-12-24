<?php

namespace _\lot\x\next {
    function meta($content) {
        extract($GLOBALS, \EXTR_SKIP);
        if (isset($page) && $page->exist) {
            if ($i = \substr_count($page->content, '<!-- next -->')) {
                $current = \Get::get($q = \State::get('x.next', true)['q']) ?? 1;
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

namespace _\lot\x {
    function next($content) {
        if (!$content || false === \strpos($content, '<!-- next -->')) {
            return $content;
        }
        global $url;
        $state = \State::get('x.next', true);
        $steps = \explode('<!-- next -->', $content);
        $current = \Get::get($q = $state['q']) ?? 1;
        \State::set([
            'has' => [
                'next' => isset($steps[$current + 1]),
                'prev' => $current > 1
            ]
        ]);
        return ($current > -1 && isset($steps[$current - 1]) ? \trim($steps[$current - 1]) : '<p>' . \i('Not found.') . '</p>') . '<nav class="next p">' . (function($current, $count, $chunk, $peek, $fn, $first, $prev, $next, $last) {
            $begin = 1;
            $end = (int) \ceil($count / $chunk);
            $out = "";
            if ($end <= 1) {
                return $out;
            }
            if ($current <= $peek + $peek) {
                $min = $begin;
                $max = \min($begin + $peek + $peek, $end);
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
                    $out .= '<a href="' . $fn($current - 1) . '" title="' . $prev . '" rel="prev">' . $prev . '</a>';
                }
                $out .= '</span> ';
            }
            if ($first && $last) {
                $out .= '<span>';
                if ($min > $begin) {
                    $out .= '<a href="' . $fn($begin) . '" title="' . $first . '" rel="prev">' . $begin . '</a>';
                    if ($min > $begin + 1) {
                        $out .= ' <span>&#x2026;</span>';
                    }
                }
                for ($i = $min; $i <= $max; ++$i) {
                    if ($current === $i) {
                        $out .= ' <b title="' . $i . '">' . $i . '</b>';
                    } else {
                        $out .= ' <a href="' . $fn($i) . '" title="' . $i . '" rel="' . ($current >= $i ? 'prev' : 'next') . '">' . $i . '</a>';
                    }
                }
                if ($max < $end) {
                    if ($max < $end - 1) {
                        $out .= ' <span>&#x2026;</span>';
                    }
                    $out .= ' <a href="' . $fn($end) . '" title="' . $last . '" rel="next">' . $end . '</a>';
                }
                $out .= '</span>';
            }
            if ($next) {
                $out .= ' <span>';
                if ($current === $end) {
                    $out .= '<b title="' . $next . '">' . $next . '</b>';
                } else {
                    $out .= '<a href="' . $fn($current + 1) . '" title="' . $next . '" rel="next">' . $next . '</a>';
                }
                $out .= '</span>';
            }
            return $out;
        })($current, \count($steps), 1, $state['peek'] ?? 2, function($i) use($q, $url) {
            return $url->clean . $url->query('&amp;', [$q => $i > 1 ? $i : false]);
        }, \i('First'), !empty($state['prev']) ? \i('Previous') : false, !empty($state['next']) ? \i('Next') : false, \i('Last')) . '</nav>';
    }
    \Hook::set('page.content', __NAMESPACE__ . "\\next", 2.1);
}
