# Configuring PDG

PDG requires a configuration file. It automatically detects `pdg.config.yaml`, `pdg.config.yml`, `pdg.config.dist.yaml`
and `pdg.config.dist.yml` files.

> Note: the configuration file name can be overridden using the `PDG_CONFIG_FILE` environment variable.

The configuration may look like this:

```yaml
pdg:
    # Project autoload for tests
    autoload: 'vendor/autoload.php'
    references:
        # Root of the source code, used for resolving a namespace based on the file path
        src: 'src'
        # Root namespace
        namespace: 'App'
        # Exclude glob pattern on file names
        exclude: ['*Factory.php']
        # Exclude paths, relative to src/
        exclude_path: ['Model']
        tags_to_ignore: ['@experimental', '@internal', '@ignore']
        # Output for the "references" command
        output: 'docs/references'
        # Base URL for references linking
        base_url: '/docs/references'
    guides:
        # Source for the "guides" command
        src: 'guides'
        # Output for the "guides" command
        output: 'docs/guides'
        # Base URL for guides linking
        base_url: '/docs/guides'
```

---

<p align="center">
<a href="quick-install-guide.md">&lt; Quick Install Guide</a> -
<a href="generating-references.md">Generating References &gt;</a>
</p>
