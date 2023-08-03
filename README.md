# PHP Documentation Generator

A PHP tool to generate a clean and structured documentation based on PHP documentation, and following
[Diátaxis](https://diataxis.fr/) documentation framework.

This tool was written for [api-platform](https://api-platform.com/docs) and is a work in progress. 

## Installation

### With phar.io 

```
phive install php-documentation-generator/php-documentation-generator
```

### Using git

```
git clone git@github.com:php-documentation-generator/php-documentation-generator
cd php-documentation-generator
composer install
```

## Usage

### Reference

To create an API reference of a PHP source code directory:

```
PDG_AUTOLOAD=vendor/autoload.php ./bin/pdg references src docs
```

Or for a single PHP file:

```
PDG_AUTOLOAD=vendor/autoload.php ./bin/pdg reference src/Foo.php > Foo.mdx
```

### Guides

To generate a guide from a PHP file use the `bin/pdg guide` command.

There's also an adapted version of `phpunit` to run a guide tests with `bin/pdg-phpunit`.

## Configuration

Example of API Platform's configuration:

```
pdg:
    guides:
        base_url: '/docs/guide'
        output: '/data/docs/guides'
        src: './guides'
    references:
        base_url: '/docs/reference'
        exclude: ['*Factory.php', '*.tpl.php']
        exclude_path: ['JsonSchema/Tests', 'Metadata/Tests', 'OpenApi/Tests']
        namespace: 'ApiPlatform'
        output: '/data/docs/reference'
        src: '../src'
        tags_to_ignore: ['@experimental', '@internal']
```

Find also a programmatic usage on [API Platform's website](https://github.com/api-platform/website/blob/3f936bec48477a6709028e557622af961e2ca507/pwa/Dockerfile#L21-L31)

## Notes

This tool is not ready to be customized or used as-is and you'll need to code to adapt to your needs. The code architecture is based on extending Reflection with advanced types (read from php documentation) and Linking support. Feel free to reuse this nice basis to help with your documentation. Templates are written in old-school, hard to read PHP and only output MDX as needed for API Platform. We don't guarantee any backward compatibility release yet.
