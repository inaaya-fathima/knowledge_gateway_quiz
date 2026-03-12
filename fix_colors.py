import os
import re

dir_path = r'c:\xampp\htdocs\ca2.0\quiz-system'
for root, dirs, files in os.walk(dir_path):
    for filename in files:
        if filename.endswith('.php') or filename.endswith('.html'):
            filepath = os.path.join(root, filename)
            with open(filepath, 'r', encoding='utf-8') as file:
                content = file.read()
            
            # Remove inline background-color styles in style attributes
            # Matches: background-color: #hex; or background-color: rgba(...); or background-color: var(...);
            content = re.sub(r'background-color:\s*(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\)|var\([^)]+\));?\s*', '', content)
            
            # Remove any empty style attributes
            content = re.sub(r'style="\s*"', '', content)
            
            # Additional cleanup for specific CSS blocks where background-color was set:
            # specifically in admin/manage_questions.php, add_question.php, view_results.php
            content = re.sub(r'th\s*\{\s*background-color:[^}]+}', 'th { }', content)
            
            with open(filepath, 'w', encoding='utf-8') as file:
                file.write(content)
                  
