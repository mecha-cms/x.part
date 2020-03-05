<?php

class tags extends Pages {

    public function page(string $path) {
        return new Tag($path);
    }

    public static function from(...$lot) {
        $lot[0] = $lot[0] ?? LOT . DS . 'tag';
        return parent::from(...$lot);
    }

}