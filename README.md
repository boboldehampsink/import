Import plugin for Craft CMS
=================

Plugin that allows you to import data from CSV files.

Features:
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
 
Roadmap:
 - Import more ElementTypes (Tags, Globals, Assets?) (0.8)
 - Support JSON and XML (0.9)
 
Important:
The plugin's folder should be named "import"

###Screenshots###

History
![History](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/history.png)

Upload (entries)
![Upload](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/entries.png)

Upload (users)
![Upload](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/users.png)

Map fields
![Map](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/map.png)