<?php

declare(strict_types=1);

namespace Fomvasss\MediaLibraryExtension\Tests;

/**
 * Інший тип моделі (інший morph class) на тій самій таблиці
 */
class Page extends Article
{
    protected $table = 'articles';
}
