Import plugin for Craft CMS
=================

Plugin that allows you to import data from CSV files.

Usage
=================
Importing data is a two step process:

Step 1. Select import options
- Choose the type of element to import (Category, Entry, or User)
- Select the corresponding category group, section, entry type, or user group
- Select the Import Behavior:
    - Append Data — Will add new categories, entries, or users.
    - Replace Data — Will update data for existing matched categories, entries, or users.
    - Delete Data — Will delete data for existing matched categories, entries, or users.

Step 2. Map CSV data
- Map the destination fields for the csv data.
- Select which fields will be used as criteria to match existing records to replace (update) or delete data.

And import!

Features
=================
 - Import Entries, Users and Categories
 - Map CSV columns onto Fields
 - Append, replace or delete data
   - When replacing or deleting, you can build your own criteria for finding
 - Has a hook "registerImportOperation" to parse special FieldType inputs if you want.
 - Has events "onImportFinish" and "onBeforeImportDelete" 
   - These will notify you when the import finishes or wants to delete an element
 - Uses the Task service to import while you work on.
 - Automatically detects CSV delimiters
 - Will connect Entries (also Structures), Categories, Users and Assets by searching for them
 - Will send a summary email when the task if finished
 - View your import history
 - Ability to revert imports
 
###Roadmap###
 - Import more ElementTypes (Tags, Globals, Assets?) (0.8)
 - Support JSON and XML (0.9)
 
Important:
=================
The plugin's folder should be named "import"

Frequently Asked Questions
=================
- How do I indicate an element field type in the CSV file?
	- Import utilizes "search", so it can be anything that makes it unique
- How would I indicate a multiplicity of element field types in the CSV file?
	- Just separate them by comma
- How should I write my CSVs so parent & child entries work properly?
	- Write it like Ancestor/Child/Entry and connect as "Ancestor"
- Do parent entries already need to exist before I import a CSV? Or can they be created form the came CSV import as their child entries?
	- The entry should exist, but if it comes in the row before then it will exist and it will pass.
- Is there an "Export" feature so I can get to data that is more complete in the DB than in my CSV file?
	- No, but there is the "Export" plugin that can be found here: https://github.com/boboldehampsink/export

Screenshots
=================
History
![History](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/history.png)

Upload (entries)
![Upload](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/entries.png)

Upload (users)
![Upload](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/users.png)

Map fields
![Map](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/map.png)
