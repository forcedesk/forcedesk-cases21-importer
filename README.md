
# SchoolDesk CASES21 Importer

Imports Staff from a CASES21 Export file and creates SchoolDesk accounts.

Existing accounts matching incoming records are updated. No accounts are deleted, these must be done manually.

## Installation

Install package via Composer in your SchoolDesk instance.

```
composer require schooldesk/cases21-importer
```

## Usage/Examples

Place a CASES21 Export file in your ```storage/app/importers``` directory.

**EG:** ```storage/app/importers/SF_XXXX.csv``` where `XXXX` is your School Code.

Then run the following command and follow the steps to process the file and create/update accounts.

```php artisan importers:cases21-importer```

Only records having a `STAFF_STATUS` of `ACTV` with ```PAYROLL_REC_NO``` and ```EMAIL``` fields defined will be imported, any other rows will be ignored.

## Support

Support is provided as per your SchoolDesk license. Contact your SchoolDesk Account Manager for details.
## Disclaimer

Use of the CASES21 Importer is at your own risk. No responsibility is taken by SchoolDesk or it's contributors.