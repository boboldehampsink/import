Import plugin for Craft CMS
=================

Plugin that allows you to import data from CSV files.

Features:
 - Map CSV columns onto Fields
 - Append, replace or delete data
   - When replacing or deleting, you can build your own criteria for finding
 - Has a hook "registerFieldTypeOperations" to parse special FieldType inputs if you want.
 - Uses the Task service to import while you work on.
 - Automatically detects CSV delimiters
 - Will connect Entries, Users and Assets by searching for them
 
Todo:
 - Import all ElementTypes (currently only Entries)
 - Give more info about the import process

###Screenshots###

Upload
![Upload](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/upload.png)

Map fields
![Map](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/map.png)

Map fields (2)
![Fields](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/fields.png)