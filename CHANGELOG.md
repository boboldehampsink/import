Changelog
=================
### 0.8.34 ###
 - Show filename in history overview

### 0.8.33 ###
 - Require an asset source, closing #132

### 0.8.32 ###
 - Fixed broken relative link for user (thanks to @timkelty)
 - Fix for assets giving 404 on download (thanks to @MRolefes)
 - Allow element fieldtypes to find disabled elements (thanks to @timkelty)

### 0.8.31 ###
 - Make sure we have a unique filename to prevent conflict
 - Cache opening of file to prevent a download on every step

### 0.8.30 ###
 - Added the ability to upload the import file to an asset source for better persistency
 - Save user id to settings so we can run the task without user session
 - Fixed a bug where deleting/replacing didn't work on PHP7
 - Fixed a bug where logs didn't show

### 0.8.29 ###
 - All service code is now fully covered by unit tests (thanks to @bvangennep)

### 0.8.28 ###
 - Fix history overview item url, closing issue #93 (thanks to @timkelty)
 - Fix for "only variables should be passed by reference" (PHP7), closing issue #96 (thanks to @ianshea)

### 0.8.27 ###
 - Allow custom options for categories and users too,
 - Set user pending when status field is pending, closing issue #53
 - Use correct category fieldlayout, closing issue #61
 - Adds the ability to import in a specific locale, closing issue #62
 - Revert the validate content option, closing issue #64
 - Adds the ability to import specific ID's, closing issue #65
 - Fix redirect after importing in some cases, closing issue #78

### 0.8.26 ###
 - Added the "registerImportOptionPaths" hook (thanks to @lindseydiloreto)
 - Replace "sourceId" with "folderId" criteria when linking an asset field (thanks to @damiani)

### 0.8.25 ###
 - Add detection of text/x-comma-separated-values, fixing issue #67

### 0.8.24 ###
 - You can now choose if you want to validate your content

### 0.8.23 ###
 - Fixed not showing full history set (missing one line), fixing issue #52
 - Skip row when criteria value looks suspicious - preventing possible data loss
 - Fixed an undefined index error
 - Fixed js not being executed when no sections exist, closing issue #56

### 0.8.22 ##
 - Added support for (translated) yes/no importing into lightswitch fields

### 0.8.21 ###
 - Bugfix: Start errors at line 2, as line one are column headers
 - Added MIT license

### 0.8.20 ###
 - Check if uploaded file is valid, closing issue #46

### 0.8.19 ###
 - Fixed a bug that broke User connecting

### 0.8.18 ###
 - Fixed undefined variable $user, closing issues #42 and #43

### 0.8.17 ###
 - Only import when atleast one row is found

### 0.8.16 ###
 - Added a registerImportService hook so you can write an import service for other/your own element type(s)
 - Author can now be an id, username or emailaddress
 - Added a backup permission and better security for existing permissions
 - Added a check to see if the import file (still) exists

### 0.8.15 ###
 - added an onImportStart event
 - added an modifyImportRow hook (thanks to freddietilley)
 - improved event handling - min. required Craft build is now 2615

### 0.8.14 ###
 - Made versioning unavailable for Craft Personal licenses, as its not supported by Craft
 - Fixed content attributes not (always) being pre-set on criteria models
 - Set email as username when defined in config

### 0.8.13 ###
 - Added ability to connect to multiple categories

### 0.8.12 ###
 - Added support for the tag field type (thanks to Richard Brown)
 - Added getCsrfInput function to forms

### 0.8.11 ###
 - Added automatic line ending detection
 - Improved usability

### 0.8.10 ###
 - Fixed category structure importing

### 0.8.9 ###
 - Fixed errors when importing single-option fieldtype data (thanks to Richard Brown)

### 0.8.8 ###
 - Remove title workaround, the fix is to escape comma's
 - Better date parsing

### 0.8.7 ###
 - Added ancestor matching (by uri)
 - Improved entry type matching
 - Added a workaround for title matching

### 0.8.6 ###
 - Fixed parent matching

### 0.8.5 ###
 - Added handling of option-based fieldtypes
 - Fixed a bug with the onImportFinish event
 - Improved the task checker

### 0.8.4 ###
 - Fixed handling element fields for category element
 - Make sure there's always a valid criterium when matching

### 0.8.3 ###
 - Respect element connect limits

### 0.8.2 ###
 - Updated the slugify function to match the latest Craft createSlug function
 - Check if the installation supports usergroups
 - Fixed phpunit unit testing

### 0.8.1 ###
 - Respect import order when connecting to entries, assets or users
 - Report when deleting fails

### 0.8.0 ###
 - Added the ability to import Users and Categories
 - Added the ability to download the originally uploaded file
 - Added the ability to delete import history
 - Parse numbers while respecting locales
 - Added Date FieldType parsing
 - Smoother error handling
 - Changed the "registerImportFinish" hook to "onImportFinish" event
 - Improved UI

### 0.7.3 ###
 - Fixed a bug that led to not importing data and not failing import

### 0.7.2 ###
 - Added the ability to run a custom hook on import finish
 - Added behaviour permissions and a section permissions check/warning
 - Added unit testing via phpunit

### 0.7.1 ###
 - Added Number FieldType parsing (as float)
 - Fixed a bug with importing Expiry Date

### 0.7.0 ###
 - Ability to revert imports
 - Fixed a bug with parent matching where the parent wasn't looked up in the same section

### 0.6.9 ###
 - Added parent matching, so you can import entries as children of other entries

### 0.6.8 ###
 - Only list sections for which the user has permissions, also fixing an entrytype listing bug if the first found section was a single.

### 0.6.7 ###
 - Bugfix: Criteria matching now checks all statuses and has no limit

### 0.6.6 ###
 - Added an "onBeforeImportDelete" event, so your plugin can intervene on deletion by this plugin

### 0.6.5 ###
 - Disabled listing of singles to import into

### 0.6.4 ###
 - Fixed a redirecting bug that occured in the previous update

### 0.6.3 ###
 - Better live import updates

### 0.6.2 ###
 - Fixed a bug where errors in import failed to render a history detail page

### 0.6.1 ###
 - Get pending task info in import overview
 - Ability to choose wether you want to receive an e-mail or not

### 0.6 ###
 - Added an import history tab

### 0.5.2 ###
 - Fixed a bug where specific backup settings would fail the import task

### 0.5.1 ###
 - Fixed a bug where some objects were supposed to be arrays

### 0.5 ###
 - The plugin now checks if you meet the minimum Craft build that's required
 - Ability to backup database before importing

### 0.4.2 ###
 - You now get a warning when a CSV row is malformed (per mail)
 - Now supports slug importing

### 0.4.1 ###
 - In certain situations, values of variables within the plugin weren't properly checked

### 0.4 ###
 - When the import task if finished, you'll receive an e-mail with info about the import task

### 0.3.1 ###
 - Fixed a bug where Title fields would not be set

### 0.3 ###
 - Initial push to GitHub
