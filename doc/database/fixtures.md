[Home](../../README.md) / [Database](../database.md) / Database fixtures

Previous section : [Database schema](schema.md)

## Database fixtures

Your bundle database fixtures must be in the folder `bundles/bundleName/config/data/yml/fixtures`.

A fixture file must look like this :

```yaml
table_name: 
    fixture_name:
        my_id_column: '1'
        my_other_column: 'Main topic'
        a_timestamp_column: '2010-12-31 00:00:00'
        unsigned_column: '1'
    fixture_name_bis:
        my_id_column: '2'
        my_other_column: 'Second topic'
        a_timestamp_column: '2010-12-32 00:10:00'
        unsigned_column: '2'
    fixture_name_ter:
        my_id_column: '3'
        my_other_column: 'Third topic'
        a_timestamp_column: '2010-12-33 00:20:00'
        unsigned_column: '3'
    [...]
```

When you want to make reference to a fixture of another table, we must put the fixture name instead of the value.
