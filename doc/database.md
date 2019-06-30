[Home](../README.md) /

### Database

## Database schema

Your bundle database must be in the file `bundles/bundleName/config/data/yml/schema.yml`.

It must follow this kind of architecture :

    table_name: 
      columns:
        my_id_column:
          type: int(11)
          notnull: true
          auto_increment: true
          primary: true
        my_other_column:
          type: varchar(255)
          notnull: true
        a_timestamp_column:
          type: timestamp
          notnull: true
        unsigned_column:
          type: tinyint(1) unsigned
        [...]
    
    another_table_name: 
      columns:
        my_foreign_id_column:
          type: int(11)
          notnull: true
          primary: true
        my_foreign_id_column_two:
          type: int(11)
          notnull: true
          primary: true
        a_column:
          type: int(11)
          notnull: true
      relations:
        other_table:
          local: my_foreign_id_column
          foreign: id
          constraint_name: relation_name
        other_table_bis:
          local: my_foreign_id_column_two
          foreign: my_id_column
          constraint_name: another_relation_name
    
    other_table: 
      columns:
        [...]
    
    other_table_bis: 
      columns:
        [...]

It remains SQL things that are not handled yet but feel free to create issues about those that are missing.

## Database fixtures

Your bundle database fixtures must be in the folder `bundles/bundleName/config/data/yml/fixtures`.

A fixture file must look like this :

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

When you want to make reference to a fixture of another table, we must put the fixture name instead of the value.