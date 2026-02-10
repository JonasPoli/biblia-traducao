import json
import re
import sys
import os

if len(sys.argv) < 4:
    print("Usage: python3 generate_book_sql.py <input_md_file> <output_sql_file> <json_ids_file> [version_id]")
    exit(1)

input_md_file = sys.argv[1]
output_sql_file = sys.argv[2]
json_ids_file = sys.argv[3]
version_id = int(sys.argv[4]) if len(sys.argv) > 4 else 17

# Read verse IDs
try:
    with open(json_ids_file, 'r') as f:
        content = f.read()
        # Find the start of the JSON object
        start_index = content.find('{')
        if start_index == -1:
             print(f"Error: Could not find JSON object start in {json_ids_file}")
             exit(1)
        end_index = content.rfind('}')
        if end_index == -1:
             print(f"Error: Could not find JSON object end in {json_ids_file}")
             exit(1)
        
        json_str = content[start_index:end_index+1]
        verse_ids = json.loads(json_str)

except FileNotFoundError:
    print(f"Error: {json_ids_file} not found.")
    exit(1)
except json.JSONDecodeError as e:
    print(f"Error parsing JSON: {e}")
    exit(1)

# Function to clean text based on user requirements
def clean_text(text):
    # Remove leading "- " or "\- " 
    text = re.sub(r'^\\?- ', '', text)
    # Convert "\! " to "! "
    text = text.replace('\\! ', '! ')
    # Convert "**" to bold tags
    def replace_bold(match):
        return f"<b>{match.group(1)}</b>"
    text = re.sub(r'\*\*(.*?)\*\*', replace_bold, text)
    
    # Also clean up other likely markdown escapes
    text = text.replace('\\-', '-')
    
    return text.strip()

# Read markdown file
try:
    with open(input_md_file, 'r') as f:
        lines = f.readlines()
except FileNotFoundError:
     print(f"Error: {input_md_file} not found.")
     exit(1)

sql_statements = []

for line in lines:
    line = line.strip()
    if not line:
        continue
        
    # Regex to match "Chapter:Verse \- Text" or similar
    match = re.match(r'^(\d+):(\d+)\s*(?:\\?-)?\s*(.*)', line)
    
    if match:
        chapter = match.group(1)
        verse = match.group(2)
        raw_text = match.group(3)
        
        # Clean the text part
        text = clean_text(raw_text)
        
        # Look up ID
        key = f"{chapter}:{verse}"
        if key in verse_ids:
            verse_id_val = verse_ids[key]
            # Escape single quotes for SQL
            safe_text = text.replace("'", "''")
            sql = f"INSERT INTO biblia_verse_text (verse_id, version_id, text) VALUES ({verse_id_val}, {version_id}, '{safe_text}');"
            sql_statements.append(sql)
        else:
            print(f"Warning: Verse {key} not found in database mapping. Skipping.")
    else:
        pass

# Write SQL file
with open(output_sql_file, 'w') as f:
    f.write('\n'.join(sql_statements))

print(f"Generated {len(sql_statements)} INSERT statements in {output_sql_file}")
