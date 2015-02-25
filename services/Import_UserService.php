<?php
namespace Craft;

/**
 * Import User Service.
 *
 * Contains logic for importing users
 *
 * @author    Bob Olde Hampsink <b.oldehampsink@itmundi.nl>
 * @copyright Copyright (c) 2015, Bob Olde Hampsink
 * @license   http://buildwithcraft.com/license Craft License Agreement
 *
 * @link      http://github.com/boboldehampsink
 */
class Import_UserService extends BaseApplicationComponent implements IImportElementType
{
    /**
     * Return groups.
     *
     * @return array|boolean
     */
    public function getGroups()
    {
        // Check if usergroups are allowed in this installation
        if (craft()->getEdition() == Craft::Pro) {

            // Get usergroups
            $groups = craft()->userGroups->getAllGroups();

            // Return when groups found
            if (count($groups)) {
                return $groups;
            }

            // Still return true when no groups found
            return true;
        }

        // Else, dont proceed with the user element
        return false;
    }

    /**
     * Return user model with group.
     *
     * @param array $settings
     *
     * @return UserModel
     */
    public function setModel(array $settings)
    {
        // Set up new user model
        $element = new UserModel();

        return $element;
    }

    /**
     * Set user criteria.
     *
     * @param array $settings
     *
     * @return ElementCriteriaModel
     */
    public function setCriteria(array $settings)
    {
        // Match with current data
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->limit = null;
        $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;

        return $criteria;
    }

    /**
     * Delete users.
     *
     * @param array $elements
     *
     * @return boolean
     */
    public function delete(array $elements)
    {
        $return = true;

        // Delete users
        foreach ($elements as $element) {
            if (!craft()->users->deleteUser($element)) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Prepare reserved ElementModel values.
     *
     * @param array            &$fields
     * @param BaseElementModel $element
     *
     * @return BaseElementModel
     */
    public function prepForElementModel(array &$fields, BaseElementModel $element)
    {

        // Set username
        $username = Import_ElementModel::HandleUsername;
        if (isset($fields[$username])) {
            $element->$username = $fields[$username];
            unset($fields[$username]);
        }

        // Set photo
        $photo = Import_ElementModel::HandlePhoto;
        if (isset($fields[$photo])) {
            $element->$photo = $fields[$photo];
        }

        // Set firstname
        $firstName = Import_ElementModel::HandleFirstname;
        if (isset($fields[$firstName])) {
            $element->$firstName = $fields[$firstName];
            unset($fields[$firstName]);
        }

        // Set lastname
        $lastName = Import_ElementModel::HandleLastname;
        if (isset($fields[$lastName])) {
            $element->$lastName = $fields[$lastName];
            unset($fields[$lastName]);
        }

        // Set email
        $email = Import_ElementModel::HandleEmail;
        if (isset($fields[$email])) {
            $element->$email = $fields[$email];
            unset($fields[$email]);

            // Set email as username
            if (craft()->config->get('useEmailAsUsername')) {
                $element->$username = $element->$email;
            }
        }

        // Set status
        $status = Import_ElementModel::HandleStatus;
        if (isset($fields[$status])) {
            $element->$status = $fields[$status];
            unset($fields[$status]);
        }

        // Set locale
        $locale = Import_ElementModel::HandleLocale;
        if (isset($fields[$locale])) {
            $element->$locale = $fields[$locale];
            unset($fields[$locale]);
        }

        // Set password
        $password = Import_ElementModel::HandlePassword;
        if (isset($fields[$password])) {
            $element->$password = $fields[$password];
            unset($fields[$password]);
        }

        // Return entry
        return $element;
    }

    /**
     * Save a user.
     *
     * @param BaseElementModel &$element
     * @param array            $settings
     *
     * @return boolean
     */
    public function save(BaseElementModel &$element, array $settings)
    {
        // Save user
        if (craft()->users->saveUser($element)) {

            // Assign to groups
            craft()->userGroups->assignUserToGroups($element->id, $settings['elementvars']['groups']);

            return true;
        }

        return false;
    }

    /**
     * Executes after saving a user.
     *
     * @param array            $fields
     * @param BaseElementModel $element
     */
    public function callback(array $fields, BaseElementModel $element)
    {
        // No callback for users
    }
}
