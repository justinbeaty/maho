#!/bin/bash

for file in *.css; do
    deno run --allow-read ~/bin/pigeon.js "$file" > "../../final-css/$file"
done
