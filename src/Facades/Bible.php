<?php

namespace Kudastech\Bible\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Kudastech\Bible\Bible setLang(string $lang)
 * @method static \Kudastech\Bible\Bible setVersion(string $version)
 * @method static \Kudastech\Bible\Bible book(string $bookName)
 * @method static \Kudastech\Bible\Bible chapter(int $chapterNumber)
 * @method static \Kudastech\Bible\Bible verse(int $verseNumber)
 * @method static array|null getBook()
 * @method static array|null getChapter()
 * @method static string|null getVerse()
 * @method static string|null get(string $reference)
 * @method static array getBooks()
 * @method static array getBibleIndex()
 * @method static array loadBibleIndex()
 *
 * @see \Kudastech\Bible\Bible
 */
class Bible extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bible';
    }
}