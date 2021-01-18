[Home](../../README.md) / [Contributing](../../CONTRIBUTING.md) / Generic rules

### Generic rules

#### Coding style

- Tabulation :
    - Spaces, not tabs
    - 2 spaces => to avoid scrolling horizontally when they are much nesting

- Curly brackets on the same column
    - more aeration
    - easy to see if there are some missing curly brackets, counting curly brackets, above all when there are far away 

- Put a blank line before and after block statements (if, for, while etc.)

- 80 characters per line maximum in order to be able to have 2 files opened side by side on a laptop screen.

- Use PascalCase for classesName, SCREAMING_SNAKE_CASE for constants, camelCase for the rest.

- If the line of code of a function signature is too long, we have to put the parameters under, one parameter by line.

    Example : 
    
    ```php
    public final function renderView(
      string $file,
      array $variables = [],
      bool $ajax = false,
      bool $viewPath = true
    ) : string
    ```

Next section : [PHP side](php.md)
