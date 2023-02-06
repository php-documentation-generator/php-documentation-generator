# Generating Guides

A _guide_ is a recipe. It guides you through the steps involved in addressing key problems and use-cases. It is more
advanced than tutorials and assumes some knowledge of how a project works.

## Generating All Guides

```shell
pdg guides docs/guides
```

> Note: the `docs/guides` argument is the path where the guide files will be generated. If you omit this argument, the
> guides will just be printed on the command output.

## Generating a Single Guide

```shell
pdg guide path/to/guides/use-doctrine.php --output docs/guides/use-doctrine.md
```

> Note: the `--output` option is the path where the guide file will be generated. If you omit this option, the
> guide will just be printed on the command output.

## Generating Guides Index

```shell
pdg guides:index docs/guides
```

> Note: the `docs/guides` argument is the path where the guides index will be generated. If you omit this
> argument, the index will just be printed on the command output.

---

<p align="center">
<a href="generating-references.md">&lt; Generating References</a> -
<a href="testing-guides.md">Testing Guides &gt;</a>
</p>
