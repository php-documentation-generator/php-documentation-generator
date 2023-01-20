#!/bin/bash

for d in tests/Application/guides/*.php; do
  APP_ENV=test php bin/pdg pdg:test:guide $d
done