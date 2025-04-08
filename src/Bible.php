<?php

namespace Kudastech\Bible;

class Bible
{
    private string $lang;
    private string $version;
    private ?array $bibleIndex = null;
    private ?string $book = null;
    private ?int $chapter = null;
    private ?int $verse = null;
    private string $basePath;

    /**
     * Initialize the Bible class with language and version
     * @param string $lang The language code (default: 'en')
     * @param string $version The Bible version (default: 'kjv')
     * @throws \Exception If language or version doesn't exist
     */
    public function __construct($lang = null,  $version = null)
    {
        $this->basePath = __DIR__ . '/../bibles';
        $this->setLang($lang ?? config('bible.default_language', 'en'));
        $this->setVersion($version ?? config('bible.default_version', 'kjv'));
    }

    /**
     * Set the language and validate if exists
     * @param string $lang The language name for example "en"
     * @return $this
     * @throws \Exception If language doesn't exist
     */
    public function setLang(string $lang): self
    {
        $langPath = "{$this->basePath}/{$lang}";
        $booksIndexPath = "{$langPath}/Books.json";

        if (!file_exists($langPath)) {
            throw new \Exception("The language [{$lang}] doesn't exist");
        }

        if (!file_exists($booksIndexPath)) {
            throw new \Exception("Index of available books not found for [{$lang}]");
        }

        $this->lang = $lang;
        $this->loadBibleIndex();

        return $this;
    }

    /**
     * Set the Bible version and validate it exists
     * @param string $version The Bible version
     * @return $this
     * @throws \Exception If version doesn't exist for the current language
     */
    public function setVersion(string $version): self
    {
        $versionPath = "{$this->basePath}/{$this->lang}/{$version}";

        if (!file_exists($versionPath)) {
            throw new \Exception("The bible version [{$version}] for language [{$this->lang}] doesn't exist");
        }

        $this->version = $version;
        return $this;
    }

    /**
     * Load the Bible index file
     *
     * @return array The Bible index
     */
    public function loadBibleIndex(): array
    {
        $indexPath = "{$this->basePath}/{$this->lang}/Books.json";
        $this->bibleIndex = json_decode(file_get_contents($indexPath), true);
        return $this->bibleIndex;
    }

    /**
     * Get the Bible index
     *
     * @return array|null The Bible index
     */
    public function getBibleIndex(): ?array
    {
        if ($this->bibleIndex === null) {
            return $this->loadBibleIndex();
        }

        return $this->bibleIndex;
    }

    /**
     * Set the book to retrieve
     *
     * @param string $bookName The name of the book
     * @return $this
     */
    public function book(string $bookName): self
    {
        if ($this->bibleIndex === null) {
            $this->loadBibleIndex();
        }
        $this->book = ucfirst($bookName);
        return $this;
    }

    /**
     * Set the chapter to retrieve
     *
     * @param int $chapterNumber The chapter number (1-based)
     * @return $this
     */
    public function chapter(int $chapterNumber): self
    {
        $this->chapter = $chapterNumber - 1; 
        return $this;
    }

    /**
     * Set the verse to retrieve
     *
     * @param int $verseNumber The verse number (1-based)
     * @return $this
     */
    public function verse(int $verseNumber): self
    {
        $this->verse = $verseNumber - 1;
        return $this;
    }

    /**
     * Get the entire book content
     *
     * @return array|null The book content or null if not found
     */
    public function getBook(): ?array
    {
        if ($this->book === null) {
            return null;
        }

        $bookPath = "{$this->basePath}/{$this->lang}/{$this->version}/{$this->book}.json";

        if (file_exists($bookPath)) {
            return json_decode(file_get_contents($bookPath), true);
        }

        return null;
    }

    /**
     * Get a specific chapter from the book
     *
     * @return array|null The chapter content or null if not found
     */
    public function getChapter(): ?array
    {
        if ($this->chapter === null) {
            return null;
        }

        $book = $this->getBook();
        return $book['chapters'][$this->chapter] ?? null;
    }

    /**
     * Get a specific verse from the chapter
     *
     * @return string|null The verse text or null if not found
     */
    public function getVerse(): ?string
    {
        if ($this->verse === null) {
            return null;
        }

        $chapter = $this->getChapter();

        if ($chapter && isset($chapter['verses'][$this->verse])) {
            return $chapter['verses'][$this->verse]['text'] ?? null;
        }

        return null;
    }

    /**
     * Parse a reference string and get the verse
     * Format: "BookName Chapter:Verse"
     *
     * @param string $reference The reference string (e.g., "John 3:16")
     * @return string|null The verse text or null if not found
     */
    public function get(string $reference): ?string
    {
        $parts = explode(' ', $reference, 2);

        if (count($parts) !== 2) {
            return null;
        }

        $this->book($parts[0]);

        $chapterVerse = explode(':', $parts[1]);
        if (count($chapterVerse) !== 2) {
            return null;
        }

        $this->chapter((int)$chapterVerse[0]);
        $this->verse((int)$chapterVerse[1]);

        return $this->getVerse();
    }
    
    /**
     * Get all books available
     *
     * @return array The list of available books
     */
    public function getBooks(): array
    {
        if ($this->bibleIndex === null) {
            $this->loadBibleIndex();
        }
        
        return $this->bibleIndex;
    }
}