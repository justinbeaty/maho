#!/bin/bash

for file in *.css; do
    cat "$file" >> ../styles.css
done
