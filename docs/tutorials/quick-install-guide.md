# Quick Install Guide

We highly recommend to install PDG statically and globally on your machine, not as a project dependency.

PDG requires PHP 8.1 or higher to run. However, code of earlier PHP versions can be analyzed.

## Using the Phar (recommended)

1. Download the Phar file from https://github.com/php-documentation-generator/php-documentation-generator/releases/latest/download/pdg.phar
2. Run the Phar with PHP: `php pdg.phar`

## Using via Composer (not recommended)

Installing PDG through [Composer](https://getcomposer.org/) is not recommended as it may conflict with your
dependencies, and is not a project dependency but a static tool.

However, if you still want to install it through Composer, you should install it globally:

```shell
composer global require php-documentation-generator/php-documentation-generator
```

---

<p align="center">
<a href="README.md">&lt; Getting Started</a> -
<a href="configuring-pdg.md">Configuring PDG &gt;</a>
</p>
