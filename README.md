
# SchoolDesk CASES21 Importer

Imports Staff from a CASES21 Delta Export file and creates SchoolDesk accounts.

Existing accounts matching incoming records are updated. No accounts are deleted, these must be done manually.

## Installation

Install package via Composer in your SchoolDesk instance.

```
composer require schooldesk/cases21-importer
```

## Usage/Examples

Place a CASES21 Delta Export file in your ```storage/app/importers``` directory.

Run the following command and follow the steps to process the Delta file and create/update accounts.

```php artisan importers:cases21-importer```

## Support

Support is provided as per your SchoolDesk license. Contact your SchoolDesk Account Manager for details.
## Disclaimer

Use of the CASES21 Importer is at your own risk. No responsibility is taken by SchoolDesk or it's contributors.