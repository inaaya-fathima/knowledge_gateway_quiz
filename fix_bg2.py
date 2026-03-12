import os
import re

dir_path = r'c:\xampp\htdocs\ca2.0\quiz-system'
for root, dirs, files in os.walk(dir_path):
    for filename in files:
        if filename.endswith('.php') or filename.endswith('.html'):
            filepath = os.path.join(root, filename)
            with open(filepath, 'r', encoding='utf-8') as file:
                content = file.read()
            
            # Remove inline 'background: #HEX' and 'background: rgba(...)'
            content = re.sub(r'background:\s*(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\));?\s*', '', content)
            
            # Remove empty style attributes left over
            content = re.sub(r'style="\s*"', '', content)
            
            with open(filepath, 'w', encoding='utf-8') as file:
                file.write(content)

print("Done stripping background statements.")
