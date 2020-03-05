<?php

function tag(...$v) {
    return new Tag(...$v);
}

function tags(...$v) {
    return Tags::from(...$v);
}