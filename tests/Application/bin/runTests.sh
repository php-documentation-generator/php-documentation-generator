#!/bin/bash

for d in guides/*.php; do
  APP_ENV=test php bin/console app:test:guide $d
done

for f in src/*.php; do
  APP_ENV=test php bin/console app:test:guide $f
done