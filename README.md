# Laravel Filament Bible Package

A Laravel package for fetching Bible verses with Filament integration. 

![Bible Verse Selector](https://github.com/kudastech/filament-bible/raw/main/images/bible-selector.png)

## Features

- 📖 Access Bible verses programmatically
- 🔍 Search and select verses with an intuitive UI
- 🧩 Seamless integration with Filament forms
- 🌐 Support for multiple languages and Bible versions (currently KJV in English)
- 📋 Easy to use API with fluent interface

## Installation

You can install the package via composer:

```bash
composer require kudastech/filament-bible
```

After installation, publish the configuration file:

```bash
php artisan vendor:publish --tag=bible-config
```

To publish the Bible files to your application's storage directory:

```bash
php artisan vendor:publish --tag=bible-files
```

## Configuration

After publishing the config file, you can modify it at `config/bible.php`:

```php
return [
    // Default Bible language
    'default_language' => 'en',
    
    // Default Bible version
    'default_version' => 'kjv',
    
    // Path to Bible data files
    'bibles_path' => storage_path('app/bibles'),
];
```

## Usage

### Basic Usage

The package provides a convenient facade for quick access to Bible functionality:

```php
use Kudastech\Bible\Facades\Bible;

// Get a specific verse
$verse = Bible::get('Isaiah 60:1');

$verse = Bible::book('Isaiah')->chapter(60)->verse(1)->getVerse();

// Get list of available books
$books = Bible::getBooks();
```

### Filament Integration

This package provides a convenient integration with Filament forms through the `BibleAction` class:

```php
use Kudastech\Bible\Actions\BibleAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

Forms\Components\Section::make()
    ->schema([
        Forms\Components\TextInput::make('verse_reference')
            ->label('Bible Reference')
            ->helperText('Reference will be automatically filled when you fetch a verse')
            ->columnSpan('full'),
        
        Forms\Components\Textarea::make('verse_text')
            ->label('Bible Verse')
            ->rows(3)
            ->columnSpan('md'),
        
        // This is how you can use the BibleAction
        Forms\Components\Actions::make([
            BibleAction::make('fetchBibleVerse')
                ->targetField('verse_text')
                ->referenceField('verse_reference')
        ])->columnSpan('md'),
    ])
    ->columns(1),
```

When the user clicks the Bible action button, they'll see a modal dialog where they can select a Bible book, chapter, and verses. The selected verses will be inserted into the target field, and the reference will be inserted into the reference field (if specified).

## Currently Supported Languages and Versions

| Language | Code | Versions |
|----------|------|----------|
| English  | en   | kjv      |

## Adding New Languages and Bible Versions

To add a new language or Bible version:

1. Create a folder structure similar to `bibles/en`
2. Add a `Books.json` file with the list of books
3. Add JSON files for each book with chapters and verses

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Acknowledgements

The KJV English Bible JSON files were sourced from [here](https://github.com/aruljohn/Bible-kjv).

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.