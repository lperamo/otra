[Home](../../README.md) / [Contributing](../contributing.md) / PHP side

Previous section : [Generic rules](generic.md)

### PHP side

#### Coding style

- Organize use statements particularly when they share path e.g. :

    ```php
    use config\{All_Config, Routes};
    ```
    
#### Best practices

- Use `const` instead of `define` when possible.

  https://stackoverflow.com/a/3193704/1818095

- Use a class only when you cannot do it procedurally or when you need a scope.

- Always put a PHP documentation block unless the meaning is obvious. Ex :
  
    ```php
    public function getFirstName() : string
    {
      return $this->firstName;
    }
    ```
  
  Here, there is no need for documentation.
  
- Always type the variables and put a return type

- Do not calculate the length of an array in the condition statement of a loop.

  Put it on the assignment statement. This allows us to not calculate length at each loop. 
  
  Wrong :
  
    ```php
    for ($i=0; $i < count($myArray); ++$i)
    {
     [...]
    }
    ```
    
  Good : 
  
    ```php
    for ($i=0, $myArrayLength = count($myArray);  $i < $myArrayLength; ++$i)
    {
      [...]
    }
    ```
  
- Always use === instead of == unless we cannot do otherwise. == can do conversions so === performs better.

Next section : [CSS side or SCSS side](css.md)