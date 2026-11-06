import os
import shutil

# Get all files in the current directory
for filename in os.listdir("."):
    if os.path.isfile(filename):
        # Check if the filename contains any uppercase letters
        if any(char.isupper() for char in filename):
            lowercase_name = filename.lower()
            # Avoid overwriting existing files
            if lowercase_name != filename and not os.path.exists(lowercase_name):
                shutil.copy2(filename, lowercase_name)
                print(f"Duplicated '{filename}' as '{lowercase_name}'")
