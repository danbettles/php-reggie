# Reggie 🤴🏻

Reggie is a very simple regular-expression (regex) builder for PHP.  It was created to eliminate the clutter and confusion involved in assembling regexes from various different parts, to help prevent hard-to-find bugs and make code easier to understand.

Regexes can be confusing enough: having to also mentally-parse multiple complicated concatenation and interpolation operations makes things many times more difficult.  That's where Reggie can help.  Its fluent interface breaks things down into more distinct pieces, eliminates a ton of hieroglyphics, makes things more readable.

## Examples

A few examples adapted from real-world code.

> [!NOTE]
> For the sake of clarity and brevity, the following code uses literals as inputs.  Consequently, some examples may appear simplistic&mdash;perhaps even pointless.  Indeed, the benefits of using Reggie will become apparent only when regexes must be built from various variables, constants, etc.

### PHP Pathnames

```php
$regexStr = (new Builder())
    ->caseInsensitive()
    ->anchorEnd()
    ->addLiteral('.php')
    ->buildString()
;

// '~\.php$~i' === $regexStr
```

### Project Paths to Ignore

```php
$builder = new Builder();

$regexStr = $builder
    ->anchorStart()
    ->addLiteral("/path/to/project/")
    ->addSubpattern(
        $builder->listOfAlternatives(['vendor', 'var', 'node_modules'], quoteEach: true)
    )
    ->addLiteral('/')
    ->buildString()
;

// '~^/path/to/project/(vendor|var|node_modules)/~' === $regexStr
```

## Usage

### `Builder`

Generally speaking, the builder class provides two kinds of methods for building a regex:

- methods that change the flags;
- methods that add chunks (patterns or literals).

> [!IMPORTANT]
> Methods that add chunks have the prefix "add"; *these must be called in the order you want the chunks to appear in the regex*.  Other methods can be called whenever makes sense to you.
