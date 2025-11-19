# Reggie 🤴🏻

Reggie is a very simple regular-expression (regex) builder for PHP.  It was created to eliminate the clutter, and confusion, involved in assembling regexes from various different parts (e.g. variables, constants, etc), to help prevent hard-to-find bugs and make code easier to understand.

Regexes can be confusing enough, eh: having to also mentally-parse multiple complicated concatenation, and interpolation, operations, for example, makes things many times more difficult.  That's where Reggie can help.  Its fluent interface breaks things down into more distinct chunks, eliminates a ton of hieroglyphics, making things more readable.

## Examples

A few examples adapted from real-world code.

> [!NOTE]
> For the sake of clarity and brevity, the following code uses literals as inputs.  Consequently, some examples may appear simplistic&mdash;perhaps even pointless.  Indeed, the benefits of using Reggie will become apparent only when regexes must be built from various variables, constants, etc.

### PHP Pathnames

```php
$regex = (new Builder())
    ->setFlags('i')
    ->anchorRight()
    ->addLiteral('.php')
    ->toString()
;

// $regex === '~\.php$~i'
```

### Project Paths to Ignore

```php
$builder = new Builder();

$regex = $builder
    ->anchorLeft()
    ->addLiteral("/path/to/project/")
    ->addSubpattern(
        $builder->listOfAlternatives(['vendor', 'var', 'node_modules'], quoteEach: true)
    )
    ->addLiteral('/')
    ->toString()
;

// $regex === '~^/path/to/project/(vendor|var|node_modules)/~'
```
