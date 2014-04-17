Import plugin for Craft CMS
=================

Plugin that allows you to import data from CSV files.

Features:
 - Map CSV columns onto Fields
 - Append, replace or delete data
   - When replacing or deleting, you can build your own criteria for finding
 - Has a hook "registerImportOperation" to parse special FieldType inputs if you want.
 - Uses the Task service to import while you work on.
 - Automatically detects CSV delimiters
 - Will connect Entries, Users and Assets by searching for them
 - Will send a summary email when the task if finished
 
Todo:
 - Import all ElementTypes (currently only Entries)
 - Support JSON and XML
 - Backup before importing, so you can rollback
 - Import a fixed value in absence of data
 - More info in the import summary mail

###Screenshots###

Upload
![Upload](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/upload.png)

Map fields
![Map](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/map.png)

Map fields (2)
![Fields](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/fields.png)

Changelog
=================
###0.5###
 - The plugin now checks if you meet the minimum Craft build that's required

###0.4.2###
 - You now get a warning when a CSV row is malformed (per mail)
 - Now supports slug importing

###0.4.1###
 - In certain situations, values of variables within the plugin weren't properly checked

###0.4###
 - When the import task if finished, you'll receive an e-mail with info about the import task

###0.3.1###
 - Fixed a bug where Title fields would not be set
 
###0.3###
 - Initial push to GitHub