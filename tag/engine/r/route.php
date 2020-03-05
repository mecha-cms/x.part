<?php namespace _\lot\x\tag;

$GLOBALS['tag'] = new \Tag;

function route($any, $name) {
    extract($GLOBALS, \EXTR_SKIP);
    $i = ($url['i'] ?? 1) - 1;
    $path = \State::get('x.tag.path') ?? '/tag';
    if (null !== ($id = \From::tag($name))) {
        $r = \LOT . \DS . 'page' . \DS . $any;
        if ($file = \File::exist([
            $r . '.page',
            $r . '.archive'
        ])) {
            $page = new \Page($file);
        }
        if ($file = \File::exist([
            \LOT . \DS . 'tag' . \DS . $name . '.page',
            \LOT . \DS . 'tag' . \DS . $name . '.archive'
        ])) {
            $tag = new \Tag($file);
        }
        \State::set([
            'chunk' => $chunk = $tag['chunk'] ?? $page['chunk'] ?? 5,
            'deep' => $deep = $tag['deep'] ?? $page['deep'] ?? 0,
            'sort' => $sort = $tag['sort'] ?? $page['sort'] ?? [1, 'path']
        ]);
        $pages = \Pages::from($r, 'page', $deep)->sort($sort);
        if ($pages->count() > 0) {
            $pages->lot($pages->is(function($v) use($id) {
                if (\is_file($k = \Path::F($v) . \DS . 'kind.data')) {
                    $k = \e(\file_get_contents($k));
                } else if (!$k = (\From::page(\file_get_contents($v))['kind'] ?? null)) {
                    return false;
                }
                $k = ',' . \implode(',', (array) $k) . ',';
                return false !== \strpos($k, ',' . $id . ',');
            })->get());
        }
        \State::set([
            'is' => [
                'error' => false,
                'page' => false,
                'pages' => true,
                'tag' => false, // Never be `true`
                'tags' => true
            ],
            'has' => [
                'page' => true,
                'pages' => $pages->count() > 0,
                'parent' => true
            ]
        ]);
        $GLOBALS['t'][] = \i('Tag');
        $GLOBALS['t'][] = $tag->title;
        $pager = new \Pager\Pages($pages->get(), [$chunk, $i], $url . '/' . $any . $path . '/' . $name);
        $pages = $pages->chunk($chunk, $i);
        $GLOBALS['page'] = $page;
        $GLOBALS['pager'] = $pager;
        $GLOBALS['pages'] = $pages;
        $GLOBALS['parent'] = $page;
        $GLOBALS['tag'] = $tag;
        if (0 === $pages->count()) {
            // Greater than the maximum step or less than `1`, abort!
            \State::set([
                'has' => [
                    'next' => false,
                    'parent' => false,
                    'prev' => false
                ],
                'is' => ['error' => 404]
            ]);
            $GLOBALS['t'][] = \i('Error');
            $this->status(404);
            $this->view('404/' . $any . $path . '/' . $name . '/' . ($i + 1));
        }
        \State::set('has', [
            'next' => !!$pager->next,
            'parent' => !!$pager->parent,
            'prev' => !!$pager->prev
        ]);
        $this->view('pages/' . $any . $path . '/' . $name . '/' . ($i + 1));
    }
    \State::set([
        'has' => [
            'next' => false,
            'parent' => false,
            'prev' => false
        ],
        'is' => ['error' => 404]
    ]);
    $GLOBALS['t'][] = \i('Error');
    $this->status(404);
    $this->view('404/' . $any . $path . '/' . $name . '/' . ($i + 1));
}

\Route::set('*' . (\State::get('x.tag.path') ?? '/tag') . '/:name', 200, __NAMESPACE__ . "\\route", 10);
