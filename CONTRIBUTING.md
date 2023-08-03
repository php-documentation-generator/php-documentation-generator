# Contributing to PDG

First of all, thank you for contributing, you're awesome!

To have your code integrated in PDG, there are some rules to follow, but don't panic, it's easy!

## Reporting Bugs

If you happen to find a bug, we kindly request you to report it. However, before submitting it, please:

* Check the [documentation](README.md)

Then, if it appears that it's a real bug, you may report it using GitHub by following these 3 points:

* Check if the bug is not already reported!
* A clear title to resume the issue
* A description of the workflow needed to reproduce the bug

> _NOTE:_ Don't hesitate giving as much information as you can (OS, PHP version extensions...)

## Pull Requests

### Writing a Pull Request

First of all, you must decide on what branch your changes will be based depending of the nature of the change.

To prepare your patch:

1. Fork PDG and add your Git remote: `git remote add <your-name> git@github.com:<your-name>/php-documentation-generator.git`
2. Create a branch for your code: `git checkout -b my_patch`
3. Fix the bug or add the feature, then commit it: `git commit -a -m "fix: my patch"`
4. You can now push your code and open your Pull Request: `git push <your-name> my_patch`

### Matching Coding Standards

PDG follows [Symfony coding standards](https://symfony.com/doc/current/contributing/code/standards.html). But don't
worry, you can fix CS issues automatically using the [PHP CS Fixer](https://cs.symfony.com) tool:

    php-cs-fixer.phar fix

And then, add the fixed file to your commit before pushing.
Be sure to add only **your modified files**. If any other file is fixed by cs tools, just revert it before committing.

### Backward Compatibility Promise

PDG follows the [Symfony Backward Compatibility Promise](https://symfony.com/doc/current/contributing/code/bc.html).

As users need to use named arguments when using our attributes, they don't follow the backward compatibility rules applied to the constructor.

When you are making a change, make sure no BC break is added.

### Deprecating Code

Adding a deprecation is sometimes necessary in order to follow the backward compatibility promise and to improve an existing implementation.

They can only be introduced in minor or major versions (`main` branch) and exceptionally in patch versions if they are critical.

See also the [related documentation for Symfony](https://symfony.com/doc/current/contributing/code/conventions.html#deprecating-code).

### Sending a Pull Request

When you send a PR, just make sure that:

* You add valid test cases.
* Tests are green.
* You add or update the documentation.
* You make the PR on the same branch you based your changes on. If you see commits that you did not make in your PR,
  you're doing it wrong.

The commit messages must follow the [Conventional Commits specification](https://www.conventionalcommits.org/).
The following types are allowed:

* `fix`: bug fix
* `feat`: new feature
* `docs`: change in the documentation
* `spec`: spec change
* `test`: test-related change
* `perf`: performance optimization
* `ci`: CI-related change
* `chore`: updating dependencies and related changes

Examples:

    fix(metadata): resource identifiers from properties 

    feat(validation): introduce a number constraint

    feat(metadata)!: new resource metadata system, BC break

    docs(doctrine): search filter on uuids

    test(doctrine): mongodb disambiguation

We strongly recommend the use of a scope.

### Tests

On PDG, there are two kinds of tests: unit and integration tests. Both are written in [PHPUnit](https://phpunit.de/)
with the [Symfony PHPUnit Bridge](https://symfony.com/doc/current/components/phpunit_bridge.html).

To launch tests:

    vendor/bin/simple-phpunit --stop-on-failure -vvv

If you want coverage, you will need the `pcov` PHP extension and run:

    vendor/bin/simple-phpunit --coverage-html coverage -vvv --stop-on-failure

Sometimes there might be an error with too many open files when generating coverage. To fix this, you can increase the
`ulimit`, for example:

    ulimit -n 4000

Coverage will be available in `coverage/index.html`.

# License and Copyright Attribution

When you open a Pull Request to PDG, you agree to license your code under the [MIT license](../LICENSE)
and to transfer the copyright on the submitted code to Kévin Dunglas.

Be sure to you have the right to do that (if you are a professional, ask your company)!

If you include code from another project, please mention it in the Pull Request description and credit the original author.
