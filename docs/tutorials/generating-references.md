# Generating References

A _reference_ contains technical references for APIs and other aspects of this project. It describes how it works and
how to use it but assumes that you have a basic understanding of key concepts.

## Generating All References

```shell
pdg references docs/references
```

> Note: the `docs/references` argument is the path where the references files will be generated. If you omit this argument, the
> references will just be printed on the command output.

## Generating a Single Reference

```shell
pdg reference src/Controller/IndexController.php --output docs/references/Controller/IndexController.md
```

> Note: the `--output` option is the path where the reference file will be generated. If you omit this option, the
> reference will just be printed on the command output.

## Generating References Index

```shell
pdg references:index docs/references
```

> Note: the `docs/references` argument is the path where the references index will be generated. If you omit this
> argument, the index will just be printed on the command output.

---

<p align="center">
<a href="configuring-pdg.md">&lt; Configuring PDG</a> -
<a href="generating-guides.md">Generating Guides &gt;</a>
</p>
