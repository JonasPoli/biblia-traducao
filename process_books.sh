#!/bin/bash

# Define books array: "BookID|MarkdownFile|SQLFile"
# Note: Filenames are based on what was seen in `ls` output
BOOKS=(
    "45|romanos.md|romanos.sql"
    "46|I-conrintios.md|1_corintios.sql"
    "47|II-corintios.md|2_corintios.sql"
    "48|galatas.md|galatas.sql"
    "50|filipenses.md|filipenses.sql"
)

DOCS_DIR="docs/biblia-traduzida"
VERSION_ID=17

for entry in "${BOOKS[@]}"; do
    IFS="|" read -r BOOK_ID INPUT_FILE OUTPUT_FILE <<< "$entry"
    
    echo "Processing Book ID: $BOOK_ID ($INPUT_FILE)"
    
    # Generate SQL using Symfony command
    INPUT_PATH="${DOCS_DIR}/${INPUT_FILE}"
    
    if [ -f "$INPUT_PATH" ]; then
        php bin/console app:generate-bible-sql "$INPUT_PATH" "$BOOK_ID"
    else
        echo "Error: Input file $INPUT_PATH not found"
    fi
    
    echo "-----------------------------------"
done
