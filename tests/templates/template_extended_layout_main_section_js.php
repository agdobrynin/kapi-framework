<?php
/** @var \Kaspi\View $this */
$this->layout('layouts/main_default');
$this->sectionStart('js');
?><script>alert(1);</script><?php
$this->sectionEnd();
