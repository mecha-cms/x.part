<?php namespace _\lot\x\tag;

function query() {
    $query = [];
    foreach ((array) $this['kind'] as $v) {
        if ($name = \To::tag($v)) {
            $query[] = \strtr($name, '-', ' ');
        }
    }
    \sort($query);
    return $query;
}

function tags() {
    $tags = [];
    foreach ($this->query() as $v) {
        $v = \strtr($v, ' ', '-');
        $tags[$v] = \LOT . \DS . 'tag' . \DS . $v . '.page';
    }
    return (new \Tags($tags))->sort([1, 'title']);
}

\Page::_('query', __NAMESPACE__ . "\\query");
\Page::_('tags', __NAMESPACE__ . "\\tags");