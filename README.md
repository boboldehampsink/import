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
 
Roadmap
 - Import more ElementTypes (Tags, Globals, Assets?) (0.8)
 - Support JSON and XML (0.9)
 
Important:
=================
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

Changelog
=================
###0.8.10###
 - Fixed category structure importing

###0.8.9###
 - Fixed errors when importing single-option fieldtype data (thanks to Richard Brown)

###0.8.8###
 - Remove title workaround, the fix is to escape comma's
 - Better date parsing

###0.8.7###
 - Added ancestor matching (by uri)
 - Improved entry type matching
 - Added a workaround for title matching

###0.8.6###
 - Fixed parent matching

###0.8.5###
 - Added handling of option-based fieldtypes
 - Fixed a bug with the onImportFinish event
 - Improved the task checker

###0.8.4###
 - Fixed handling element fields for category element
 - Make sure there's always a valid criterium when matching

###0.8.3###
 - Respect element connect limits

###0.8.2###
 - Updated the slugify function to match the latest Craft createSlug function
 - Check if the installation supports usergroups
 - Fixed phpunit unit testing

###0.8.1###
 - Respect import order when connecting to entries, assets or users
 - Report when deleting fails

###0.8.0###
 - Added the ability to import Users and Categories
 - Added the ability to download the originally uploaded file
 - Added the ability to delete import history
 - Parse numbers while respecting locales
 - Added Date FieldType parsing
 - Smoother error handling
 - Changed the "registerImportFinish" hook to "onImportFinish" event
 - Improved UI

###0.7.3###
 - Fixed a bug that led to not importing data and not failing import

###0.7.2###
 - Added the ability to run a custom hook on import finish
 - Added behaviour permissions and a section permissions check/warning
 - Added unit testing via phpunit

###0.7.1###
 - Added Number FieldType parsing (as float)
 - Fixed a bug with importing Expiry Date

###0.7.0###
 - Ability to revert imports
 - Fixed a bug with parent matching where the parent wasn't looked up in the same section

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