[Home](../../README.md) / [Contributing](../contributing.md) / CSS or SCSS side

Previous section : [PHP side](php.md)

### CSS side or SCSS side

#### Coding style

- Do not use uppercase in classes, the css rules and properties do not do that => consistency.

- Use the BEM (Block Element Modifier) syntax. [Documentation](http://getbem.com/introduction/)

#### Best practices

- Use SCSS or SASS. SCSS is probably best because if one day, it becomes useless due to CSS enhancement ... SCSS has
  almost the same coding style so it will be simpler to adapt the code.
  
- Keep the lowest specificity as possible. Some people even say never use ids but classes instead for reusability !

- Use `border: 0` and not `border: none` (shorter).

Next section : [JavaScript side](js.md)