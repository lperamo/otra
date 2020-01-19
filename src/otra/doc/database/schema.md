[Home](../README.md) / [Database](../database.md) / Database schema

## Database schema

Your bundle database must be in the file `bundles/bundleName/config/data/yml/schema.yml`.

It must follow this kind of architecture :

```yaml
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
```

It remains SQL things that are not handled yet but feel free to create issues about those that are missing.

Next section : [Database fixtures](fixtures.md)
