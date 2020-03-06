<?php

namespace _\lot\x\next {
    function route($path) {
        $n = \State::get('x.next.path') ?? '/page';
        if (\File::exist([
            \LOT . \DS . 'page' . \DS . $path . $n . '.archive',
            \LOT . \DS . 'page' . \DS . $path . $n . '.page'
        ])) {
            \Route::fire('*', [$path . $n]);
        }
        \Route::fire('*', [$path]);
    }
    \Route::set('*' . (\State::get('x.next.path') ?? '/page'), __NAMESPACE__ . "\\route", 10);
}

namespace _\lot\x {
    function next($content) {
        $state = \State::get('x.next', true);
        if (!$content || isset($state['cut']) && false === \strpos($content, $state['cut'])) {
            return $content;
        }
        global $url;
        $that = $this;
        $steps = \explode($state['cut'], $content);
        $p = $state['path'] ?? '/page';
        $i = $url['i'] ?? 1;
        if ($i > 1) {
            $current = $p === \substr($url['path'], -\strlen($p)) ? $i : \count($steps) + 1;
        } else {
            $current = $i;
        }
        $exist = $current > -1 && isset($steps[$current - 1]);
        \State::set([
            'has' => [
                'next' => isset($steps[$current + 1]),
                'prev' => $current > 1
            ],
            'is' => [
                'error' => !$exist
            ]
        ]);
        return $exist ? \trim($steps[$current - 1]) . '<nav class="next p">' . (function($current, $count, $chunk, $peek, $fn, $first, $prev, $next, $last) {
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
        })($current, \count($steps), 1, $state['pager']['peek'] ?? 2, function($i) use($p, $that, $url) {
            return $that->url . ($i > 1 ? $p . '/' . $i : "") . $url->query . $url->hash;
        }, \i('First'), !empty($state['pager']['prev']) ? \i('Previous') : false, !empty($state['pager']['next']) ? \i('Next') : false, \i('Last')) . '</nav>' : '<p>' . \i('Not found.') . '</p>';
    }
    \Hook::set('page.content', __NAMESPACE__ . "\\next", 2.1);
}
