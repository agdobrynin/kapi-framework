<?php

if (isset($title)) {
    echo $title.PHP_EOL;
}
/* @var $this \Kaspi\View */
// default section put here.
$this->section();
$this->section('js');
