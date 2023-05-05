# <?php echo $c->name; ?> <Badge><?php echo $c->type; ?></Badge>

```php
<?php echo strtolower($c->type); ?> <?php echo $c->name; ?>
<?php
if ($c->getParentClass()) {
    ?>
extends <?php echo $c->getParentClass()->name; ?>
<?php
}
?>
<?php foreach ($c->getMethods() as $m) {
    if (!$m->isPublic()) {
        continue;
    }
    ?>
<?php echo $m->name; ?>
<?php

} ?>
```
