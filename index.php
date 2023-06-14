<?php

namespace x\part {
    function page__content($content) {
        if (!$content || false === \strpos($content, "\f")) {
            return $content;
        }
        \extract($GLOBALS, \EXTR_SKIP);
        // There must be at least 2 form feed character(s) in content or it will be considered as a page excerpt marker
        if (\substr_count($content, "\f") < ($state->x->part->min ?? 2)) {
            return $content;
        }
        $path = \trim($url->path ?? "", '/');
        $route = \trim($state->x->part->route ?? 'page', '/');
        if (\preg_match('/^(.*?)\/([1-9]\d*)$/', $path, $m)) {
            [$any, $path, $part] = $m;
        }
        $part = ((int) ($part ?? 1)) - 1;
        $parts = \explode("\f", $content);
        $content = $parts[$part] ?? false;
        // Make sure that route ends with `/page`
        if ('/' . $route === \substr($path, $end = -(\strlen($route) + 1))) {
            // Normalize the route to a value without the `/page` ending
            $path = \substr($path, 0, $end);
            // Route still ends with `/page`, but does not point to a valid file
            $folder = \LOT . \D . 'page' . \D . \strtr($path, ['/' => \D]);
            if (\exist([
                $folder . '.archive',
                $folder . '.page'
            ], 1) !== $this->path) {
                // Always show the first part
                $content = $parts[$part = 0];
            }
        } else {
            // Always show the first part
            $content = $parts[$part = 0];
        }
        if (false === $content) {
            return '<p role="status">' . \i('No more %s to show.', 'pages') . '</p>';
        }
        // Need to fill the `$pager` data with a list of dummy (but valid) file path(s), otherwise the `$pager->count`
        // value will be invalid because normally any invalid file path data will be ignored by the `Pager` class.
        $pager = new \Pager(\array_fill(0, \count($parts), $this->path));
        $pager = $pager->chunk(1, $part);
        $pager->hash = $url->hash;
        $pager->path = '/' . $path . '/' . $route;
        $pager->query = $url->query;
        $content = '<div aria-posinset="' . ($part + 1) . '" aria-setsize="' . \count($parts) . '" role="doc-part">' . $content . '</div>';
        if (isset($state->x->pager) && \class_exists("\\Layout")) {
            $end = \Layout::pager($state->x->part->pager ?? 'peek', [
                '2' => ['role' => 'doc-pagelist'],
                'pager' => $pager
            ]);
        } else {
            $next = $pager->next;
            $prev = $pager->prev;
            $end = new \HTML([
                0 => 'p',
                1 => [
                    'prev' => [
                        0 => 'a',
                        1 => \i('Previous'),
                        2 => [
                            'aria-disabled' => $prev ? null : 'true',
                            'href' => $prev ? $prev->link : null,
                            'rel' => $prev ? 'prev' : null,
                            'title' => \i('Go to the %s page.', 'previous')
                        ]
                    ],
                    ' ' => '&#xa0;',
                    'next' => [
                        0 => 'a',
                        1 => \i('Next'),
                        2 => [
                            'aria-disabled' => $next ? null : 'true',
                            'href' => $next ? $next->link : null,
                            'rel' => $next ? 'next' : null,
                            'title' => \i('Go to the %s page.', 'next')
                        ]
                    ]
                ],
                2 => [
                    'aria-label' => \i('Page Navigation'),
                    'role' => 'doc-pagelist'
                ]
            ], true);
        }
        return $content . $end;
    }
    function route__page($content, $path, $query, $hash) {
        \extract($GLOBALS, \EXTR_SKIP);
        $path = \trim($path ?? "", '/');
        $route = \trim($state->x->part->route ?? 'page', '/');
        if (\preg_match('/^(.*?)\/([1-9]\d*)$/', $path, $m)) {
            [$any, $path, $part] = $m;
        }
        $part = ((int) ($part ?? 1)) - 1;
        // Test if current route ends with `/page`
        $folder = \LOT . \D . 'page' . \D . $path;
        if ('/' . $route === \substr($path, $end = -(\strlen($route) + 1))) {
            // A page route with `/page` ending is present
            if (\exist([
                $folder . '.archive',
                $folder . '.page'
            ], 1)) {
                return $content;
            }
            $folder = \LOT . \D . 'page' . \D . ($path = \substr($path, 0, $end));
            $file = \exist([
                $folder . '.archive',
                $folder . '.page'
            ], 1);
            $test = $file ? (\From::page(\file_get_contents($file))['content'] ?? "") : "";
            $test = \fire(__NAMESPACE__ . "\\page__content\\n", [$test], new \Page);
            $parts = \explode("\f", $test);
            \State::set([
                'has' => [
                    'next' => isset($parts[$part + 1]),
                    'page' => !!$file,
                    'pages' => false,
                    'prev' => isset($parts[$part - 1])
                ],
                'is' => [
                    'error' => isset($parts[$part]) ? false : 404,
                    'page' => !!$file,
                    'pages' => false
                ],
                'part' => $part + 1
            ]);
            // Resolve to the native page route
            return \Hook::fire('route.page', [$content, '/' . $path, $query, $hash]);
        }
        return $content;
    }
    \Hook::set('page.content', __NAMESPACE__ . "\\page__content", 2.2);
    \Hook::set('route.page', __NAMESPACE__ . "\\route__page", 99.99); // Execute before the default page route priority!
}

namespace x\part\page__content {
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
    \Hook::set('page.content', __NAMESPACE__ . "\\n", 2.1);
}

namespace x\part\route__page {
    function status($content) {
        \extract($GLOBALS, \EXTR_SKIP);
        $error = $state->is('error');
        if ($error && \is_int($error)) {
            if ($content && \is_array($content) && isset($content[2])) {
                $content[2] = $error;
            }
        }
        return $content;
    }
    // Late error check…
    \Hook::set('route.page', __NAMESPACE__ . "\\status", 100.01); // Execute after the default page route priority!
}