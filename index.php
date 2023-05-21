<?php

namespace x\part {
    function content($content) {
        if (!$content || false === \strpos($content, "\f")) {
            return $content;
        }
        \extract($GLOBALS, \EXTR_SKIP);
        $path = \trim($url->path ?? $state->route ?? 'index', '/');
        if ($path && \preg_match('/^(.*?)\/([1-9]\d*)$/', $path, $m)) {
            [$any, $path, $part] = $m;
        }
        $part = ((int) ($part ?? 1)) - 1;
        $parts = \explode("\f", $content);
        $route = \trim($state->x->part->route ?? 'page', '/');
        $content = $parts[$part] ?? "";
        if ('/' . $route !== \substr($path, -(\strlen($route) + 1))) {
            $path .= '/' . $route;
            $content = \reset($parts); // Invalid route, return the first part!
        }
        if ("" === $content) {
            return '<p role="status">' . \i('No more %s to show.', 'pages') . '</p>';
        }
        $end = "";
        $pager = new \Pager(\array_fill(0, \count($parts), $this->path));
        $pager = $pager->chunk(1, $part);
        $pager->hash = $url->hash;
        $pager->path = '/' . $path;
        $pager->query = $url->query;
        if (isset($state->x->pager) && \class_exists("\\Layout")) {
            $end = \Layout::pager('peek', [
                '2' => ['role' => 'doc-pagelist'],
                'pager' => $pager
            ]);
        } else {
            $end .= '<nav aria-label="' . \eat(\i('Page Navigation')) . '" role="doc-pagelist">';
            if ($prev = $pager->prev) {
                $end .= '<a href="' . \eat($prev->link) . '" rel="prev" title="' . \eat(\i('Go to the %s page', 'previous')) . '">' . \i('Previous') . '</a>';
            }
            if ($next = $pager->next) {
                $end .= '<a href="' . \eat($next->link) . '" rel="next" title="' . \eat(\i('Go to the %s page', 'next')) . '">' . \i('Next') . '</a>';
            }
            $end .= '</nav>';
        }
        $content = '<div aria-posinset="' . ($part + 1) . '" aria-setsize="' . \count($parts) . '" role="doc-part">' . $content . '</div>';
        return $content . $end;
    }
    function n($content) {
        if (!$content) {
            return $content;
        }
        $exist = \strpos($content, "\f") ?: \strpos($content, '&#12;') ?: \stripos($content, '&#xc;');
        if (!$exist) {
            return $content;
        }
        // Normalize `&#12;` and `&#xc;` to a literal `\f`, also, remove the surrounding HTML element if any (usually a paragraph element)
        return \preg_replace('/\s*<([\w:-]+)(?:\s[^>]*)?>\s*(?:[\f]|&#(?:12|x[cC]);)\s*<\/\1>\s*|\s*(?:[\f]|&#(?:12|x[cC]);)\s*/', "\f", $content);
    }
    function route($content, $path, $query, $hash) {
        \extract($GLOBALS, \EXTR_SKIP);
        $path = \trim($path ?? $state->route ?? 'index', '/');
        if ($path && \preg_match('/^(.*?)\/([1-9]\d*)$/', $path, $m)) {
            [$any, $path, $part] = $m;
        }
        $part = ((int) ($part ?? 1)) - 1;
        $route = \trim($state->x->part->route ?? 'page', '/');
        // Test if current route ends with `/page` and then resolve it to the native page route
        if ('/' . $route === \substr($path, $end = -(\strlen($route) + 1)) && !\exist([
            \LOT . \D . 'page' . \D . $path . '.archive',
            \LOT . \D . 'page' . \D . $path . '.page'
        ], 1)) {
            $path = \substr($path, 0, $end);
            $exist = $path && \exist([
                \LOT . \D . 'page' . \D . $path . '.archive',
                \LOT . \D . 'page' . \D . $path . '.page'
            ], 1);
            \State::set([
                'has' => [
                    'page' => !!$exist,
                    'pages' => false
                ],
                'is' => [
                    'error' => false,
                    'page' => !!$exist,
                    'pages' => false
                ],
                'part' => $part + 1
            ]);
            return \Hook::fire('route.page', [$content, '/' . $path, $query, $hash]);
        }
        return $content;
    }
    \Hook::set('page.content', __NAMESPACE__ . "\\content", 2.2);
    \Hook::set('page.content', __NAMESPACE__ . "\\n", 2.1);
    \Hook::set('route.page', __NAMESPACE__ . "\\route", 99.99);
}