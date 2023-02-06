# Using a Template

By default, PDG generates files (references and guides) in [Markdown](https://en.wikipedia.org/wiki/Markdown). It uses
a [Twig](https://twig.symfony.com/) template to generate a proper documentation file, which can easily be overridden.

## Overriding the Reference Template

A reference template is a Twig file representing a class. It receives a `class` object argument which represents it.

Download the default template file in your project and customize it to your needs:
https://github.com/php-documentation-generator/php-documentation-generator/blob/main/template/references/reference.md.twig.

## Overriding the References Index Template

A references index template is a Twig file representing an index of references. It receives a `namespaces` array
argument containing all references classes indexed by namespace.

Download the default template file in your project and customize it to your needs:
https://github.com/php-documentation-generator/php-documentation-generator/blob/main/template/references/index.md.twig.

## Overriding the Guide Template

[//]: # (TODO write documentation)

Download the default template file in your project and customize it to your needs:
https://github.com/php-documentation-generator/php-documentation-generator/blob/main/template/guides/guide.md.twig.

## Overriding the Guides Index Template

[//]: # (TODO write documentation)

Download the default template file in your project and customize it to your needs:
https://github.com/php-documentation-generator/php-documentation-generator/blob/main/template/guides/index.md.twig.

## Using a Template

To use a custom template on `reference`, `references`, `references:index`, `guide`, `guides` and `guides:index`
commands, use the `--template` option:

```shell
pdg references docs/references --template path/to/template/reference.md.twig
pdg reference src/Controller/IndexController.php --output docs/references/Controller/IndexController.md --template path/to/template/reference.md.twig
pdg references:index docs/references --template path/to/template/reference-index.md.twig

pdg guides docs/guides --template path/to/template/guide.md.twig
pdg guide path/to/guides/use-doctrine.php --output docs/guides/use-doctrine.md --template path/to/template/guide.md.twig
pdg guides:index docs/guides --template path/to/template/guide-index.md.twig
```

---

<p align="center"><a href="README.md">How-to Guides</a></p>
