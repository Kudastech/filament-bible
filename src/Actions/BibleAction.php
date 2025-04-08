<?php

namespace Kudastech\Bible\Actions;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Kudastech\Bible\Bible;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Component;
use Filament\Notifications\Notification;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Actions\Action;

class BibleAction extends Action
{
    protected ?string $targetField = null;
    protected ?string $referenceField = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Fetch Bible Verse')
            ->icon('heroicon-o-book-open')
            ->modalHeading('Select Bible Verse')
            ->modalWidth('lg')
            ->modalIcon('heroicon-o-book-open')
            ->form($this->getFormSchema())
            ->action(function (array $data, Component $component) {
                $this->handleBibleVerseSelection($data, $component);
            });
    }

    /**
     * Define the form schema for Bible verse selection
     * 
     * @return array
     */
    protected function getFormSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    $this->getBookSelector(),
                    $this->getChapterSelector(),
                ]),

            $this->getHiddenVerseCounter(),
            $this->getVerseSelector(),
        ];
    }

    /**
     * Get book selector component
     * 
     * @return Select
     */
    protected function getBookSelector(): Select
    {
        return Select::make('book')
            ->label('Book')
            ->options(function () {
                $bible = app(Bible::class);
                $books = $bible->getBooks();

                if (is_array($books)) {
                    return array_combine($books, $books);
                }

                return [];
            })
            ->required()
            ->reactive()
            ->searchable()
            ->afterStateUpdated(function (Set $set) {
                $set('chapter', null);
                $set('selected_verses', []);
            });
    }

    /**
     * Get chapter selector component
     * 
     * @return Select
     */
    protected function getChapterSelector(): Select
    {
        return Select::make('chapter')
            ->label('Chapter')
            ->options(function (Get $get) {
                $bookName = $get('book');
                if (!$bookName) {
                    return [];
                }

                $bible = app(Bible::class);
                $book = $bible->book($bookName)->getBook();
                $chapterCount = count($book['chapters']);
                $options = [];

                for ($i = 1; $i <= $chapterCount; $i++) {
                    $options[(string)$i] = (string)$i;
                }

                return $options;
            })
            ->required()
            ->reactive()
            ->searchable()
            ->afterStateUpdated(fn(Set $set) => $set('selected_verses', []));
    }

    /**
     * Get hidden verse counter component
     * 
     * @return Hidden
     */
    protected function getHiddenVerseCounter(): Hidden
    {
        return Hidden::make('verse_count')
            ->reactive()
            ->afterStateHydrated(function (Hidden $component, Get $get, Set $set) {
                $bookName = $get('book');
                $chapterNum = $get('chapter');

                if (!$bookName || !$chapterNum) {
                    $set('verse_count', 0);
                    return;
                }

                $bible = app(Bible::class);
                $chapter = $bible->book($bookName)->chapter($chapterNum)->getChapter();

                $set('verse_count', count($chapter['verses']));
            })
            ->visible(fn(Get $get): bool => $get('book') && $get('chapter'));
    }

    /**
     * Get verse selector component
     * 
     * @return CheckboxList
     */
    protected function getVerseSelector(): CheckboxList
    {
        return CheckboxList::make('selected_verses')
            ->label('Select Verses')
            ->options(function (Get $get) {
                $bookName = $get('book');
                $chapterNum = $get('chapter');

                if (!$bookName || !$chapterNum) {
                    return [];
                }

                $bible = app(Bible::class);
                $chapter = $bible->book($bookName)->chapter($chapterNum)->getChapter();
                $verseCount = count($chapter['verses']);
                $options = [];

                for ($i = 1; $i <= $verseCount; $i++) {
                    $options[(string)$i] = (string)$i;
                }

                return $options;
            })
            ->columns(6)
            ->gridDirection('row')
            ->required()
            ->visible(fn(Get $get): bool => $get('book') && $get('chapter'))
            ->helperText('Select one or more verses');
    }

    /**
     * Handle the Bible verse selection and update form fields
     * 
     * @param array $data
     * @param Component $component
     * @return void
     */
    protected function handleBibleVerseSelection(array $data, Component $component): void
    {
        $bible = app(Bible::class);
        $bookName = $data['book'];
        $chapterNum = $data['chapter'];
        $selectedVerses = $data['selected_verses'];

        // Sort verse numbers for sequential processing
        sort($selectedVerses, SORT_NUMERIC);

        $versesText = [];
        foreach ($selectedVerses as $verseNum) {
            $singleRef = "{$bookName} {$chapterNum}:{$verseNum}";
            $verseText = $bible->get($singleRef);

            if ($verseText) {
                $versesText[] = "{$verseNum}. {$verseText}";
            }
        }

        if (empty($versesText)) {
            Notification::make()->title('Verses not found')->warning()->send();
            return;
        }

        $combinedText = implode("\n\n", $versesText);

        $bibleVersion = strtoupper(config('bible.default_version'));
        $formattedReference = "{$bookName} {$chapterNum}:{$this->formatVerseRange($selectedVerses)} ({$bibleVersion})";

        $this->updateFormFields($component, $combinedText, $formattedReference);

        Notification::make()->title('Bible verses fetched')->success()->send();
    }

    /**
     * Update form fields with Bible verse data
     * 
     * @param Component $component
     * @param string $verseText
     * @param string $reference
     * @return void
     */
    protected function updateFormFields(Component $component, string $verseText, string $reference): void
    {
        if ($this->getTargetField()) {
            $livewire = $component->getContainer()->getLivewire();
            $livewire->data[$this->getTargetField()] = $verseText;
            if ($this->getReferenceField()) {
                $livewire->data[$this->getReferenceField()] = $reference;
            }
        }
    }

    /**
     * Check if the array of verse numbers is a consecutive range
     * 
     * @param array $verses
     * @return bool
     */
    protected function isConsecutiveRange(array $verses): bool
    {
        if (count($verses) <= 1) {
            return true;
        }
        for ($i = 1; $i < count($verses); $i++) {
            if ($verses[$i] != $verses[$i - 1] + 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Format a list of verses as a comma-separated string
     * 
     * @param array $verses
     * @return string
     */
    protected function formatVerseList(array $verses): string
    {
        return implode(', ', $verses);
    }

    /**
     * Format verse numbers as a range or list
     * 
     * @param array $verses
     * @return string
     */
    protected function formatVerseRange(array $verses): string
    {
        if (empty($verses)) {
            return '';
        }

        if (count($verses) === 1) {
            return $verses[0];
        }

        if ($this->isConsecutiveRange($verses)) {
            return $verses[0] . '-' . $verses[count($verses) - 1];
        }

        return $this->formatVerseList($verses);
    }

    /**
     * Set the target field for verse text
     * 
     * @param string $field
     * @return static
     */
    public function targetField(string $field): static
    {
        $this->targetField = $field;
        return $this;
    }

    /**
     * Get the target field
     * 
     * @return string|null
     */
    public function getTargetField(): ?string
    {
        return $this->targetField;
    }

    /**
     * Set the reference field
     * 
     * @param string $field
     * @return static
     */
    public function referenceField(string $field): static
    {
        $this->referenceField = $field;
        return $this;
    }

    /**
     * Get the reference field
     * 
     * @return string|null
     */
    public function getReferenceField(): ?string
    {
        return $this->referenceField;
    }
}
