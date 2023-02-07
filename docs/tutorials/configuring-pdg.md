# Configuring PDG

PDG requires a configuration file. It automatically detects `pdg.config.yaml`, `pdg.config.yml`, `pdg.config.dist.yaml`
and `pdg.config.dist.yml` files.

> Note: the configuration file name can be overridden using the `PDG_CONFIG_FILE` environment variable.

Check for the default configuration:

```yaml
api_platform_pdg:
    # Project autoload
    autoload: 'vendor/autoload.php'
    # References configuration
    reference:
        # Root path for code parsing
        src: 'src'
        # Root namespace
        namespace: 'App'
        patterns:
            # Directories to parse (supports pattern syntax)
            directories: [ '' ]
            # File names to parse (supports pattern syntax)
            names: [ '*.php' ]
            # Files or directories to ignore (supports pattern syntax)
            exclude: [ ]
            # PHP tags to ignore
            class_tags_to_ignore: [ '@internal', '@experimental' ]
    target:
        directories:
            # Path to output generated reference files
            reference_path: 'docs/reference'
            # Path to output generated guide files
            guide_path: 'docs/guide'
        # Base url for link generation (e.g.: `/docs`, `docs`, `https://github.com/foo/bar/blob/main/docs/docs/`)
        base_url: '/docs'
```

---

<p align="center">
<a href="quick-install-guide.md">&lt; Quick Install Guide</a> -
<a href="generating-references.md">Generating References &gt;</a>
</p>
