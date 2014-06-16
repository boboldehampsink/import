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
 - View your import history
 
Todo:
 - Import all ElementTypes (currently only Entries)
 - Support JSON and XML
 - Import a fixed value in absence of data
 - More info in the import summary mail

###Screenshots###

History
![History](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/history.png)

Upload
![Upload](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/upload.png)

Map fields
![Map](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/map.png)

Map fields (2)
![Fields](https://raw.githubusercontent.com/boboldehampsink/CraftImportPlugin/gh-pages/images/fields.png)

Changelog
=================
###0.6.9###
 - Added parent matching, so you can import entries as children of other entries

###0.6.8###
 - Only list sections for which the user has permissions, also fixing an entrytype listing bug if the first found section was a single.

###0.6.7###
 - Bugfix: Criteria matching now checks all statuses and has no limit

###0.6.6###
 - Added an "onBeforeImportDelete" event, so your plugin can intervene on deletion by this plugin

###0.6.5###
 - Disabled listing of singles to import into

###0.6.4###
 - Fixed a redirecting bug that occured in the previous update

###0.6.3###
 - Better live import updates

###0.6.2###
 - Fixed a bug where errors in import failed to render a history detail page

###0.6.1###
 - Get pending task info in import overview
 - Ability to choose wether you want to receive an e-mail or not
 
###0.6###
 - Added an import history tab

###0.5.2###
 - Fixed a bug where specific backup settings would fail the import task

###0.5.1###
 - Fixed a bug where some objects were supposed to be arrays

###0.5###
 - The plugin now checks if you meet the minimum Craft build that's required
 - Ability to backup database before importing

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